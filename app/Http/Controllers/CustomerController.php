<?php

namespace App\Http\Controllers;

use App\Libraries\RouterOSAPI;
use App\Models\Connection;
use App\Models\ConnectionUsageSample;
use App\Models\Customer;
use App\Models\HotspotUserBilling;
use App\Models\Invoice;
use App\Models\MegaPayment;
use App\Models\MeteredUsage;
use App\Models\Message;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\InvoiceBillingService;
use App\Services\InvoiceNotificationService;
use App\Services\Sms\AdvantaSmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    private const BYTES_PER_GB = 1073741824;
    private const BYTES_PER_MB = 1048576;
    private const SESSION_DISCONNECT_GRACE_SECONDS = 30;
    private const SESSION_HISTORY_SAMPLE_LIMIT = 240;
    private static array $columnCache = [];
    private static array $tableCache = [];

    public function index()
    {
        [$api, $error] = $this->connectApi();
        $plans = $this->availablePlans();
        $profileOptions = $this->availableProfileOptions($error ? null : $api, $plans);

        if ($error) {
            return view('customers.index', [
                'error' => $error,
                'users' => [],
                'activeSessions' => [],
                'hosts' => [],
                'cookies' => [],
                'plans' => $plans,
                'profileOptions' => $profileOptions,
            ]);
        }

        $usersRaw = $api->comm('/ip/hotspot/user/print') ?? [];
        $routerActiveSessions = $api->comm('/ip/hotspot/active/print') ?? [];
        $hosts = $api->comm('/ip/hotspot/host/print') ?? [];
        $cookies = $api->comm('/ip/hotspot/cookie/print') ?? [];

        $this->syncConnectionTelemetry($routerActiveSessions, $usersRaw, 'customers_index');
        $activeSessions = $this->augmentActiveSessionsWithTrackedConnections($routerActiveSessions);
        $finalUsers = $this->buildUsersWithStatus($usersRaw, $activeSessions, $hosts, $cookies);

        return view('customers.index', [
            'error' => null,
            'users' => $finalUsers,
            'activeSessions' => $activeSessions,
            'hosts' => $hosts,
            'cookies' => $cookies,
            'plans' => $plans,
            'profileOptions' => $profileOptions,
        ]);
    }

    public function section(Request $request, $section)
    {
        $section = strtolower(trim((string) $section));

        try {
            switch ($section) {
                case 'users':
                    $users = $this->fetchHotspotUsers();
                    return response()->view('customers.partials.users', compact('users'));

                case 'hosts':
                    $hosts = $this->fetchHotspotHosts();
                    return response()->view('customers.partials.hosts', compact('hosts'));

                case 'cookies':
                    $cookies = $this->fetchHotspotCookies();
                    return response()->view('customers.partials.cookies', compact('cookies'));

                case 'sessions':
                    $activeSessions = $this->fetchHotspotSessions();
                    return response()->view('customers.partials.sessions', compact('activeSessions'));

                default:
                    return response()->view('customers.partials.section-error', [
                        'title' => 'Invalid section',
                        'message' => 'The requested section is not recognized.',
                    ], 200);
            }
        } catch (\Throwable $e) {
            Log::error('CustomerController@section failed', [
                'section' => $section,
                'error' => $e->getMessage(),
            ]);

            return response()->view('customers.partials.section-error', [
                'title' => 'Refresh failed',
                'message' => 'Could not load this section. Check MikroTik connection and logs.',
            ], 200);
        }
    }

    public function userDetails(Request $request)
    {
        $username = trim((string) $request->query('username', ''));

        if ($username === '') {
            return response()->json(['message' => 'Username is required.'], 422);
        }

        [$api, $error] = $this->connectApi();
        if ($error) {
            return response()->json(['message' => $error], 500);
        }

        $usersRaw = $api->comm('/ip/hotspot/user/print') ?? [];
        $routerActiveSessions = $api->comm('/ip/hotspot/active/print') ?? [];
        $activeSessions = $this->augmentActiveSessionsWithTrackedConnections($routerActiveSessions);

        $rawUser = collect($usersRaw)->first(function (array $user) use ($username) {
            return (($user['name'] ?? $user['username'] ?? '') === $username);
        });

        if (!$rawUser) {
            $rawUser = $this->buildSyntheticUserFromSession($activeSessions, $username);
        }

        if (!$rawUser) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $hosts = $api->comm('/ip/hotspot/host/print') ?? [];
        $cookies = $api->comm('/ip/hotspot/cookie/print') ?? [];

        $normalizedUsers = $this->buildUsersWithStatus([$rawUser], $activeSessions, $hosts, $cookies);
        $user = $normalizedUsers[0] ?? null;

        $activeSession = collect($activeSessions)->first(function (array $session) use ($username) {
            return (($session['user'] ?? $session['username'] ?? '') === $username);
        });

        $this->syncConnectionTelemetry($routerActiveSessions, $usersRaw, 'user_details');

        $usage = $this->buildUsageSummary($username, $rawUser);

        $customer = Customer::query()
            ->where('username', $username)
            ->orderByDesc('id')
            ->first();

        $billing = HotspotUserBilling::query()->where('username', $username)->first();

        if (!$customer && $billing?->customer_id) {
            $customer = Customer::find($billing->customer_id);
        }

        $invoices = collect();
        $payments = collect();
        $balances = [
            'total_billed' => 0,
            'total_paid' => 0,
            'total_due' => 0,
            'open_invoices' => 0,
        ];

        if ($customer?->id) {
            $invoiceSelect = ['id', 'invoice_number', 'amount', 'status', 'created_at'];
            foreach (['total_amount', 'paid_amount', 'balance_amount', 'invoice_status', 'due_date'] as $column) {
                if ($this->hasColumn('invoices', $column)) {
                    $invoiceSelect[] = $column;
                }
            }

            $invoices = Invoice::query()
                ->where('customer_id', $customer->id)
                ->orderByDesc('id')
                ->limit(10)
                ->get($invoiceSelect)
                ->map(function (Invoice $invoice) {
                    try {
                        return app(InvoiceBillingService::class)->recalculate($invoice);
                    } catch (\Throwable $e) {
                        Log::warning('Invoice recalculate fallback in userDetails', [
                            'invoice_id' => $invoice->id,
                            'error' => $e->getMessage(),
                        ]);
                        return $invoice;
                    }
                });

            $paymentSelect = ['id', 'amount', 'method', 'transaction_id', 'created_at'];
            foreach (['currency', 'reference', 'transaction_code', 'status', 'paid_at'] as $column) {
                if ($this->hasColumn('payments', $column)) {
                    $paymentSelect[] = $column;
                }
            }

            $payments = Payment::query()
                ->where('customer_id', $customer->id)
                ->orderByDesc('id')
                ->limit(8)
                ->get($paymentSelect);

            $sumAmountCol = $this->hasColumn('invoices', 'total_amount') ? 'total_amount' : 'amount';
            $sumPaidCol = $this->hasColumn('invoices', 'paid_amount') ? 'paid_amount' : null;
            $sumBalanceCol = $this->hasColumn('invoices', 'balance_amount') ? 'balance_amount' : null;
            $statusCol = $this->hasColumn('invoices', 'invoice_status')
                ? 'invoice_status'
                : ($this->hasColumn('invoices', 'status') ? 'status' : null);

            $totalBilled = round((float)Invoice::query()->where('customer_id', $customer->id)->sum($sumAmountCol), 2);
            $totalPaid = $sumPaidCol
                ? round((float)Invoice::query()->where('customer_id', $customer->id)->sum($sumPaidCol), 2)
                : round((float)Payment::query()->where('customer_id', $customer->id)->sum('amount'), 2);
            $totalDue = $sumBalanceCol
                ? round((float)Invoice::query()->where('customer_id', $customer->id)->sum($sumBalanceCol), 2)
                : round(max(0, $totalBilled - $totalPaid), 2);

            $openInvoicesQ = Invoice::query()->where('customer_id', $customer->id);
            if ($statusCol === 'invoice_status') {
                $openInvoicesQ->whereNotIn('invoice_status', ['paid', 'cancelled']);
            } elseif ($statusCol === 'status') {
                $openInvoicesQ->where('status', '!=', 'paid');
            }

            $balances = [
                'total_billed' => $totalBilled,
                'total_paid' => $totalPaid,
                'total_due' => $totalDue,
                'open_invoices' => (int)$openInvoicesQ->count(),
            ];
        }

        $plans = $this->availablePlans();
        $selectedBillingPlanId = $this->hasColumn('hotspot_user_billings', 'package_id')
            ? $billing?->package_id
            : $customer?->package_id;
        $billingMode = 'metered';
        if ($selectedBillingPlanId) {
            $selectedPlan = collect($plans)->first(function ($plan) use ($selectedBillingPlanId) {
                return (int)($plan['id'] ?? 0) === (int)$selectedBillingPlanId;
            });
            $category = strtolower((string)($selectedPlan['category'] ?? 'metered'));
            if ($category === 'hotspot') {
                $billingMode = 'hotspot';
            }
        }

        $packageInfo = $this->buildPackageInfo(
            username: $username,
            hotspotUser: $rawUser,
            activeSession: $activeSession,
            customer: $customer,
            billing: $billing
        );
        $detailMode = $this->resolveDetailMode($user, $packageInfo);
        $hotspotInsights = $detailMode === 'hotspot'
            ? $this->buildHotspotInsights(
                username: $username,
                hotspotUser: $rawUser,
                activeSession: $activeSession,
                customer: $customer,
                plans: $plans,
                packageInfo: $packageInfo
            )
            : [
                'subscriptions' => [],
                'sessions' => [],
                'transactions' => [],
                'recent_payments' => [],
                'summary' => [
                    'last_seen' => '-',
                    'last_seen_at' => null,
                    'total_online_seconds' => 0,
                    'total_online' => '-',
                ],
                'extension' => [
                    'default_package_id' => $packageInfo['package_id'] ?? null,
                    'eligible_package_ids' => [],
                    'previous_package_ids' => [],
                    'force_allowed' => true,
                ],
                'extension_options' => [],
                'force_extension_options' => [],
            ];
        $recentPaymentsPayload = $payments;
        if ($detailMode === 'hotspot' && !empty($hotspotInsights['recent_payments'])) {
            $recentPaymentsPayload = collect($hotspotInsights['recent_payments']);
        }

        return response()->json([
            'user' => $user,
            'detail_mode' => $detailMode,
            'active_session' => $activeSession,
            'usage' => $usage,
            'package_info' => $packageInfo,
            'hotspot' => $hotspotInsights,
            'general' => [
                'customer' => [
                    'id' => $customer?->id,
                    'username' => $customer?->username ?: $username,
                    'name' => $customer?->name ?: $username,
                    'phone' => $customer?->phone,
                    'email' => $customer?->email,
                    'address' => $customer?->address,
                    'status' => $customer?->status ?: 'active',
                    'package_id' => $customer?->package_id,
                ],
                'balances' => $balances,
                'recent_payments' => $recentPaymentsPayload,
            ],
            'billing' => [
                'rate_per_mb' => (float)($billing->rate_per_gb ?? 0),
                'rate_per_gb' => (float)($billing->rate_per_gb ?? 0),
                'currency' => $billing->currency ?? 'KES',
                'package_id' => $selectedBillingPlanId,
                'billing_mode' => $billingMode,
                'notify_customer' => $this->hasColumn('hotspot_user_billings', 'notify_customer')
                    ? (bool)($billing->notify_customer ?? false)
                    : false,
            ],
            'invoices' => $invoices,
            'plans' => $plans,
            'public_account_url' => $this->buildCustomerStatusUrl($username),
        ]);
    }

    public function saveCustomerProfile(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|max:255',
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'package_id' => 'nullable|exists:packages,id',
            'notify_customer' => 'nullable|boolean',
        ]);

        $username = trim((string)$data['username']);

        $customer = Customer::firstOrCreate(
            ['username' => $username],
            ['name' => $data['name'] ?: $username, 'status' => 'active']
        );

        $customer->fill([
            'name' => $data['name'] ?: ($customer->name ?: $username),
            'phone' => $data['phone'] ?? $customer->phone,
            'email' => $data['email'] ?? $customer->email,
            'address' => $data['address'] ?? $customer->address,
            'status' => $data['status'] ?? ($customer->status ?: 'active'),
        ]);

        if ($this->hasColumn('customers', 'package_id')) {
            $customer->package_id = $data['package_id'] ?? $customer->package_id;
        }

        $customer->save();

        if ($request->has('notify_customer')) {
            $billingValues = [
                'customer_id' => $customer->id,
                'rate_per_gb' => (float)(HotspotUserBilling::query()->where('username', $username)->value('rate_per_gb') ?? 0),
                'currency' => HotspotUserBilling::query()->where('username', $username)->value('currency') ?? 'KES',
            ];

            if ($this->hasColumn('hotspot_user_billings', 'package_id')) {
                $billingValues['package_id'] = $data['package_id'] ?? ($this->hasColumn('customers', 'package_id') ? $customer->package_id : null);
            }

            if ($this->hasColumn('hotspot_user_billings', 'notify_customer')) {
                $billingValues['notify_customer'] = (bool)$data['notify_customer'];
            }

            HotspotUserBilling::updateOrCreate(
                ['username' => $username],
                $billingValues
            );
        }

        $packageId = $this->hasColumn('customers', 'package_id') ? ($customer->package_id ?? null) : ($data['package_id'] ?? null);
        if ($packageId) {
            $this->applyPackageProfileToHotspotUser($username, (int)$packageId);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Customer profile saved.',
            'customer' => $customer,
        ]);
    }

    public function updateUser(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|max:255',
            'profile' => 'nullable|string|max:255',
            'limit_uptime' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:255',
        ]);

        $username = trim((string) $data['username']);

        [$api, $error] = $this->connectApi();
        if ($error) {
            return response()->json(['status' => 'fail', 'message' => $error], 500);
        }

        $user = $this->findHotspotUserByUsername($api, $username);
        if (!$user || empty($user['.id'])) {
            return response()->json(['status' => 'fail', 'message' => 'Hotspot user not found.'], 404);
        }

        $payload = ['.id' => $user['.id']];

        if ($request->has('profile')) {
            $payload['profile'] = (string)($data['profile'] ?? '');
        }
        if ($request->has('limit_uptime')) {
            $payload['limit-uptime'] = (string)($data['limit_uptime'] ?? '');
        }
        if ($request->has('comment')) {
            $payload['comment'] = (string)($data['comment'] ?? '');
        }

        if (count($payload) <= 1) {
            return response()->json(['status' => 'fail', 'message' => 'No changes provided.'], 422);
        }

        $api->comm('/ip/hotspot/user/set', $payload);

        return response()->json([
            'status' => 'success',
            'message' => 'User updated successfully.',
        ]);
    }

    public function createUser(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:4|max:255',
            'profile' => 'nullable|string|max:255',
            'limit_uptime' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'package_id' => 'required|exists:packages,id',
            'notify_customer' => 'nullable|boolean',
        ]);

        $username = trim((string)$data['username']);

        [$api, $error] = $this->connectApi();
        if ($error) {
            return response()->json(['status' => 'fail', 'message' => $error], 500);
        }

        if ($this->findHotspotUserByUsername($api, $username)) {
            return response()->json(['status' => 'fail', 'message' => 'Hotspot user already exists.'], 422);
        }

        $package = Package::find((int)$data['package_id']);
        if (!$package) {
            return response()->json(['status' => 'fail', 'message' => 'Select a valid metered plan.'], 422);
        }

        $packageCategory = strtolower(trim((string)($package->category ?? 'metered')));
        if ($packageCategory === 'hotspot') {
            return response()->json([
                'status' => 'fail',
                'message' => 'This form creates metered users only. Select a metered plan.',
            ], 422);
        }

        $profile = trim((string)($data['profile'] ?? ''));
        if ($profile === '' && $package) {
            $profile = trim((string)(
                $package->mk_profile
                ?? $package->mikrotik_profile
                ?? ''
            ));
        }
        if ($profile === '') {
            $profile = 'default';
        }

        $payload = [
            'name' => $username,
            'password' => (string)$data['password'],
            'profile' => $profile,
        ];
        if (($data['status'] ?? 'active') === 'inactive') {
            $payload['disabled'] = 'yes';
        }
        if (!empty($data['limit_uptime'])) {
            $payload['limit-uptime'] = (string)$data['limit_uptime'];
        }
        if (!empty($data['comment'])) {
            $payload['comment'] = (string)$data['comment'];
        }

        try {
            $api->comm('/ip/hotspot/user/add', $payload);
        } catch (\Throwable $e) {
            Log::error('Hotspot user create failed', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['status' => 'fail', 'message' => 'Failed to create hotspot user.'], 500);
        }

        $customer = null;
        $billing = null;

        try {
            DB::beginTransaction();

            $customer = Customer::firstOrCreate(
                ['username' => $username],
                ['name' => $data['name'] ?: $username, 'status' => $data['status'] ?? 'active']
            );

            $customer->fill([
                'name' => $data['name'] ?: ($customer->name ?: $username),
                'phone' => $data['phone'] ?? $customer->phone,
                'email' => $data['email'] ?? $customer->email,
                'address' => $data['address'] ?? $customer->address,
                'status' => $data['status'] ?? ($customer->status ?: 'active'),
            ]);
            if ($this->hasColumn('customers', 'package_id')) {
                $customer->package_id = $data['package_id'] ?? $customer->package_id;
            }
            $customer->save();

            $billingValues = [
                'customer_id' => $customer->id,
                'rate_per_gb' => (float)($package->price ?? 0),
                'currency' => strtoupper(trim((string)(HotspotUserBilling::query()->where('username', $username)->value('currency') ?? 'KES'))) ?: 'KES',
            ];
            if ($this->hasColumn('hotspot_user_billings', 'package_id')) {
                $billingValues['package_id'] = $package->id
                    ?? ($this->hasColumn('customers', 'package_id') ? $customer->package_id : null);
            }
            if ($this->hasColumn('hotspot_user_billings', 'notify_customer')) {
                $billingValues['notify_customer'] = (bool)($data['notify_customer'] ?? false);
            }
            $billing = HotspotUserBilling::updateOrCreate(['username' => $username], $billingValues);

            if ($this->hasTable('subscriptions')) {
                $subscriptionMatch = [];
                if ($this->hasColumn('subscriptions', 'customer_id')) {
                    $subscriptionMatch['customer_id'] = $customer->id;
                }
                if ($this->hasColumn('subscriptions', 'type')) {
                    $subscriptionMatch['type'] = 'metered';
                }
                if ($this->hasColumn('subscriptions', 'username')) {
                    $subscriptionMatch['username'] = $username;
                }
                if (empty($subscriptionMatch)) {
                    $subscriptionMatch = ['id' => 0];
                }

                $subscriptionValues = [];
                if ($this->hasColumn('subscriptions', 'customer_id')) {
                    $subscriptionValues['customer_id'] = $customer->id;
                }
                if ($this->hasColumn('subscriptions', 'package_id')) {
                    $subscriptionValues['package_id'] = $package->id;
                }
                if ($this->hasColumn('subscriptions', 'type')) {
                    $subscriptionValues['type'] = 'metered';
                }
                if ($this->hasColumn('subscriptions', 'username')) {
                    $subscriptionValues['username'] = $username;
                }
                if ($this->hasColumn('subscriptions', 'password')) {
                    $subscriptionValues['password'] = encrypt((string)$data['password']);
                }
                if ($this->hasColumn('subscriptions', 'mk_profile')) {
                    $subscriptionValues['mk_profile'] = $profile;
                }
                if ($this->hasColumn('subscriptions', 'starts_at')) {
                    $subscriptionValues['starts_at'] = now();
                }
                if ($this->hasColumn('subscriptions', 'expires_at')) {
                    $subscriptionValues['expires_at'] = null;
                }
                if ($this->hasColumn('subscriptions', 'status')) {
                    $subscriptionValues['status'] = ($data['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
                }
                if ($this->hasColumn('subscriptions', 'price_paid')) {
                    $subscriptionValues['price_paid'] = (float)($package?->price ?? 0);
                }
                if ($this->hasColumn('subscriptions', 'meta')) {
                    $subscriptionValues['meta'] = [
                        'source' => 'customers.create_user',
                        'account_mode' => 'metered',
                        'notify_customer' => (bool)($data['notify_customer'] ?? false),
                        'phone' => $data['phone'] ?? null,
                        'email' => $data['email'] ?? null,
                        'address' => $data['address'] ?? null,
                        'status' => $data['status'] ?? 'active',
                        'profile' => $profile,
                        'limit_uptime' => $data['limit_uptime'] ?? null,
                        'comment' => $data['comment'] ?? null,
                    ];
                }

                if (!empty($subscriptionValues)) {
                    Subscription::updateOrCreate($subscriptionMatch, $subscriptionValues);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            try {
                $existing = $this->findHotspotUserByUsername($api, $username);
                if ($existing && !empty($existing['.id'])) {
                    $api->comm('/ip/hotspot/user/remove', ['.id' => $existing['.id']]);
                }
            } catch (\Throwable $rollbackError) {
                Log::warning('Hotspot rollback failed after DB failure', [
                    'username' => $username,
                    'error' => $rollbackError->getMessage(),
                ]);
            }

            Log::error('Create user DB sync failed', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'fail',
                'message' => 'Failed to save user details in database. Please retry.',
            ], 500);
        }

        if (!empty($data['package_id'])) {
            $this->applyPackageProfileToHotspotUser($username, (int)$data['package_id']);
        }

        $publicAccountUrl = $this->buildCustomerStatusUrl($username);
        $notification = null;
        if ((bool)($data['notify_customer'] ?? false)) {
            $notification = $this->sendNewUserSms(
                phone: (string)($customer->phone ?? ($data['phone'] ?? '')),
                username: $username,
                password: (string)$data['password'],
                planName: (string)($package?->name ?? 'N/A'),
                profile: $profile,
                ratePerMb: (float)($package?->price ?? $billing?->rate_per_gb ?? 0),
                billingMode: 'metered',
                statusUrl: $publicAccountUrl
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Metered user created successfully.',
            'user' => [
                'username' => $username,
                'profile' => $profile,
            ],
            'customer' => $customer,
            'package' => $package?->only(['id', 'name']),
            'public_account_url' => $publicAccountUrl,
            'notification' => $notification,
        ]);
    }

    public function disableUser(Request $request)
    {
        $username = trim((string) $request->input('username', ''));
        $mac = trim((string) $request->input('mac', ''));

        if ($username === '' && $mac === '') {
            return response()->json(['status' => 'fail', 'message' => 'Username or MAC is required.'], 422);
        }

        [$api, $error] = $this->connectApi();
        if ($error) {
            return response()->json(['status' => 'fail', 'message' => $error], 500);
        }

        $disabled = false;

        if ($username !== '') {
            $user = $this->findHotspotUserByUsername($api, $username);
            if ($user && !empty($user['.id'])) {
                $api->comm('/ip/hotspot/user/set', [
                    '.id' => $user['.id'],
                    'disabled' => 'yes',
                ]);
                $disabled = true;
            }
        }

        $actives = $api->comm('/ip/hotspot/active/print') ?? [];
        $removedCount = 0;

        foreach ($actives as $session) {
            $sessionUser = (string)($session['user'] ?? $session['username'] ?? '');
            $sessionMac = (string)($session['mac-address'] ?? '');

            $matchesUser = ($username !== '' && $sessionUser === $username);
            $matchesMac = ($mac !== '' && $sessionMac === $mac);

            if (($matchesUser || $matchesMac) && !empty($session['.id'])) {
                $api->comm('/ip/hotspot/active/remove', ['.id' => $session['.id']]);
                $removedCount++;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'User disabled and sessions disconnected.',
            'disabled' => $disabled,
            'disconnected_sessions' => $removedCount,
        ]);
    }

    public function enableUser(Request $request)
    {
        $username = trim((string)$request->input('username', ''));
        $mac = trim((string)$request->input('mac', ''));

        if ($username === '' && $mac === '') {
            return response()->json(['status' => 'fail', 'message' => 'Username or MAC is required.'], 422);
        }

        [$api, $error] = $this->connectApi();
        if ($error) {
            return response()->json(['status' => 'fail', 'message' => $error], 500);
        }

        $users = $api->comm('/ip/hotspot/user/print') ?? [];
        $enabledCount = 0;

        foreach ($users as $user) {
            $rowUsername = trim((string)($user['name'] ?? $user['username'] ?? ''));
            $rowMac = trim((string)($user['mac-address'] ?? $user['mac'] ?? ''));

            $matchesUser = ($username !== '' && $rowUsername === $username);
            $matchesMac = ($mac !== '' && $rowMac === $mac);
            if (!($matchesUser || $matchesMac) || empty($user['.id'])) {
                continue;
            }

            $api->comm('/ip/hotspot/user/set', [
                '.id' => $user['.id'],
                'disabled' => 'no',
            ]);
            $enabledCount++;
        }

        if ($enabledCount === 0) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'No matching hotspot user found to enable.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'User enabled successfully.',
            'enabled_count' => $enabledCount,
        ]);
    }

    public function extendPackage(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|max:255',
            'package_id' => 'nullable|exists:packages,id',
            'extend_minutes' => 'nullable|integer|min:1|max:525600',
            'force' => 'nullable|boolean',
        ]);

        $username = trim((string)$data['username']);
        $forceExtend = (bool)($data['force'] ?? false);

        $customer = Customer::query()
            ->where('username', $username)
            ->orderByDesc('id')
            ->first();

        if (!$customer) {
            $customer = Customer::create([
                'username' => $username,
                'name' => $username,
                'status' => 'active',
            ]);
        }

        $billing = HotspotUserBilling::query()->where('username', $username)->first();
        $packageId = (int)($data['package_id'] ?? 0);
        if ($packageId <= 0) {
            if ($this->hasColumn('hotspot_user_billings', 'package_id')) {
                $packageId = (int)($billing?->package_id ?? 0);
            }
            if ($packageId <= 0 && $this->hasColumn('customers', 'package_id')) {
                $packageId = (int)($customer->package_id ?? 0);
            }
        }

        $package = $packageId > 0 ? Package::find($packageId) : null;
        if (!$package) {
            return response()->json([
                'status' => 'fail',
                'message' => 'No package assigned. Select a package to extend.',
            ], 422);
        }

        $packageDuration = $this->packageDurationMinutes($package);
        $extendMinutes = (int)($data['extend_minutes'] ?? 0);
        if ($extendMinutes <= 0) {
            $extendMinutes = $packageDuration;
        }
        if ($extendMinutes <= 0) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Selected package has no duration. Enter extension minutes manually.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            if ($this->hasColumn('customers', 'package_id')) {
                $customer->package_id = $package->id;
                $customer->save();
            }

            if ($this->hasTable('hotspot_user_billings')) {
                $billingValues = [
                    'customer_id' => $customer->id,
                    'rate_per_gb' => (float)($billing?->rate_per_gb ?? 0),
                    'currency' => strtoupper(trim((string)($billing?->currency ?? 'KES'))) ?: 'KES',
                ];
                if ($this->hasColumn('hotspot_user_billings', 'package_id')) {
                    $billingValues['package_id'] = $package->id;
                }
                if ($this->hasColumn('hotspot_user_billings', 'notify_customer')) {
                    $billingValues['notify_customer'] = (bool)($billing?->notify_customer ?? false);
                }

                $billing = HotspotUserBilling::updateOrCreate(
                    ['username' => $username],
                    $billingValues
                );
            }

            $subscription = $this->resolveUserSubscription($username, $customer);
            $modeType = strtolower((string)($package->category ?? 'hotspot')) === 'metered'
                ? 'metered'
                : 'hotspot';

            if ($subscription) {
                $base = now();
                if ($this->hasColumn('subscriptions', 'expires_at') && $subscription->expires_at) {
                    $candidate = Carbon::parse($subscription->expires_at);
                    if ($candidate->isFuture()) {
                        $base = $candidate;
                    }
                }
                $newExpiry = $base->copy()->addMinutes($extendMinutes);

                if ($this->hasColumn('subscriptions', 'package_id')) {
                    $subscription->package_id = $package->id;
                }
                if ($this->hasColumn('subscriptions', 'starts_at') && !$subscription->starts_at) {
                    $subscription->starts_at = now();
                }
                if ($this->hasColumn('subscriptions', 'expires_at')) {
                    $subscription->expires_at = $newExpiry;
                }
                if ($this->hasColumn('subscriptions', 'status')) {
                    $subscription->status = 'active';
                }
                if ($this->hasColumn('subscriptions', 'type')) {
                    $subscription->type = $modeType;
                }
                if ($this->hasColumn('subscriptions', 'mk_profile')) {
                    $subscription->mk_profile = (string)($package->mk_profile ?? $package->mikrotik_profile ?? $subscription->mk_profile);
                }
                if ($this->hasColumn('subscriptions', 'meta')) {
                    $meta = is_array($subscription->meta) ? $subscription->meta : [];
                    $meta['extended_at'] = now()->toDateTimeString();
                    $meta['extended_minutes'] = $extendMinutes;
                    $meta['source'] = 'customers.extend_package';
                    $meta['force_extended'] = $forceExtend;
                    $subscription->meta = $meta;
                }
                $subscription->save();
            } elseif ($this->hasTable('subscriptions')) {
                $payload = [];
                if ($this->hasColumn('subscriptions', 'customer_id')) {
                    $payload['customer_id'] = $customer->id;
                }
                if ($this->hasColumn('subscriptions', 'package_id')) {
                    $payload['package_id'] = $package->id;
                }
                if ($this->hasColumn('subscriptions', 'type')) {
                    $payload['type'] = $modeType;
                }
                if ($this->hasColumn('subscriptions', 'username')) {
                    $payload['username'] = $username;
                }
                if ($this->hasColumn('subscriptions', 'mk_profile')) {
                    $payload['mk_profile'] = (string)($package->mk_profile ?? $package->mikrotik_profile ?? 'default');
                }
                if ($this->hasColumn('subscriptions', 'starts_at')) {
                    $payload['starts_at'] = now();
                }
                if ($this->hasColumn('subscriptions', 'expires_at')) {
                    $payload['expires_at'] = now()->addMinutes($extendMinutes);
                }
                if ($this->hasColumn('subscriptions', 'status')) {
                    $payload['status'] = 'active';
                }
                if ($this->hasColumn('subscriptions', 'price_paid')) {
                    $payload['price_paid'] = (float)($package->price ?? 0);
                }
                if ($this->hasColumn('subscriptions', 'meta')) {
                    $payload['meta'] = [
                        'extended_at' => now()->toDateTimeString(),
                        'extended_minutes' => $extendMinutes,
                        'source' => 'customers.extend_package',
                        'force_extended' => $forceExtend,
                    ];
                }

                if (!empty($payload)) {
                    Subscription::create($payload);
                }
            }

            if (
                $this->hasTable('connections')
                && $this->hasColumn('connections', 'username')
                && $this->hasColumn('connections', 'expires_at')
            ) {
                $connectionQuery = Connection::query()->where('username', $username);
                if ($this->hasColumn('connections', 'status')) {
                    $connectionQuery->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END");
                }
                $connection = $connectionQuery->orderByDesc('id')->first();

                if ($connection) {
                    $base = $connection->expires_at && Carbon::parse($connection->expires_at)->isFuture()
                        ? Carbon::parse($connection->expires_at)
                        : now();

                    $connection->expires_at = $base->copy()->addMinutes($extendMinutes);
                    if ($this->hasColumn('connections', 'package_id')) {
                        $connection->package_id = $package->id;
                    }
                    if ($this->hasColumn('connections', 'status')) {
                        $connection->status = 'active';
                    }
                    $connection->save();
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('extendPackage failed', [
                'username' => $username,
                'package_id' => $package->id,
                'extend_minutes' => $extendMinutes,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'fail',
                'message' => 'Could not extend package right now.',
            ], 500);
        }

        $this->applyPackageProfileToHotspotUser($username, (int)$package->id);

        $freshBilling = HotspotUserBilling::query()->where('username', $username)->first();
        $freshPackageInfo = $this->buildPackageInfo(
            username: $username,
            hotspotUser: null,
            activeSession: null,
            customer: $customer,
            billing: $freshBilling
        );

        return response()->json([
            'status' => 'success',
            'message' => $forceExtend
                ? sprintf('Package force-extended by %d minutes.', $extendMinutes)
                : sprintf('Package extended by %d minutes.', $extendMinutes),
            'package_info' => $freshPackageInfo,
            'force' => $forceExtend,
        ]);
    }

    public function expirePackage(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|max:255',
            'subscription_id' => 'nullable|integer|min:1',
            'connection_id' => 'nullable|integer|min:1',
            'disconnect' => 'nullable|boolean',
        ]);

        $username = trim((string)$data['username']);
        $subscriptionId = (int)($data['subscription_id'] ?? 0);
        $connectionId = (int)($data['connection_id'] ?? 0);
        $disconnectRouter = (bool)($data['disconnect'] ?? true);

        $customer = Customer::query()
            ->where('username', $username)
            ->orderByDesc('id')
            ->first();

        $expiredAt = now();
        $updatedSubscription = false;
        $updatedConnection = false;

        DB::beginTransaction();
        try {
            $subscription = null;
            if ($this->hasTable('subscriptions')) {
                if ($subscriptionId > 0) {
                    $subscriptionQuery = Subscription::query()->where('id', $subscriptionId);
                    if ($this->hasColumn('subscriptions', 'username')) {
                        $subscriptionQuery->where('username', $username);
                    } elseif ($customer?->id && $this->hasColumn('subscriptions', 'customer_id')) {
                        $subscriptionQuery->where('customer_id', $customer->id);
                    }
                    $subscription = $subscriptionQuery->first();
                }

                if (!$subscription) {
                    $subscription = $this->resolveUserSubscription($username, $customer);
                }

                if ($subscription) {
                    if ($this->hasColumn('subscriptions', 'expires_at')) {
                        $subscription->expires_at = $expiredAt;
                    }
                    if ($this->hasColumn('subscriptions', 'status')) {
                        $subscription->status = 'expired';
                    }
                    if ($this->hasColumn('subscriptions', 'meta')) {
                        $meta = is_array($subscription->meta) ? $subscription->meta : [];
                        $meta['expired_at'] = $expiredAt->toDateTimeString();
                        $meta['expired_by'] = 'customers.expire_package';
                        $subscription->meta = $meta;
                    }
                    $subscription->save();
                    $updatedSubscription = true;
                }
            }

            $connection = null;
            if (
                $this->hasTable('connections')
                && $this->hasColumn('connections', 'username')
            ) {
                if ($connectionId > 0 && $this->hasColumn('connections', 'id')) {
                    $connection = Connection::query()
                        ->where('id', $connectionId)
                        ->where('username', $username)
                        ->first();
                }

                if (!$connection) {
                    $connection = $this->resolveLatestConnection($username);
                }

                if ($connection) {
                    if ($this->hasColumn('connections', 'expires_at')) {
                        $connection->expires_at = $expiredAt;
                    }
                    if ($this->hasColumn('connections', 'status')) {
                        $connection->status = 'expired';
                    }
                    $connection->save();
                    $updatedConnection = true;
                }
            }

            if (!$updatedSubscription && !$updatedConnection) {
                DB::rollBack();

                return response()->json([
                    'status' => 'fail',
                    'message' => 'No active subscription record found to expire.',
                ], 404);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('expirePackage failed', [
                'username' => $username,
                'subscription_id' => $subscriptionId,
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'fail',
                'message' => 'Could not expire subscription right now.',
            ], 500);
        }

        $removedCount = 0;
        $routerDisconnectError = null;

        if ($disconnectRouter) {
            [$api, $error] = $this->connectApi();
            if ($error) {
                $routerDisconnectError = $error;
            } else {
                $actives = $api->comm('/ip/hotspot/active/print') ?? [];
                foreach ($actives as $session) {
                    $sessionUser = (string)($session['user'] ?? $session['username'] ?? '');
                    if ($sessionUser === $username && !empty($session['.id'])) {
                        $api->comm('/ip/hotspot/active/remove', [
                            '.id' => $session['.id'],
                        ]);
                        $removedCount++;
                    }
                }
            }
        }

        $freshBilling = HotspotUserBilling::query()->where('username', $username)->first();
        $freshPackageInfo = $this->buildPackageInfo(
            username: $username,
            hotspotUser: null,
            activeSession: null,
            customer: $customer,
            billing: $freshBilling
        );

        $message = $removedCount > 0
            ? 'Subscription expired and active session disconnected.'
            : 'Subscription expired.';

        if ($routerDisconnectError) {
            $message .= ' Router disconnect is pending because MikroTik is unavailable.';
        }

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'package_info' => $freshPackageInfo,
            'disconnected_sessions' => $removedCount,
            'router_disconnect_error' => $routerDisconnectError,
        ]);
    }

    public function disconnect(Request $request)
    {
        $mac = trim((string) $request->input('mac', ''));
        $username = trim((string) $request->input('username', ''));

        if ($mac === '' && $username === '') {
            return response()->json(['status' => 'fail', 'message' => 'MAC or username is required.'], 422);
        }

        [$api, $error] = $this->connectApi();
        if ($error) {
            return response()->json(['status' => 'fail', 'message' => $error], 500);
        }

        $actives = $api->comm('/ip/hotspot/active/print') ?? [];
        $removedCount = 0;

        foreach ($actives as $session) {
            $sessionUser = (string)($session['user'] ?? $session['username'] ?? '');
            $sessionMac = (string)($session['mac-address'] ?? '');

            $matchesUser = ($username !== '' && $sessionUser === $username);
            $matchesMac = ($mac !== '' && $sessionMac === $mac);

            if (($matchesUser || $matchesMac) && !empty($session['.id'])) {
                $api->comm('/ip/hotspot/active/remove', [
                    '.id' => $session['.id'],
                ]);

                $removedCount++;
            }
        }

        if ($removedCount > 0) {
            return response()->json([
                'status' => 'success',
                'message' => 'Session(s) disconnected.',
                'disconnected_sessions' => $removedCount,
            ]);
        }

        return response()->json([
            'status' => 'not_found',
            'message' => 'No matching active session found.',
            'disconnected_sessions' => 0,
        ]);
    }

    public function blockHost(Request $request)
    {
        $data = $request->validate([
            'mac' => 'required|string|max:255',
        ]);

        $mac = trim((string) $data['mac']);

        [$api, $error] = $this->connectApi();
        if ($error) {
            return response()->json(['status' => 'fail', 'message' => $error], 500);
        }

        $bindings = $api->comm('/ip/hotspot/ip-binding/print') ?? [];

        $alreadyBlocked = collect($bindings)->contains(function (array $binding) use ($mac) {
            return (($binding['mac-address'] ?? '') === $mac)
                && (strtolower((string)($binding['type'] ?? '')) === 'blocked');
        });

        if (!$alreadyBlocked) {
            $api->comm('/ip/hotspot/ip-binding/add', [
                'mac-address' => $mac,
                'type' => 'blocked',
                'comment' => 'Blocked from dashboard',
            ]);
        }

        $actives = $api->comm('/ip/hotspot/active/print') ?? [];
        $removedCount = 0;

        foreach ($actives as $session) {
            if (($session['mac-address'] ?? '') === $mac && !empty($session['.id'])) {
                $api->comm('/ip/hotspot/active/remove', ['.id' => $session['.id']]);
                $removedCount++;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => $alreadyBlocked ? 'Host already blocked.' : 'Host blocked successfully.',
            'disconnected_sessions' => $removedCount,
        ]);
    }

    public function deleteCookie(Request $request)
    {
        $username = trim((string) $request->input('username', ''));
        $mac = trim((string) $request->input('mac', ''));

        if ($username === '' && $mac === '') {
            return response()->json(['status' => 'fail', 'message' => 'Username or MAC is required.'], 422);
        }

        [$api, $error] = $this->connectApi();
        if ($error) {
            return response()->json(['status' => 'fail', 'message' => $error], 500);
        }

        $cookies = $api->comm('/ip/hotspot/cookie/print') ?? [];
        $removedCount = 0;

        foreach ($cookies as $cookie) {
            $cookieUser = (string)($cookie['user'] ?? '');
            $cookieMac = (string)($cookie['mac-address'] ?? '');

            $matchesUser = ($username !== '' && $cookieUser === $username);
            $matchesMac = ($mac !== '' && $cookieMac === $mac);

            if (($matchesUser || $matchesMac) && !empty($cookie['.id'])) {
                $api->comm('/ip/hotspot/cookie/remove', ['.id' => $cookie['.id']]);
                $removedCount++;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => $removedCount > 0 ? 'Cookie(s) deleted.' : 'No matching cookies found.',
            'removed' => $removedCount,
        ]);
    }

    public function monitorTraffic(Request $request)
    {
        $username = trim((string)$request->input('username', ''));
        $mac = trim((string)$request->input('mac', ''));
        $connectionId = max(0, (int)$request->input('connection_id', 0));

        if ($connectionId > 0) {
            if (!$this->hasTable('connections')) {
                return response()->json(['error' => 'Session history is not available yet.'], 404);
            }

            $connection = Connection::query()->find($connectionId);
            if (!$connection) {
                return response()->json(['error' => 'Saved session was not found.'], 404);
            }

            return response()->json($this->buildStoredTrafficPayload($connection));
        }

        if ($username === '' && $mac === '') {
            return response()->json(['error' => 'Username or MAC is required'], 422);
        }

        [$api, $error] = $this->connectApi();
        if ($error) {
            return response()->json(['error' => $error], 500);
        }

        $actives = $api->comm('/ip/hotspot/active/print') ?? [];
        $session = collect($actives)->first(function (array $row) use ($username, $mac) {
            $rowUser = trim((string)($row['user'] ?? $row['username'] ?? ''));
            $rowMac = trim((string)($row['mac-address'] ?? ''));
            return ($username !== '' && $rowUser === $username)
                || ($mac !== '' && $rowMac === $mac);
        });

        if ($username === '' && $session) {
            $username = trim((string)($session['user'] ?? $session['username'] ?? ''));
        }

        $hotspotUser = null;
        if ($username !== '') {
            $hotspotUser = $this->findHotspotUserByUsername($api, $username);
        }

        if (!$hotspotUser && !$session) {
            return response()->json(['error' => 'No matching hotspot user/session found.'], 404);
        }

        $sessionData = is_array($session) ? $session : [];
        $interface = $this->resolveHotspotSessionInterface($api, $sessionData);
        $tx = 0;
        $rx = 0;
        $liveAvailable = false;

        if ($interface !== '') {
            try {
                $monitorRes = $api->comm('/interface/monitor-traffic', [
                    'interface' => $interface,
                    'once' => '',
                ]) ?? [];
                $monitor = $monitorRes[0] ?? [];
                $tx = (int)($monitor['tx-bits-per-second'] ?? 0);
                $rx = (int)($monitor['rx-bits-per-second'] ?? 0);
                $liveAvailable = array_key_exists('tx-bits-per-second', $monitor)
                    || array_key_exists('rx-bits-per-second', $monitor);
            } catch (\Throwable $e) {
                Log::warning('monitorTraffic: interface monitor failed', [
                    'username' => $username,
                    'interface' => $interface,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $sessionBytesIn = (int)($sessionData['bytes-in'] ?? 0);
        $sessionBytesOut = (int)($sessionData['bytes-out'] ?? 0);
        $userBytesIn = (int)($hotspotUser['bytes-in'] ?? 0);
        $userBytesOut = (int)($hotspotUser['bytes-out'] ?? 0);
        $isDisabled = strtolower((string)($hotspotUser['disabled'] ?? 'false')) === 'true';
        $status = $session ? 'online' : ($isDisabled ? 'disabled' : 'offline');
        $trackedConnection = $this->matchActiveConnectionForIdentity(
            $username,
            trim((string)($sessionData['mac-address'] ?? $hotspotUser['mac-address'] ?? $mac))
        );

        if ($session && $trackedConnection) {
            $this->updateTrackedConnectionFromSession($trackedConnection, $sessionData, $hotspotUser, 'traffic_poll');
            $trackedConnection = $trackedConnection->fresh() ?: $trackedConnection;
        }

        return response()->json([
            'ok' => true,
            'username' => $username !== '' ? $username : ($sessionData['user'] ?? $sessionData['username'] ?? null),
            'status' => $status,
            'interface' => $interface !== '' ? $interface : null,
            'ip' => $sessionData['address'] ?? $sessionData['host-ip'] ?? null,
            'mac' => $sessionData['mac-address'] ?? $hotspotUser['mac-address'] ?? ($mac !== '' ? $mac : null),
            'uptime' => $sessionData['uptime'] ?? $hotspotUser['uptime'] ?? null,
            'tx' => $tx,
            'rx' => $rx,
            'live_available' => $liveAvailable,
            'session_bytes_in' => $sessionBytesIn,
            'session_bytes_out' => $sessionBytesOut,
            'session_total_bytes' => $sessionBytesIn + $sessionBytesOut,
            'user_bytes_in' => $userBytesIn,
            'user_bytes_out' => $userBytesOut,
            'user_total_bytes' => $userBytesIn + $userBytesOut,
            'connection_id' => (int)($trackedConnection?->id ?? 0) ?: null,
        ]);
    }

    public function saveBillingRate(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|max:255',
            'rate_per_mb' => 'nullable|numeric|min:0',
            'rate_per_gb' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:8',
            'package_id' => 'nullable|exists:packages,id',
            'notify_customer' => 'nullable|boolean',
        ]);

        $username = trim((string)$data['username']);
        $currency = strtoupper(trim((string)($data['currency'] ?? 'KES')));
        $package = null;
        if (!empty($data['package_id'])) {
            $package = Package::find((int)$data['package_id']);
        }
        $isHotspotPackage = strtolower((string)($package?->category ?? 'metered')) === 'hotspot';

        $ratePerMb = array_key_exists('rate_per_mb', $data) && $data['rate_per_mb'] !== null
            ? (float)$data['rate_per_mb']
            : (float)($data['rate_per_gb'] ?? -1);
        if ($ratePerMb < 0 && $isHotspotPackage) {
            $ratePerMb = (float)($package?->price ?? -1);
        }
        if ($ratePerMb < 0) {
            return response()->json([
                'status' => 'fail',
                'message' => $isHotspotPackage
                    ? 'Package price is required for hotspot billing.'
                    : 'Rate per MB is required.',
            ], 422);
        }

        $customer = Customer::firstOrCreate(
            ['username' => $username],
            ['name' => $username, 'status' => 'active']
        );

        if ($this->hasColumn('customers', 'package_id') && !empty($data['package_id'])) {
            $customer->package_id = (int)$data['package_id'];
            $customer->save();
        }

        $billingValues = [
            'customer_id' => $customer->id,
            // Legacy column name retained for compatibility. Value represents:
            // - metered: rate per MB
            // - hotspot: flat package amount
            'rate_per_gb' => $ratePerMb,
            'currency' => $currency !== '' ? $currency : 'KES',
        ];
        if ($this->hasColumn('hotspot_user_billings', 'package_id')) {
            $billingValues['package_id'] = $data['package_id']
                ?? ($this->hasColumn('customers', 'package_id') ? $customer->package_id : null);
        }
        if ($this->hasColumn('hotspot_user_billings', 'notify_customer')) {
            $billingValues['notify_customer'] = (bool)($data['notify_customer'] ?? false);
        }

        $billing = HotspotUserBilling::updateOrCreate(
            ['username' => $username],
            $billingValues
        );

        $packageId = $this->hasColumn('hotspot_user_billings', 'package_id')
            ? ($billing->package_id ?? null)
            : ($this->hasColumn('customers', 'package_id') ? $customer->package_id : null);

        if ($packageId) {
            $this->applyPackageProfileToHotspotUser($username, (int)$packageId);
        }

        return response()->json([
            'status' => 'success',
            'message' => $isHotspotPackage ? 'Billing plan price saved.' : 'Billing rate (per MB) saved.',
            'billing' => [
                'rate_per_mb' => (float)$billing->rate_per_gb,
                'rate_per_gb' => (float)$billing->rate_per_gb,
                'currency' => $billing->currency,
                'package_id' => $packageId,
                'billing_mode' => $isHotspotPackage ? 'hotspot' : 'metered',
                'notify_customer' => $this->hasColumn('hotspot_user_billings', 'notify_customer')
                    ? (bool)$billing->notify_customer
                    : false,
            ],
        ]);
    }

    public function generateInvoice(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|max:255',
            'rate_per_mb' => 'nullable|numeric|min:0',
            'rate_per_gb' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:8',
            'usage_bytes' => 'nullable|integer|min:0',
            'package_id' => 'nullable|exists:packages,id',
            'issued_at' => 'nullable|date',
            'due_date' => 'nullable|date',
            'tax_percent' => 'nullable|numeric|min:0',
            'penalty_percent' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:4000',
            'notify_customer' => 'nullable|boolean',
        ]);

        $username = trim((string)$data['username']);
        $currency = strtoupper(trim((string)($data['currency'] ?? 'KES')));
        $notifyCustomer = (bool)($data['notify_customer'] ?? false);

        $package = null;
        if (!empty($data['package_id'])) {
            $package = Package::find($data['package_id']);
        }
        $isHotspotPackage = strtolower((string)($package?->category ?? 'metered')) === 'hotspot';

        $usage = $this->buildUsageSummary($username);
        $usageBytes = array_key_exists('usage_bytes', $data)
            ? (int)$data['usage_bytes']
            : (int)$usage['total_bytes'];

        $existingRate = (float)(HotspotUserBilling::query()
            ->where('username', $username)
            ->value('rate_per_gb') ?? 0);

        $ratePerMb = array_key_exists('rate_per_mb', $data) && $data['rate_per_mb'] !== null
            ? (float)$data['rate_per_mb']
            : ((array_key_exists('rate_per_gb', $data) && $data['rate_per_gb'] !== null)
                ? (float)$data['rate_per_gb']
                : 0);
        if ($ratePerMb <= 0) {
            $ratePerMb = (float)($package?->price ?? $existingRate);
        }

        if ($ratePerMb <= 0 && !$isHotspotPackage) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Set a billing rate per MB or choose a plan with a per-MB price before generating invoice.',
            ], 422);
        }

        if ($ratePerMb <= 0 && $isHotspotPackage) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Set a hotspot package price before generating invoice.',
            ], 422);
        }

        $usageMb = round($usageBytes / self::BYTES_PER_MB, 4);
        $subtotal = $isHotspotPackage
            ? round($ratePerMb, 2)
            : round($usageMb * $ratePerMb, 2);
        $taxPercent = (float)($data['tax_percent'] ?? 0);
        $penaltyPercent = (float)($data['penalty_percent'] ?? 0);
        $taxAmount = round($subtotal * ($taxPercent / 100), 2);
        $penaltyAmount = round($subtotal * ($penaltyPercent / 100), 2);
        $totalAmount = round($subtotal + $taxAmount + $penaltyAmount, 2);
        $issuedAt = !empty($data['issued_at']) ? Carbon::parse($data['issued_at']) : now();
        $dueDate = !empty($data['due_date']) ? Carbon::parse($data['due_date']) : $issuedAt->copy()->addDays(7);

        if ($isHotspotPackage) {
            $lineBreakdown = sprintf(
                'Hotspot package "%s" billed as flat fee: %s %.2f',
                (string)($package?->name ?? 'hotspot plan'),
                $currency !== '' ? $currency : 'KES',
                $subtotal
            );
        } else {
            $lineBreakdown = sprintf(
                'Usage %.4f MB @ %s %.2f/MB',
                $usageMb,
                $currency !== '' ? $currency : 'KES',
                $ratePerMb
            );
        }

        $customer = Customer::firstOrCreate(
            ['username' => $username],
            ['name' => $username, 'status' => 'active']
        );

        if (!empty($data['package_id']) && $this->hasColumn('customers', 'package_id')) {
            $customer->package_id = $data['package_id'];
            $customer->save();
        }

        $billingValues = [
            'customer_id' => $customer->id,
            // Legacy column name retained for compatibility. Value represents:
            // - metered: rate per MB
            // - hotspot: flat package amount
            'rate_per_gb' => $ratePerMb,
            'currency' => $currency !== '' ? $currency : 'KES',
        ];
        if ($this->hasColumn('hotspot_user_billings', 'package_id')) {
            $billingValues['package_id'] = $data['package_id']
                ?? ($this->hasColumn('customers', 'package_id') ? $customer->package_id : null);
        }
        if ($this->hasColumn('hotspot_user_billings', 'notify_customer')) {
            $billingValues['notify_customer'] = $notifyCustomer;
        }

        $billing = HotspotUserBilling::updateOrCreate(
            ['username' => $username],
            $billingValues
        );

        $packageId = $this->hasColumn('hotspot_user_billings', 'package_id')
            ? ($billing->package_id ?? null)
            : ($this->hasColumn('customers', 'package_id') ? $customer->package_id : null);

        if ($packageId) {
            $this->applyPackageProfileToHotspotUser($username, (int)$packageId);
        }

        $invoiceNumber = $this->generateInvoiceNumber($username);

        $invoicePayload = [
            'customer_id' => $customer->id,
            'invoice_number' => $invoiceNumber,
            'status' => 'unpaid',
            'amount' => $this->hasColumn('invoices', 'total_amount') ? $subtotal : $totalAmount,
        ];
        if ($this->hasColumn('invoices', 'invoice_status')) {
            $invoicePayload['invoice_status'] = 'unpaid';
        }
        if ($this->hasColumn('invoices', 'subtotal_amount')) {
            $invoicePayload['subtotal_amount'] = $subtotal;
        }
        if ($this->hasColumn('invoices', 'tax_percent')) {
            $invoicePayload['tax_percent'] = $taxPercent;
        }
        if ($this->hasColumn('invoices', 'tax_amount')) {
            $invoicePayload['tax_amount'] = $taxAmount;
        }
        if ($this->hasColumn('invoices', 'penalty_percent')) {
            $invoicePayload['penalty_percent'] = $penaltyPercent;
        }
        if ($this->hasColumn('invoices', 'penalty_amount')) {
            $invoicePayload['penalty_amount'] = $penaltyAmount;
        }
        if ($this->hasColumn('invoices', 'total_amount')) {
            $invoicePayload['total_amount'] = $totalAmount;
        }
        if ($this->hasColumn('invoices', 'paid_amount')) {
            $invoicePayload['paid_amount'] = 0;
        }
        if ($this->hasColumn('invoices', 'balance_amount')) {
            $invoicePayload['balance_amount'] = $totalAmount;
        }
        if ($this->hasColumn('invoices', 'currency')) {
            $invoicePayload['currency'] = $currency !== '' ? $currency : 'KES';
        }
        if ($this->hasColumn('invoices', 'issued_at')) {
            $invoicePayload['issued_at'] = $issuedAt;
        }
        if ($this->hasColumn('invoices', 'due_date')) {
            $invoicePayload['due_date'] = $dueDate;
        }
        if ($this->hasColumn('invoices', 'notes')) {
            $userNotes = trim((string)($data['notes'] ?? ''));
            $invoicePayload['notes'] = trim($lineBreakdown . ($userNotes !== '' ? "\n" . $userNotes : ''));
        }

        $invoice = Invoice::create($invoicePayload);

        $invoice = app(InvoiceBillingService::class)->recalculate($invoice);
        $publicUrl = app(InvoiceBillingService::class)->publicUrl($invoice);

        $notification = null;
        if ($notifyCustomer) {
            $notification = app(InvoiceNotificationService::class)->sendInvoiceIssued($invoice);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Invoice generated successfully.',
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount' => (float)($invoice->total_amount ?: $invoice->amount),
                'subtotal_amount' => (float)($invoice->subtotal_amount ?? $subtotal),
                'tax_amount' => (float)($invoice->tax_amount ?? $taxAmount),
                'penalty_amount' => (float)($invoice->penalty_amount ?? $penaltyAmount),
                'status' => $invoice->invoice_status ?: $invoice->status ?: 'unpaid',
                'due_date' => $this->hasColumn('invoices', 'due_date')
                    ? optional($invoice->due_date)->format('Y-m-d')
                    : optional($dueDate)->format('Y-m-d'),
                'public_url' => $publicUrl,
                'created_at' => optional($invoice->created_at)->toDateTimeString(),
            ],
            'usage' => [
                'total_bytes' => $usageBytes,
                'total_mb' => round($usageBytes / self::BYTES_PER_MB, 4),
                'total_gb' => round($usageBytes / self::BYTES_PER_GB, 4),
                'total_human' => $this->bytesToHuman($usageBytes),
            ],
            'billing' => [
                'rate_per_mb' => $ratePerMb,
                'rate_per_gb' => $ratePerMb,
                'currency' => $currency !== '' ? $currency : 'KES',
                'package_id' => $packageId,
                'billing_mode' => $isHotspotPackage ? 'hotspot' : 'metered',
                'notify_customer' => $this->hasColumn('hotspot_user_billings', 'notify_customer')
                    ? (bool)$billing->notify_customer
                    : $notifyCustomer,
            ],
            'notification' => $notification,
        ]);
    }

    private function fetchHotspotUsers(): array
    {
        [$api, $error] = $this->connectApi();
        if ($error) {
            Log::warning('fetchHotspotUsers connect failed', ['error' => $error]);
            return [];
        }

        $usersRaw = $api->comm('/ip/hotspot/user/print') ?? [];
        $routerActiveSessions = $api->comm('/ip/hotspot/active/print') ?? [];
        $hosts = $api->comm('/ip/hotspot/host/print') ?? [];
        $cookies = $api->comm('/ip/hotspot/cookie/print') ?? [];

        $this->syncConnectionTelemetry($routerActiveSessions, $usersRaw, 'users_panel');
        $activeSessions = $this->augmentActiveSessionsWithTrackedConnections($routerActiveSessions);
        return $this->buildUsersWithStatus($usersRaw, $activeSessions, $hosts, $cookies);
    }

    private function fetchHotspotHosts(): array
    {
        [$api, $error] = $this->connectApi();
        if ($error) {
            Log::warning('fetchHotspotHosts connect failed', ['error' => $error]);
            return [];
        }

        return $api->comm('/ip/hotspot/host/print') ?? [];
    }

    private function fetchHotspotCookies(): array
    {
        [$api, $error] = $this->connectApi();
        if ($error) {
            Log::warning('fetchHotspotCookies connect failed', ['error' => $error]);
            return [];
        }

        return $api->comm('/ip/hotspot/cookie/print') ?? [];
    }

    private function fetchHotspotSessions(): array
    {
        [$api, $error] = $this->connectApi();
        if ($error) {
            Log::warning('fetchHotspotSessions connect failed', ['error' => $error]);
            return [];
        }

        $usersRaw = $api->comm('/ip/hotspot/user/print') ?? [];
        $routerActiveSessions = $api->comm('/ip/hotspot/active/print') ?? [];
        $this->syncConnectionTelemetry($routerActiveSessions, $usersRaw, 'sessions_panel');

        return $this->augmentActiveSessionsWithTrackedConnections($routerActiveSessions);
    }

    private function augmentActiveSessionsWithTrackedConnections(array $activeSessions): array
    {
        if (
            !$this->hasTable('connections')
            || !$this->hasColumn('connections', 'username')
        ) {
            return $activeSessions;
        }

        $activeUserIndex = array_fill_keys(
            collect($activeSessions)
                ->map(fn(array $session) => strtolower(trim((string)($session['user'] ?? $session['username'] ?? ''))))
                ->filter()
                ->values()
                ->all(),
            true
        );

        $activeMacIndex = array_fill_keys(
            collect($activeSessions)
                ->map(fn(array $session) => strtolower(trim((string)($session['mac-address'] ?? ''))))
                ->filter()
                ->values()
                ->all(),
            true
        );

        $query = Connection::query()
            ->where('username', 'like', 'demo_user_%');

        if ($this->hasColumn('connections', 'status')) {
            $query->where('status', 'active');
        }
        if ($this->hasColumn('connections', 'ended_at')) {
            $query->whereNull('ended_at');
        }
        if ($this->hasColumn('connections', 'expires_at')) {
            $query->where(function ($expiresQuery) {
                $expiresQuery->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
        }
        if ($this->hasColumn('connections', 'started_at')) {
            $query->orderByDesc('started_at');
        }

        $columns = ['id'];
        foreach (['username', 'mac_address', 'ip_address', 'started_at', 'updated_at', 'bytes_in', 'bytes_out'] as $column) {
            if ($this->hasColumn('connections', $column)) {
                $columns[] = $column;
            }
        }

        $connections = $query
            ->limit(60)
            ->get(array_values(array_unique($columns)));

        foreach ($connections as $connection) {
            $username = trim((string)($connection->username ?? ''));
            $usernameKey = strtolower($username);
            $mac = trim((string)($connection->mac_address ?? ''));
            $macKey = strtolower($mac);

            if ($usernameKey === '') {
                continue;
            }
            if (isset($activeUserIndex[$usernameKey])) {
                continue;
            }
            if ($macKey !== '' && isset($activeMacIndex[$macKey])) {
                continue;
            }

            $startedAt = $connection->started_at
                ? Carbon::parse($connection->started_at)
                : ($connection->updated_at ? Carbon::parse($connection->updated_at) : now());
            if ($startedAt->isFuture()) {
                $startedAt = now();
            }

            $activeSessions[] = [
                'user' => $username,
                'username' => $username,
                'mac-address' => $mac,
                'address' => (string)($connection->ip_address ?? ''),
                'uptime' => $this->formatSecondsHuman(max(0, $startedAt->diffInSeconds(now()))),
                'bytes-in' => max(0, (int)($connection->bytes_in ?? 0)),
                'bytes-out' => max(0, (int)($connection->bytes_out ?? 0)),
                'tracked-connection' => true,
                'connection-id' => (int)$connection->id,
            ];

            $activeUserIndex[$usernameKey] = true;
            if ($macKey !== '') {
                $activeMacIndex[$macKey] = true;
            }
        }

        return $activeSessions;
    }

    private function buildSyntheticUserFromSession(array $activeSessions, string $username): ?array
    {
        foreach ($activeSessions as $session) {
            $sessionUser = trim((string)($session['user'] ?? $session['username'] ?? ''));
            if ($sessionUser !== $username) {
                continue;
            }

            return [
                'name' => $sessionUser,
                'profile' => (string)($session['profile'] ?? 'default'),
                'uptime' => (string)($session['uptime'] ?? '-'),
                'limit-uptime' => (string)($session['limit-uptime'] ?? '-'),
                'last-seen' => !empty($session['tracked-connection']) ? 'Tracked session' : 'Active now',
                'mac-address' => (string)($session['mac-address'] ?? ''),
                'server' => (string)($session['server'] ?? '-'),
                'bytes-in' => max(0, (int)($session['bytes-in'] ?? 0)),
                'bytes-out' => max(0, (int)($session['bytes-out'] ?? 0)),
                'comment' => !empty($session['tracked-connection']) ? 'Tracked from connections' : 'Active session',
                'disabled' => 'false',
            ];
        }

        return null;
    }

    private function syncConnectionTelemetry(array $activeSessions, array $usersRaw = [], string $source = 'session_scan'): void
    {
        if (!$this->hasTable('connections')) {
            return;
        }

        $query = Connection::query();
        if ($this->hasColumn('connections', 'status')) {
            $query->where('status', 'active');
        }
        if ($this->hasColumn('connections', 'started_at')) {
            $query->orderByDesc('started_at');
        }
        $query->orderByDesc('id');

        $select = ['id'];
        foreach ([
            'username',
            'mac_address',
            'ip_address',
            'status',
            'started_at',
            'ended_at',
            'expires_at',
            'updated_at',
            'start_bytes_in',
            'start_bytes_out',
            'bytes_in',
            'bytes_out',
        ] as $column) {
            if ($this->hasColumn('connections', $column)) {
                $select[] = $column;
            }
        }

        $activeConnections = $query->get(array_values(array_unique($select)));
        if ($activeConnections->isEmpty() && $activeSessions === []) {
            return;
        }

        $usersByUsername = collect($usersRaw)
            ->mapWithKeys(function (array $user) {
                $username = strtolower(trim((string)($user['name'] ?? $user['username'] ?? '')));
                return $username !== '' ? [$username => $user] : [];
            })
            ->all();

        $sessionsByUsername = [];
        $sessionsByMac = [];
        foreach ($activeSessions as $session) {
            $usernameKey = strtolower(trim((string)($session['user'] ?? $session['username'] ?? '')));
            $macKey = strtolower(trim((string)($session['mac-address'] ?? '')));

            if ($usernameKey !== '' && !isset($sessionsByUsername[$usernameKey])) {
                $sessionsByUsername[$usernameKey] = $session;
            }
            if ($macKey !== '' && !isset($sessionsByMac[$macKey])) {
                $sessionsByMac[$macKey] = $session;
            }
        }

        $claimedSessions = [];
        foreach ($activeConnections as $connection) {
            $usernameKey = strtolower(trim((string)($connection->username ?? '')));
            $macKey = strtolower(trim((string)($connection->mac_address ?? '')));

            $session = null;
            if ($usernameKey !== '' && isset($sessionsByUsername[$usernameKey])) {
                $candidate = $sessionsByUsername[$usernameKey];
                $signature = $this->activeSessionSignature($candidate);
                if (!isset($claimedSessions[$signature])) {
                    $session = $candidate;
                    $claimedSessions[$signature] = true;
                }
            }

            if (!$session && $macKey !== '' && isset($sessionsByMac[$macKey])) {
                $candidate = $sessionsByMac[$macKey];
                $signature = $this->activeSessionSignature($candidate);
                if (!isset($claimedSessions[$signature])) {
                    $session = $candidate;
                    $claimedSessions[$signature] = true;
                }
            }

            if ($session) {
                $hotspotUser = $usernameKey !== '' ? ($usersByUsername[$usernameKey] ?? null) : null;
                $this->updateTrackedConnectionFromSession($connection, $session, $hotspotUser, $source);
                continue;
            }

            $lastSeenAt = $connection->updated_at
                ? Carbon::parse($connection->updated_at)
                : ($connection->started_at ? Carbon::parse($connection->started_at) : null);
            if ($lastSeenAt && $lastSeenAt->greaterThan(now()->subSeconds(self::SESSION_DISCONNECT_GRACE_SECONDS))) {
                continue;
            }

            $this->finalizeTrackedConnection($connection);
        }
    }

    private function activeSessionSignature(array $session): string
    {
        return strtolower(trim((string)($session['user'] ?? $session['username'] ?? '')))
            . '|'
            . strtolower(trim((string)($session['mac-address'] ?? '')))
            . '|'
            . trim((string)($session['address'] ?? ''));
    }

    private function updateTrackedConnectionFromSession(
        Connection $connection,
        array $session,
        ?array $hotspotUser = null,
        string $source = 'session_scan'
    ): void {
        $updates = [];
        $bytesIn = max(0, (int)($session['bytes-in'] ?? 0));
        $bytesOut = max(0, (int)($session['bytes-out'] ?? 0));
        $uptimeSeconds = $this->parseDurationToSeconds((string)($session['uptime'] ?? ''));

        if ($this->hasColumn('connections', 'status')) {
            $updates['status'] = 'active';
        }
        if ($this->hasColumn('connections', 'ip_address')) {
            $ip = trim((string)($session['address'] ?? ''));
            if ($ip !== '') {
                $updates['ip_address'] = $ip;
            }
        }
        if ($this->hasColumn('connections', 'bytes_in')) {
            $updates['bytes_in'] = $bytesIn;
        }
        if ($this->hasColumn('connections', 'bytes_out')) {
            $updates['bytes_out'] = $bytesOut;
        }
        if ($this->hasColumn('connections', 'ended_at') && $connection->ended_at) {
            $updates['ended_at'] = null;
        }
        if ($this->hasColumn('connections', 'started_at') && !$connection->started_at && $uptimeSeconds > 0) {
            $updates['started_at'] = now()->copy()->subSeconds($uptimeSeconds);
        }
        if ($hotspotUser) {
            if ($this->hasColumn('connections', 'start_bytes_in')) {
                $rawIn = max(0, (int)($hotspotUser['bytes-in'] ?? $hotspotUser['bytes_in'] ?? 0));
                $updates['start_bytes_in'] = max(0, $rawIn - $bytesIn);
            }
            if ($this->hasColumn('connections', 'start_bytes_out')) {
                $rawOut = max(0, (int)($hotspotUser['bytes-out'] ?? $hotspotUser['bytes_out'] ?? 0));
                $updates['start_bytes_out'] = max(0, $rawOut - $bytesOut);
            }
        }

        if ($updates !== []) {
            $connection->fill($updates);
            if ($connection->isDirty()) {
                $connection->save();
            } else {
                $connection->touch();
            }
        }

        $this->recordConnectionUsageSample(
            $connection,
            now(),
            $uptimeSeconds > 0 ? $uptimeSeconds : null,
            $bytesIn,
            $bytesOut,
            $source
        );
    }

    private function finalizeTrackedConnection(Connection $connection, $endedAt = null): void
    {
        $resolvedEndedAt = $endedAt
            ? Carbon::parse($endedAt)
            : ($connection->updated_at ? Carbon::parse($connection->updated_at) : now());

        if ($connection->started_at && $resolvedEndedAt->lessThan($connection->started_at)) {
            $resolvedEndedAt = $connection->started_at->copy();
        }

        $updates = [];
        if ($this->hasColumn('connections', 'ended_at')) {
            $updates['ended_at'] = $resolvedEndedAt;
        }
        if ($this->hasColumn('connections', 'status')) {
            $expiresAt = $this->hasColumn('connections', 'expires_at') && $connection->expires_at
                ? Carbon::parse($connection->expires_at)
                : null;
            $updates['status'] = ($expiresAt && $expiresAt->lessThanOrEqualTo($resolvedEndedAt)) ? 'expired' : 'terminated';
        }

        if ($updates !== []) {
            $connection->fill($updates);
            if ($connection->isDirty()) {
                $connection->save();
            }
        }

        $uptimeSeconds = $connection->started_at
            ? max(0, $connection->started_at->diffInSeconds($resolvedEndedAt))
            : null;

        $this->recordConnectionUsageSample(
            $connection,
            $resolvedEndedAt,
            $uptimeSeconds,
            max(0, (int)($connection->bytes_in ?? 0)),
            max(0, (int)($connection->bytes_out ?? 0)),
            strtolower((string)($connection->status ?? '')) === 'expired' ? 'session_expired' : 'session_end'
        );
    }

    private function matchActiveConnectionForIdentity(string $username, string $mac): ?Connection
    {
        if (!$this->hasTable('connections')) {
            return null;
        }

        $query = Connection::query();
        if ($this->hasColumn('connections', 'status')) {
            $query->where('status', 'active');
        }

        $hasUsername = $username !== '' && $this->hasColumn('connections', 'username');
        $hasMac = $mac !== '' && $this->hasColumn('connections', 'mac_address');
        if (!$hasUsername && !$hasMac) {
            return null;
        }

        $query->where(function ($matchQuery) use ($hasUsername, $hasMac, $username, $mac) {
            if ($hasUsername) {
                $matchQuery->where('username', $username);
            }
            if ($hasMac) {
                if ($hasUsername) {
                    $matchQuery->orWhere('mac_address', $mac);
                } else {
                    $matchQuery->where('mac_address', $mac);
                }
            }
        });

        if ($this->hasColumn('connections', 'started_at')) {
            $query->orderByDesc('started_at');
        }

        return $query->orderByDesc('id')->first();
    }

    private function canStoreConnectionUsageSamples(): bool
    {
        return $this->hasTable('connection_usage_samples');
    }

    private function recordConnectionUsageSample(
        Connection $connection,
        $recordedAt,
        ?int $uptimeSeconds,
        int $bytesIn,
        int $bytesOut,
        string $source
    ): void {
        if (!$this->canStoreConnectionUsageSamples()) {
            return;
        }

        $stamp = $recordedAt ? Carbon::parse($recordedAt) : now();
        $stamp = $stamp->copy()->setMicrosecond(0);

        $latest = ConnectionUsageSample::query()
            ->where('connection_id', $connection->id)
            ->latest('recorded_at')
            ->first();

        $payload = [
            'uptime_seconds' => $uptimeSeconds !== null ? max(0, $uptimeSeconds) : null,
            'bytes_in' => max(0, $bytesIn),
            'bytes_out' => max(0, $bytesOut),
            'source' => substr($source !== '' ? $source : 'snapshot', 0, 32),
        ];

        if ($latest && $latest->recorded_at && $latest->recorded_at->equalTo($stamp)) {
            $latest->fill($payload);
            $latest->save();
            return;
        }

        ConnectionUsageSample::create([
            'connection_id' => $connection->id,
            'recorded_at' => $stamp,
            'uptime_seconds' => $payload['uptime_seconds'],
            'bytes_in' => $payload['bytes_in'],
            'bytes_out' => $payload['bytes_out'],
            'source' => $payload['source'],
        ]);
    }

    private function buildStoredTrafficPayload(Connection $connection): array
    {
        $status = strtolower(trim((string)($connection->status ?? 'offline')));
        $startedAt = $connection->started_at ? Carbon::parse($connection->started_at) : null;
        $endedAt = null;

        if ($this->hasColumn('connections', 'ended_at') && $connection->ended_at) {
            $endedAt = Carbon::parse($connection->ended_at);
        } elseif ($connection->updated_at) {
            $endedAt = Carbon::parse($connection->updated_at);
        } elseif ($this->hasColumn('connections', 'expires_at') && $connection->expires_at) {
            $endedAt = Carbon::parse($connection->expires_at);
        }

        $bytesIn = max(0, (int)($connection->bytes_in ?? 0));
        $bytesOut = max(0, (int)($connection->bytes_out ?? 0));
        $rows = [];

        if ($startedAt) {
            $rows[] = [
                'recorded_at' => $startedAt->copy(),
                'uptime_seconds' => 0,
                'bytes_in' => 0,
                'bytes_out' => 0,
                'source' => 'session_start',
            ];
        }

        if ($this->canStoreConnectionUsageSamples()) {
            $samples = ConnectionUsageSample::query()
                ->where('connection_id', $connection->id)
                ->orderBy('recorded_at')
                ->get(['recorded_at', 'uptime_seconds', 'bytes_in', 'bytes_out', 'source']);

            if ($samples->count() > self::SESSION_HISTORY_SAMPLE_LIMIT) {
                $limit = self::SESSION_HISTORY_SAMPLE_LIMIT;
                $step = ($samples->count() - 1) / max(1, $limit - 1);
                $downsampled = collect();

                for ($i = 0; $i < $limit; $i++) {
                    $index = (int)round($i * $step);
                    if (isset($samples[$index])) {
                        $downsampled->push($samples[$index]);
                    }
                }

                $samples = $downsampled->values();
            }

            foreach ($samples as $sample) {
                $rows[] = [
                    'recorded_at' => $sample->recorded_at ? Carbon::parse($sample->recorded_at) : now(),
                    'uptime_seconds' => $sample->uptime_seconds !== null ? (int)$sample->uptime_seconds : null,
                    'bytes_in' => max(0, (int)($sample->bytes_in ?? 0)),
                    'bytes_out' => max(0, (int)($sample->bytes_out ?? 0)),
                    'source' => (string)($sample->source ?? 'snapshot'),
                ];
            }
        }

        if ($endedAt || $bytesIn > 0 || $bytesOut > 0) {
            $finalRecordedAt = $endedAt ?: now();
            $finalUptime = $startedAt ? max(0, $startedAt->diffInSeconds($finalRecordedAt)) : null;
            $rows[] = [
                'recorded_at' => $finalRecordedAt->copy(),
                'uptime_seconds' => $finalUptime,
                'bytes_in' => $bytesIn,
                'bytes_out' => $bytesOut,
                'source' => $status === 'active' ? 'session_live' : 'session_end',
            ];
        }

        usort($rows, function (array $a, array $b) {
            return $a['recorded_at']->getTimestamp() <=> $b['recorded_at']->getTimestamp();
        });

        $deduped = [];
        foreach ($rows as $row) {
            $key = $row['recorded_at']->copy()->setMicrosecond(0)->toDateTimeString();
            $deduped[$key] = $row;
        }

        $history = [];
        $previous = null;
        foreach (array_values($deduped) as $row) {
            $tx = 0;
            $rx = 0;

            if ($previous) {
                $elapsed = max(1, $row['recorded_at']->diffInSeconds($previous['recorded_at']));
                $deltaIn = max(0, (int)$row['bytes_in'] - (int)$previous['bytes_in']);
                $deltaOut = max(0, (int)$row['bytes_out'] - (int)$previous['bytes_out']);
                $rx = (int)round(($deltaIn * 8) / $elapsed);
                $tx = (int)round(($deltaOut * 8) / $elapsed);
            }

            $totalBytes = max(0, (int)$row['bytes_in']) + max(0, (int)$row['bytes_out']);
            $history[] = [
                'recorded_at' => $row['recorded_at']->toDateTimeString(),
                'label' => $row['recorded_at']->format('H:i:s'),
                'source' => (string)($row['source'] ?? 'snapshot'),
                'tx' => $tx,
                'rx' => $rx,
                'bytes_in' => max(0, (int)$row['bytes_in']),
                'bytes_out' => max(0, (int)$row['bytes_out']),
                'total_bytes' => $totalBytes,
            ];

            $previous = $row;
        }

        return [
            'ok' => true,
            'historical' => true,
            'username' => (string)($connection->username ?? ''),
            'status' => $status !== '' ? $status : 'offline',
            'interface' => null,
            'ip' => $connection->ip_address ?: null,
            'mac' => $connection->mac_address ?: null,
            'uptime' => ($startedAt && $endedAt) ? $this->formatSecondsHuman(max(0, $startedAt->diffInSeconds($endedAt))) : null,
            'live_available' => false,
            'live_mode' => 'stored_history',
            'session_bytes_in' => $bytesIn,
            'session_bytes_out' => $bytesOut,
            'session_total_bytes' => $bytesIn + $bytesOut,
            'user_bytes_in' => $bytesIn,
            'user_bytes_out' => $bytesOut,
            'user_total_bytes' => $bytesIn + $bytesOut,
            'connection_id' => (int)$connection->id,
            'history_started_at' => $startedAt?->toDateTimeString(),
            'history_ended_at' => $endedAt?->toDateTimeString(),
            'history' => $history,
        ];
    }

    private function connectApi(): array
    {
        $api = new RouterOSAPI();
        $config = config('mikrotik');

        $host = $config['host'] ?? null;
        $user = $config['user'] ?? null;
        $pass = $config['pass'] ?? null;
        $port = (int)($config['port'] ?? 8728);

        if (!$host || !$user) {
            return [null, 'MikroTik config missing (host/user).'];
        }

        try {
            $api->port = $port;
            $ok = $api->connect($host, $user, (string)$pass);

            if (!$ok) {
                return [null, 'Could not connect to MikroTik (check host/user/pass/port).'];
            }

            return [$api, null];
        } catch (\Throwable $e) {
            Log::error('RouterOS connect exception', [
                'error' => $e->getMessage(),
                'host' => $host,
                'port' => $port,
            ]);

            return [null, 'MikroTik connection error (see logs).'];
        }
    }

    private function buildUsersWithStatus(array $usersRaw, array $activeSessions, array $hosts = [], array $cookies = []): array
    {
        $nowTimestamp = now()->timestamp;
        $activeUsers = collect($activeSessions)
            ->map(fn(array $session) => strtolower(trim((string)($session['user'] ?? $session['username'] ?? ''))))
            ->filter()
            ->values()
            ->all();

        $activeMacs = collect($activeSessions)
            ->pluck('mac-address')
            ->map(fn($mac) => strtolower(trim((string)$mac)))
            ->filter()
            ->values()
            ->all();

        $customerUsers = collect($this->knownCustomerUsernames())
            ->map(fn($name) => strtolower(trim((string)$name)))
            ->filter()
            ->values()
            ->all();

        $meteredUsers = collect($this->knownMeteredUsernames())
            ->map(fn($name) => strtolower(trim((string)$name)))
            ->filter()
            ->values()
            ->all();

        $activeUserIndex = array_fill_keys($activeUsers, true);
        $activeMacIndex = array_fill_keys($activeMacs, true);
        $customerUserIndex = array_fill_keys($customerUsers, true);
        $meteredUserIndex = array_fill_keys($meteredUsers, true);

        $activeUsageByUser = [];
        $activeUsageByMac = [];
        $activeStartedAtByUser = [];
        $activeStartedAtByMac = [];
        foreach ($activeSessions as $session) {
            $sessionUser = strtolower(trim((string)($session['user'] ?? $session['username'] ?? '')));
            $sessionMac = strtolower(trim((string)($session['mac-address'] ?? '')));
            $sessionIn = (int)($session['bytes-in'] ?? 0);
            $sessionOut = (int)($session['bytes-out'] ?? 0);
            $sessionUptimeSeconds = $this->parseDurationToSeconds((string)($session['uptime'] ?? ''));
            $startedAt = $sessionUptimeSeconds > 0
                ? max(0, $nowTimestamp - $sessionUptimeSeconds)
                : $nowTimestamp;

            if ($sessionUser !== '') {
                if (!isset($activeUsageByUser[$sessionUser])) {
                    $activeUsageByUser[$sessionUser] = ['in' => 0, 'out' => 0];
                }
                $activeUsageByUser[$sessionUser]['in'] += $sessionIn;
                $activeUsageByUser[$sessionUser]['out'] += $sessionOut;
                $activeStartedAtByUser[$sessionUser] = max($activeStartedAtByUser[$sessionUser] ?? 0, $startedAt);
            }

            if ($sessionMac !== '') {
                if (!isset($activeUsageByMac[$sessionMac])) {
                    $activeUsageByMac[$sessionMac] = ['in' => 0, 'out' => 0];
                }
                $activeUsageByMac[$sessionMac]['in'] += $sessionIn;
                $activeUsageByMac[$sessionMac]['out'] += $sessionOut;
                $activeStartedAtByMac[$sessionMac] = max($activeStartedAtByMac[$sessionMac] ?? 0, $startedAt);
            }
        }

        $connectedHostMacIndex = [];
        foreach ($hosts as $host) {
            $hostMac = strtolower(trim((string)($host['mac-address'] ?? '')));
            if ($hostMac === '') {
                continue;
            }

            $activeHostSessions = (int)($host['active-sessions'] ?? 0);
            $isAuthorized = in_array(strtolower(trim((string)($host['authorized'] ?? ''))), ['1', 'true', 'yes'], true);
            $isBypassed = in_array(strtolower(trim((string)($host['bypassed'] ?? ''))), ['1', 'true', 'yes'], true);

            if ($activeHostSessions > 0 || $isAuthorized || $isBypassed) {
                $connectedHostMacIndex[$hostMac] = true;
            }
        }

        $cookieMacsByUser = [];
        foreach ($cookies as $cookie) {
            $cookieUser = strtolower(trim((string)($cookie['user'] ?? '')));
            $cookieMac = strtolower(trim((string)($cookie['mac-address'] ?? '')));

            if ($cookieUser === '' || $cookieMac === '') {
                continue;
            }

            if (!isset($cookieMacsByUser[$cookieUser])) {
                $cookieMacsByUser[$cookieUser] = [];
            }

            $cookieMacsByUser[$cookieUser][$cookieMac] = true;
        }

        $finalUsers = [];
        $seenUserIndex = [];
        $seenMacIndex = [];

        foreach ($usersRaw as $user) {
            $username = trim((string)($user['name'] ?? $user['username'] ?? 'guest'));
            $usernameKey = strtolower($username);
            $mac = $user['mac-address'] ?? $user['mac'] ?? null;
            $macKey = $mac ? strtolower(trim((string)$mac)) : '';
            $comment = trim((string)($user['comment'] ?? ''));
            $commentKey = strtolower($comment);
            $isDisabled = strtolower((string)($user['disabled'] ?? 'false')) === 'true';

            $hasActiveSession = isset($activeUserIndex[$usernameKey]) || ($macKey !== '' && isset($activeMacIndex[$macKey]));
            $hasConnectedHost = $macKey !== '' && isset($connectedHostMacIndex[$macKey]);
            if (!$hasConnectedHost && isset($cookieMacsByUser[$usernameKey])) {
                foreach (array_keys($cookieMacsByUser[$usernameKey]) as $cookieMac) {
                    if (isset($connectedHostMacIndex[$cookieMac])) {
                        $hasConnectedHost = true;
                        break;
                    }
                }
            }

            $isOnline = $hasActiveSession || $hasConnectedHost;
            if ($isOnline) {
                $status = 'active';
            } elseif ($isDisabled) {
                $status = 'disabled';
            } else {
                $status = 'offline';
            }

            $isMetered = isset($meteredUserIndex[$usernameKey]) || str_contains($commentKey, 'metered');
            $isTemporaryHotspot = str_starts_with($usernameKey, 'hs_')
                || str_starts_with($usernameKey, 'tmp_')
                || str_contains($commentKey, 'temporary hotspot')
                || str_contains($commentKey, 'portal');

            if ($isMetered) {
                $accountType = 'metered_static';
                $accountTypeLabel = 'Metered';
            } elseif ($isTemporaryHotspot) {
                $accountType = 'hotspot_temporary';
                $accountTypeLabel = 'Temporary';
            } elseif (isset($customerUserIndex[$usernameKey])) {
                $accountType = 'hotspot_static';
                $accountTypeLabel = 'Hotspot';
            } else {
                $accountType = 'hotspot';
                $accountTypeLabel = 'Hotspot';
            }

            $bytesIn = (int)($user['bytes-in'] ?? 0);
            $bytesOut = (int)($user['bytes-out'] ?? 0);
            if (($bytesIn + $bytesOut) <= 0) {
                if (isset($activeUsageByUser[$usernameKey])) {
                    $bytesIn = (int)($activeUsageByUser[$usernameKey]['in'] ?? 0);
                    $bytesOut = (int)($activeUsageByUser[$usernameKey]['out'] ?? 0);
                } elseif ($macKey !== '' && isset($activeUsageByMac[$macKey])) {
                    $bytesIn = (int)($activeUsageByMac[$macKey]['in'] ?? 0);
                    $bytesOut = (int)($activeUsageByMac[$macKey]['out'] ?? 0);
                }
            }

            $activityTimestamp = max(
                (int)($activeStartedAtByUser[$usernameKey] ?? 0),
                (int)($macKey !== '' ? ($activeStartedAtByMac[$macKey] ?? 0) : 0)
            );
            if ($activityTimestamp <= 0 && $hasConnectedHost) {
                $activityTimestamp = $nowTimestamp;
            }
            if ($activityTimestamp <= 0) {
                $activityTimestamp = $this->resolveRouterActivityTimestamp((string)($user['last-seen'] ?? ''), $nowTimestamp);
            }

            $finalUsers[] = [
                'username' => $username,
                'profile' => $user['profile'] ?? 'default',
                'uptime' => $user['uptime'] ?? '-',
                'limit-uptime' => $user['limit-uptime'] ?? '-',
                'last-seen' => $user['last-seen'] ?? '-',
                'mac-address' => $mac ?? '-',
                'mac' => $mac,
                'server' => $user['server'] ?? '-',
                'status' => $status,
                'status_label' => $status === 'active' ? 'Online' : ($status === 'disabled' ? 'Disabled' : 'Offline'),
                'is_online' => $isOnline,
                'account_type' => $accountType,
                'account_type_label' => $accountTypeLabel,
                'bytes-in' => $bytesIn,
                'bytes-out' => $bytesOut,
                'comment' => $comment,
                'disabled' => $user['disabled'] ?? 'false',
                'activity_sort' => $activityTimestamp,
                'status_source' => $hasActiveSession ? 'active-session' : ($hasConnectedHost ? 'host-cookie' : 'none'),
            ];

            if ($usernameKey !== '') {
                $seenUserIndex[$usernameKey] = true;
            }
            if ($macKey !== '') {
                $seenMacIndex[$macKey] = true;
            }
        }

        foreach ($activeSessions as $session) {
            $username = trim((string)($session['user'] ?? $session['username'] ?? ''));
            $usernameKey = strtolower($username);
            $mac = trim((string)($session['mac-address'] ?? ''));
            $macKey = strtolower($mac);

            if ($usernameKey === '' && $macKey === '') {
                continue;
            }
            if ($usernameKey !== '' && isset($seenUserIndex[$usernameKey])) {
                continue;
            }
            if ($usernameKey === '' && $macKey !== '' && isset($seenMacIndex[$macKey])) {
                continue;
            }

            $displayUsername = $username !== ''
                ? $username
                : ('tracked_' . ($macKey !== '' ? str_replace(':', '', $macKey) : ('session_' . (string)($session['connection-id'] ?? 'user'))));
            $displayUsernameKey = strtolower($displayUsername);

            $comment = !empty($session['tracked-connection']) ? 'Tracked from connections' : 'Active session';
            $commentKey = strtolower($comment);
            $isMetered = isset($meteredUserIndex[$displayUsernameKey]) || str_contains($commentKey, 'metered');
            $isTemporaryHotspot = str_starts_with($displayUsernameKey, 'hs_')
                || str_starts_with($displayUsernameKey, 'tmp_')
                || str_contains($commentKey, 'temporary hotspot')
                || str_contains($commentKey, 'portal');

            if ($isMetered) {
                $accountType = 'metered_static';
                $accountTypeLabel = 'Metered';
            } elseif ($isTemporaryHotspot) {
                $accountType = 'hotspot_temporary';
                $accountTypeLabel = 'Temporary';
            } elseif (isset($customerUserIndex[$displayUsernameKey])) {
                $accountType = 'hotspot_static';
                $accountTypeLabel = 'Hotspot';
            } else {
                $accountType = 'hotspot';
                $accountTypeLabel = 'Hotspot';
            }

            $bytesIn = (int)($session['bytes-in'] ?? ($activeUsageByUser[$displayUsernameKey]['in'] ?? ($activeUsageByMac[$macKey]['in'] ?? 0)));
            $bytesOut = (int)($session['bytes-out'] ?? ($activeUsageByUser[$displayUsernameKey]['out'] ?? ($activeUsageByMac[$macKey]['out'] ?? 0)));
            $activityTimestamp = max(
                (int)($activeStartedAtByUser[$displayUsernameKey] ?? 0),
                (int)($macKey !== '' ? ($activeStartedAtByMac[$macKey] ?? 0) : 0),
                $nowTimestamp
            );

            $finalUsers[] = [
                'username' => $displayUsername,
                'profile' => $session['profile'] ?? 'default',
                'uptime' => $session['uptime'] ?? '-',
                'limit-uptime' => $session['limit-uptime'] ?? '-',
                'last-seen' => !empty($session['tracked-connection']) ? 'Tracked session' : 'Active now',
                'mac-address' => $mac !== '' ? $mac : '-',
                'mac' => $mac !== '' ? $mac : null,
                'server' => $session['server'] ?? '-',
                'status' => 'active',
                'status_label' => !empty($session['tracked-connection']) ? 'Tracked' : 'Online',
                'is_online' => true,
                'account_type' => $accountType,
                'account_type_label' => $accountTypeLabel,
                'bytes-in' => $bytesIn,
                'bytes-out' => $bytesOut,
                'comment' => $comment,
                'disabled' => 'false',
                'activity_sort' => $activityTimestamp,
                'status_source' => !empty($session['tracked-connection']) ? 'tracked-connection' : 'active-session-only',
            ];

            $seenUserIndex[$displayUsernameKey] = true;
            if ($macKey !== '') {
                $seenMacIndex[$macKey] = true;
            }
        }

        $statusRank = ['active' => 0, 'offline' => 1, 'disabled' => 2];
        $typeRank = ['metered_static' => 0, 'hotspot_static' => 1, 'hotspot' => 2, 'hotspot_temporary' => 3];

        usort($finalUsers, function (array $a, array $b) use ($statusRank, $typeRank) {
            $aActivity = (int)($a['activity_sort'] ?? 0);
            $bActivity = (int)($b['activity_sort'] ?? 0);
            if ($aActivity !== $bActivity) {
                return $bActivity <=> $aActivity;
            }

            $aStatus = $statusRank[$a['status'] ?? 'offline'] ?? 99;
            $bStatus = $statusRank[$b['status'] ?? 'offline'] ?? 99;
            if ($aStatus !== $bStatus) {
                return $aStatus <=> $bStatus;
            }

            $aType = $typeRank[$a['account_type'] ?? 'hotspot'] ?? 99;
            $bType = $typeRank[$b['account_type'] ?? 'hotspot'] ?? 99;
            if ($aType !== $bType) {
                return $aType <=> $bType;
            }

            return strcmp((string)($a['username'] ?? ''), (string)($b['username'] ?? ''));
        });

        return $finalUsers;
    }

    private function resolveRouterActivityTimestamp(?string $value, ?int $nowTimestamp = null): int
    {
        $raw = trim((string)$value);
        if ($raw === '' || $raw === '-') {
            return 0;
        }

        $nowTimestamp = $nowTimestamp ?: now()->timestamp;
        $durationCandidate = trim((string)preg_replace('/\s+ago$/i', '', $raw));
        if ($durationCandidate !== '' && preg_match('/[wdhms:]/i', $durationCandidate)) {
            $durationSeconds = $this->parseDurationToSeconds($durationCandidate);
            if ($durationSeconds > 0) {
                return max(0, $nowTimestamp - $durationSeconds);
            }
        }

        $parsed = strtotime($raw);
        if ($parsed !== false) {
            return $parsed;
        }

        try {
            return Carbon::parse($raw)->timestamp;
        } catch (\Throwable) {
            return 0;
        }
    }

    private function knownCustomerUsernames(): array
    {
        if (!$this->hasTable('customers') || !$this->hasColumn('customers', 'username')) {
            return [];
        }

        try {
            return Customer::query()
                ->whereNotNull('username')
                ->where('username', '!=', '')
                ->pluck('username')
                ->map(fn($name) => trim((string)$name))
                ->filter()
                ->unique()
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('knownCustomerUsernames failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function knownMeteredUsernames(): array
    {
        $usernames = collect();

        if (
            $this->hasTable('subscriptions')
            && $this->hasColumn('subscriptions', 'username')
            && $this->hasColumn('subscriptions', 'type')
        ) {
            try {
                $subscriptionUsers = Subscription::query()
                    ->whereRaw('LOWER(type) = ?', ['metered'])
                    ->whereNotNull('username')
                    ->pluck('username')
                    ->all();

                $usernames = $usernames->merge($subscriptionUsers);
            } catch (\Throwable $e) {
                Log::warning('knownMeteredUsernames: subscriptions lookup failed', ['error' => $e->getMessage()]);
            }
        }

        if (
            $this->hasTable('customers')
            && $this->hasColumn('customers', 'username')
            && $this->hasColumn('customers', 'package_id')
            && $this->hasTable('packages')
            && $this->hasColumn('packages', 'category')
        ) {
            try {
                $customerUsers = DB::table('customers')
                    ->join('packages', 'packages.id', '=', 'customers.package_id')
                    ->whereRaw('LOWER(packages.category) = ?', ['metered'])
                    ->whereNotNull('customers.username')
                    ->pluck('customers.username')
                    ->all();

                $usernames = $usernames->merge($customerUsers);
            } catch (\Throwable $e) {
                Log::warning('knownMeteredUsernames: customers lookup failed', ['error' => $e->getMessage()]);
            }
        }

        if (
            $this->hasTable('hotspot_user_billings')
            && $this->hasColumn('hotspot_user_billings', 'username')
            && $this->hasColumn('hotspot_user_billings', 'package_id')
            && $this->hasTable('packages')
            && $this->hasColumn('packages', 'category')
        ) {
            try {
                $billingUsers = DB::table('hotspot_user_billings')
                    ->join('packages', 'packages.id', '=', 'hotspot_user_billings.package_id')
                    ->whereRaw('LOWER(packages.category) = ?', ['metered'])
                    ->whereNotNull('hotspot_user_billings.username')
                    ->pluck('hotspot_user_billings.username')
                    ->all();

                $usernames = $usernames->merge($billingUsers);
            } catch (\Throwable $e) {
                Log::warning('knownMeteredUsernames: billing lookup failed', ['error' => $e->getMessage()]);
            }
        }

        return $usernames
            ->map(fn($name) => trim((string)$name))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function resolveHotspotSessionInterface(RouterOSAPI $api, array $sessionData): string
    {
        $interface = trim((string)($sessionData['interface'] ?? ''));
        if ($interface !== '') {
            return $interface;
        }

        $server = trim((string)($sessionData['server'] ?? ''));
        if ($server === '') {
            return '';
        }

        try {
            $servers = $api->comm('/ip/hotspot/print', [
                '?name' => $server,
                '.proplist' => 'name,interface',
            ]) ?? [];
            $resolved = trim((string)(($servers[0] ?? [])['interface'] ?? ''));

            return $resolved;
        } catch (\Throwable $e) {
            Log::warning('resolveHotspotSessionInterface: hotspot lookup failed', [
                'server' => $server,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    private function findHotspotUserByUsername(RouterOSAPI $api, string $username): ?array
    {
        $users = $api->comm('/ip/hotspot/user/print') ?? [];

        foreach ($users as $user) {
            if (($user['name'] ?? $user['username'] ?? '') === $username) {
                return $user;
            }
        }

        return null;
    }

    private function buildUsageSummary(string $username, ?array $hotspotUser = null): array
    {
        if ($hotspotUser === null) {
            [$api, $error] = $this->connectApi();
            if (!$error) {
                $hotspotUser = $this->findHotspotUserByUsername($api, $username);
            }
        }

        $routerBytesIn = (int)($hotspotUser['bytes-in'] ?? 0);
        $routerBytesOut = (int)($hotspotUser['bytes-out'] ?? 0);
        $routerTotal = $routerBytesIn + $routerBytesOut;

        $dbBytesIn = 0;
        $dbBytesOut = 0;

        try {
            $dbBytesIn = (int)MeteredUsage::query()
                ->whereHas('subscription', function ($query) use ($username) {
                    $query->where('username', $username);
                })
                ->sum('bytes_in');

            $dbBytesOut = (int)MeteredUsage::query()
                ->whereHas('subscription', function ($query) use ($username) {
                    $query->where('username', $username);
                })
                ->sum('bytes_out');
        } catch (\Throwable $e) {
            Log::warning('Metered usage aggregation failed', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
        }

        $dbTotal = $dbBytesIn + $dbBytesOut;

        $totalBytes = $routerTotal > 0 ? $routerTotal : $dbTotal;

        return [
            'router_bytes_in' => $routerBytesIn,
            'router_bytes_out' => $routerBytesOut,
            'router_total_bytes' => $routerTotal,
            'db_bytes_in' => $dbBytesIn,
            'db_bytes_out' => $dbBytesOut,
            'db_total_bytes' => $dbTotal,
            'total_bytes' => $totalBytes,
            'total_gb' => round($totalBytes / self::BYTES_PER_GB, 4),
            'total_human' => $this->bytesToHuman($totalBytes),
        ];
    }

    private function bytesToHuman(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int)floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return number_format($value, $power === 0 ? 0 : 2) . ' ' . $units[$power];
    }

    private function generateInvoiceNumber(string $username): string
    {
        $slug = strtoupper(Str::of($username)->replace([' ', '/'], '_')->limit(14, ''));

        do {
            $invoiceNumber = sprintf(
                'HS-%s-%s-%s',
                $slug !== '' ? $slug : 'USER',
                now()->format('YmdHis'),
                strtoupper(Str::random(4))
            );
        } while (Invoice::query()->where('invoice_number', $invoiceNumber)->exists());

        return $invoiceNumber;
    }

    private function availablePlans()
    {
        $query = Package::query()->orderBy('name');

        if ($this->hasColumn('packages', 'status')) {
            $query->where('status', '!=', 'archived');
        } elseif ($this->hasColumn('packages', 'is_active')) {
            $query->where('is_active', 1);
        }

        $columns = ['id', 'name'];
        foreach (['price', 'speed', 'description', 'mk_profile', 'mikrotik_profile', 'category'] as $column) {
            if ($this->hasColumn('packages', $column)) {
                $columns[] = $column;
            }
        }

        return $query->get($columns)->map(function ($plan) {
            $profile = $plan->mk_profile
                ?? $plan->mikrotik_profile
                ?? null;

            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => (float)($plan->price ?? 0),
                'speed' => $plan->speed ?? null,
                'description' => $plan->description ?? null,
                'profile' => $profile,
                'category' => $plan->category ?? 'hotspot',
            ];
        })->values()->all();
    }

    private function availableProfileOptions($api = null, array $plans = []): array
    {
        $profiles = collect();

        if ($api) {
            try {
                $rows = $api->comm('/ip/hotspot/user/profile/print') ?? [];
                foreach ($rows as $row) {
                    $name = trim((string)($row['name'] ?? ''));
                    if ($name !== '') {
                        $profiles->push($name);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Failed loading hotspot profiles for customers form', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        foreach ($plans as $plan) {
            $profile = trim((string)(is_array($plan) ? ($plan['profile'] ?? '') : ($plan->profile ?? '')));
            if ($profile !== '') {
                $profiles->push($profile);
            }
        }

        $profiles->push('default');

        return $profiles
            ->map(fn($profile) => trim((string)$profile))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function resolvePackageProfile(int $packageId): ?string
    {
        $package = Package::find($packageId);
        if (!$package) {
            return null;
        }

        return $package->mk_profile
            ?? $package->mikrotik_profile
            ?? null;
    }

    private function applyPackageProfileToHotspotUser(string $username, int $packageId): void
    {
        $profile = $this->resolvePackageProfile($packageId);
        if (!$profile) {
            return;
        }

        try {
            [$api, $error] = $this->connectApi();
            if ($error) {
                return;
            }

            $user = $this->findHotspotUserByUsername($api, $username);
            if ($user && !empty($user['.id'])) {
                $api->comm('/ip/hotspot/user/set', [
                    '.id' => $user['.id'],
                    'profile' => $profile,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed applying package profile to hotspot user', [
                'username' => $username,
                'package_id' => $packageId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildPackageInfo(
        string $username,
        ?array $hotspotUser = null,
        ?array $activeSession = null,
        ?Customer $customer = null,
        ?HotspotUserBilling $billing = null
    ): array {
        $customer = $customer ?: Customer::query()->where('username', $username)->orderByDesc('id')->first();
        $billing = $billing ?: HotspotUserBilling::query()->where('username', $username)->first();
        $subscription = $this->resolveUserSubscription($username, $customer);

        $packageId = 0;
        if ($subscription && $this->hasColumn('subscriptions', 'package_id')) {
            $packageId = (int)($subscription->package_id ?? 0);
        }
        if ($packageId <= 0 && $this->hasColumn('hotspot_user_billings', 'package_id')) {
            $packageId = (int)($billing?->package_id ?? 0);
        }
        if ($packageId <= 0 && $this->hasColumn('customers', 'package_id')) {
            $packageId = (int)($customer?->package_id ?? 0);
        }

        $package = $packageId > 0 ? Package::find($packageId) : null;
        $connection = $this->resolveLatestConnection($username);

        $subscribedAt = null;
        if ($subscription && $this->hasColumn('subscriptions', 'starts_at') && $subscription->starts_at) {
            $subscribedAt = Carbon::parse($subscription->starts_at);
        } elseif ($connection && $this->hasColumn('connections', 'started_at') && $connection->started_at) {
            $subscribedAt = Carbon::parse($connection->started_at);
        }

        $expiresAt = null;
        if ($subscription && $this->hasColumn('subscriptions', 'expires_at') && $subscription->expires_at) {
            $expiresAt = Carbon::parse($subscription->expires_at);
        } elseif ($connection && $this->hasColumn('connections', 'expires_at') && $connection->expires_at) {
            $expiresAt = Carbon::parse($connection->expires_at);
        }

        $durationMinutes = $this->packageDurationMinutes($package);
        if ($durationMinutes <= 0 && $subscribedAt && $expiresAt && $expiresAt->greaterThan($subscribedAt)) {
            $durationMinutes = (int)$subscribedAt->diffInMinutes($expiresAt);
        }

        $uptimeRaw = trim((string)($activeSession['uptime'] ?? $hotspotUser['uptime'] ?? ''));
        $uptimeSeconds = $this->parseDurationToSeconds($uptimeRaw);
        $connectionStatus = strtolower(trim((string)($connection?->status ?? '')));
        $hasLiveConnection = $connection
            && $connectionStatus === 'active'
            && (!$connection->expires_at || Carbon::parse($connection->expires_at)->isFuture());

        if (
            ($uptimeRaw === '' || $uptimeRaw === '-' || ($uptimeSeconds <= 0 && !$activeSession))
            && $hasLiveConnection
            && $connection->started_at
        ) {
            $fallbackUptimeSeconds = max(0, Carbon::parse($connection->started_at)->diffInSeconds(now(), false));
            if ($fallbackUptimeSeconds > 0) {
                $uptimeSeconds = $fallbackUptimeSeconds;
                $uptimeRaw = $this->formatSecondsHuman($fallbackUptimeSeconds);
            }
        }

        $limitUptimeRaw = trim((string)($hotspotUser['limit-uptime'] ?? ''));
        $limitSeconds = $this->parseDurationToSeconds($limitUptimeRaw);

        $remainingSeconds = null;
        if ($expiresAt) {
            $remainingSeconds = now()->diffInSeconds($expiresAt, false);
        } elseif ($limitSeconds > 0 && $uptimeSeconds >= 0) {
            $remainingSeconds = $limitSeconds - $uptimeSeconds;
        }

        $remainingLabel = '-';
        if ($remainingSeconds !== null) {
            $remainingLabel = $remainingSeconds <= 0
                ? 'Expired'
                : $this->formatSecondsHuman($remainingSeconds);
        }

        $category = strtolower((string)($package?->category ?? 'hotspot')) === 'metered'
            ? 'metered'
            : 'hotspot';
        $type = $subscription && $this->hasColumn('subscriptions', 'type')
            ? strtolower((string)($subscription->type ?? $category))
            : $category;

        return [
            'package_id' => $package?->id,
            'package_name' => $package?->name ?? 'No package',
            'package_category' => $category,
            'package_price' => (float)($package?->price ?? 0),
            'package_profile' => (string)($package?->mk_profile ?? $package?->mikrotik_profile ?? $hotspotUser['profile'] ?? 'default'),
            'duration_minutes' => $durationMinutes > 0 ? $durationMinutes : null,
            'duration_label' => $durationMinutes > 0 ? $this->formatSecondsHuman($durationMinutes * 60) : '-',
            'subscribed_at' => $subscribedAt?->toDateTimeString(),
            'expires_at' => $expiresAt?->toDateTimeString(),
            'uptime' => $uptimeRaw !== '' ? $uptimeRaw : '-',
            'limit_uptime' => $limitUptimeRaw !== '' ? $limitUptimeRaw : '-',
            'time_remaining_seconds' => $remainingSeconds,
            'time_remaining' => $remainingLabel,
            'connection_status' => ($activeSession || $hasLiveConnection)
                ? 'online'
                : ((strtolower((string)($hotspotUser['disabled'] ?? 'false')) === 'true') ? 'disabled' : 'offline'),
            'subscription_id' => $subscription?->id,
            'subscription_type' => $type,
            'subscription_status' => $subscription && $this->hasColumn('subscriptions', 'status')
                ? (string)($subscription->status ?? 'active')
                : ($remainingSeconds !== null && $remainingSeconds <= 0 ? 'expired' : 'active'),
        ];
    }

    private function resolveDetailMode(?array $user, array $packageInfo): string
    {
        $accountType = strtolower(trim((string)($user['account_type'] ?? '')));
        if ($accountType === 'metered_static') {
            return 'metered';
        }

        $subscriptionType = strtolower(trim((string)($packageInfo['subscription_type'] ?? '')));
        if ($subscriptionType === 'metered') {
            return 'metered';
        }

        $category = strtolower(trim((string)($packageInfo['package_category'] ?? 'hotspot')));
        return $category === 'metered' ? 'metered' : 'hotspot';
    }

    private function buildHotspotInsights(
        string $username,
        ?array $hotspotUser = null,
        ?array $activeSession = null,
        ?Customer $customer = null,
        iterable $plans = [],
        array $packageInfo = []
    ): array {
        $now = now();
        $usernameKey = strtolower(trim($username));
        $mac = strtolower(trim((string)($hotspotUser['mac-address'] ?? $hotspotUser['mac'] ?? '')));

        $planMap = collect($plans)
            ->mapWithKeys(function ($plan) {
                $id = (int)(is_array($plan) ? ($plan['id'] ?? 0) : ($plan->id ?? 0));
                return $id > 0 ? [$id => $plan] : [];
            });

        $packageCache = [];
        $packageDataForId = function (int $packageId) use ($planMap, &$packageCache): ?array {
            if ($packageId <= 0) {
                return null;
            }
            if (array_key_exists($packageId, $packageCache)) {
                return $packageCache[$packageId];
            }

            $data = null;
            $plan = $planMap->get($packageId);
            if (is_array($plan)) {
                $data = [
                    'id' => $packageId,
                    'name' => (string)($plan['name'] ?? 'Package'),
                    'price' => (float)($plan['price'] ?? 0),
                    'category' => strtolower((string)($plan['category'] ?? 'hotspot')) === 'metered' ? 'metered' : 'hotspot',
                    'duration_minutes' => (int)($plan['duration_minutes'] ?? 0),
                    'duration_label' => (string)($plan['duration_label'] ?? '-'),
                ];
            } elseif (is_object($plan)) {
                $durationMinutes = (int)($plan->duration_minutes ?? 0);
                $data = [
                    'id' => $packageId,
                    'name' => (string)($plan->name ?? 'Package'),
                    'price' => (float)($plan->price ?? 0),
                    'category' => strtolower((string)($plan->category ?? 'hotspot')) === 'metered' ? 'metered' : 'hotspot',
                    'duration_minutes' => $durationMinutes,
                    'duration_label' => $durationMinutes > 0 ? $this->formatSecondsHuman($durationMinutes * 60) : '-',
                ];
            }

            if (!$data) {
                $package = Package::find($packageId);
                if ($package) {
                    $durationMinutes = $this->packageDurationMinutes($package);
                    $data = [
                        'id' => $packageId,
                        'name' => (string)($package->name ?? 'Package'),
                        'price' => (float)($package->price ?? 0),
                        'category' => strtolower((string)($package->category ?? 'hotspot')) === 'metered' ? 'metered' : 'hotspot',
                        'duration_minutes' => $durationMinutes,
                        'duration_label' => $durationMinutes > 0 ? $this->formatSecondsHuman($durationMinutes * 60) : '-',
                    ];
                }
            }

            $packageCache[$packageId] = $data;
            return $data;
        };

        $resolveConnectionEndedAt = function (Connection $connection) use ($now) {
            if ($this->hasColumn('connections', 'ended_at') && $connection->ended_at) {
                return Carbon::parse($connection->ended_at);
            }

            $status = strtolower(trim((string)($connection->status ?? '')));
            $updatedAt = $connection->updated_at ? Carbon::parse($connection->updated_at) : null;
            $expiresAt = $connection->expires_at ? Carbon::parse($connection->expires_at) : null;

            if (in_array($status, ['terminated', 'expired', 'inactive', 'ended'], true)) {
                return $updatedAt ?: $expiresAt;
            }

            if ($expiresAt && $expiresAt->isPast()) {
                return $expiresAt;
            }

            return null;
        };

        $resolveConnectionUsage = function (Connection $connection) use ($hotspotUser, $activeSession): array {
            $bytesIn = $this->hasColumn('connections', 'bytes_in')
                ? max(0, (int)($connection->bytes_in ?? 0))
                : 0;
            $bytesOut = $this->hasColumn('connections', 'bytes_out')
                ? max(0, (int)($connection->bytes_out ?? 0))
                : 0;
            $status = strtolower(trim((string)($connection->status ?? '')));

            if ($status === 'active' && $hotspotUser) {
                $startBytesIn = $this->hasColumn('connections', 'start_bytes_in')
                    ? max(0, (int)($connection->start_bytes_in ?? 0))
                    : 0;
                $startBytesOut = $this->hasColumn('connections', 'start_bytes_out')
                    ? max(0, (int)($connection->start_bytes_out ?? 0))
                    : 0;

                $rawBytesIn = max(0, (int)($hotspotUser['bytes-in'] ?? 0));
                $rawBytesOut = max(0, (int)($hotspotUser['bytes-out'] ?? 0));
                $bytesIn = max($bytesIn, $rawBytesIn - $startBytesIn);
                $bytesOut = max($bytesOut, $rawBytesOut - $startBytesOut);
            }

            if ($status === 'active' && $activeSession) {
                $bytesIn = max($bytesIn, max(0, (int)($activeSession['bytes-in'] ?? 0)));
                $bytesOut = max($bytesOut, max(0, (int)($activeSession['bytes-out'] ?? 0)));
            }

            $total = $bytesIn + $bytesOut;

            return [
                'bytes_in' => $bytesIn,
                'bytes_out' => $bytesOut,
                'total' => $total,
                'total_human' => $this->bytesToHuman($total),
            ];
        };

        $subscriptions = collect();
        if ($this->hasTable('subscriptions')) {
            $subscriptionQuery = Subscription::query();
            $hasMatch = false;

            if ($this->hasColumn('subscriptions', 'username')) {
                $subscriptionQuery->where('username', $username);
                $hasMatch = true;
            } elseif ($customer?->id && $this->hasColumn('subscriptions', 'customer_id')) {
                $subscriptionQuery->where('customer_id', $customer->id);
                $hasMatch = true;
            }

            if ($hasMatch) {
                if ($this->hasColumn('subscriptions', 'type')) {
                    $subscriptionQuery->whereRaw('LOWER(type) = ?', ['hotspot']);
                }
                if ($this->hasColumn('subscriptions', 'starts_at')) {
                    $subscriptionQuery->orderByDesc('starts_at');
                }
                $subscriptionQuery->orderByDesc('id');

                $subscriptionColumns = ['id'];
                foreach (['package_id', 'status', 'type', 'price_paid', 'starts_at', 'expires_at', 'created_at'] as $column) {
                    if ($this->hasColumn('subscriptions', $column)) {
                        $subscriptionColumns[] = $column;
                    }
                }

                $subscriptions = $subscriptionQuery->limit(40)->get($subscriptionColumns);
            }
        }

        $subscriptionHistory = $subscriptions->map(function (Subscription $subscription) use ($packageDataForId, $now) {
            $packageId = (int)($subscription->package_id ?? 0);
            $packageData = $packageId > 0 ? $packageDataForId($packageId) : null;

            $startsAt = $subscription->starts_at ? Carbon::parse($subscription->starts_at) : null;
            $expiresAt = $subscription->expires_at ? Carbon::parse($subscription->expires_at) : null;
            $status = strtolower(trim((string)($subscription->status ?? 'active')));

            $durationMinutes = (int)($packageData['duration_minutes'] ?? 0);
            if ($durationMinutes <= 0 && $startsAt && $expiresAt && $expiresAt->greaterThan($startsAt)) {
                $durationMinutes = (int)$startsAt->diffInMinutes($expiresAt);
            }

            $remainingSeconds = $expiresAt ? $now->diffInSeconds($expiresAt, false) : null;
            $isExpired = $remainingSeconds !== null ? $remainingSeconds <= 0 : $status === 'expired';
            $isActive = $status === 'active' && !$isExpired;

            $remainingLabel = '-';
            if ($remainingSeconds !== null) {
                $remainingLabel = $remainingSeconds <= 0
                    ? 'Expired'
                    : $this->formatSecondsHuman($remainingSeconds);
            } elseif ($isActive) {
                $remainingLabel = 'Active';
            }

            return [
                'id' => (int)$subscription->id,
                'source' => 'subscription',
                'package_id' => $packageId > 0 ? $packageId : null,
                'package_name' => (string)($packageData['name'] ?? 'Package'),
                'price_paid' => (float)($subscription->price_paid ?? ($packageData['price'] ?? 0)),
                'type' => strtolower((string)($subscription->type ?? 'hotspot')),
                'status' => $status !== '' ? $status : 'active',
                'starts_at' => $startsAt?->toDateTimeString(),
                'expires_at' => $expiresAt?->toDateTimeString(),
                'duration_minutes' => $durationMinutes > 0 ? $durationMinutes : null,
                'duration_label' => $durationMinutes > 0 ? $this->formatSecondsHuman($durationMinutes * 60) : '-',
                'time_remaining' => $remainingLabel,
                'is_active' => $isActive,
                'is_expired' => $isExpired,
                'eligible_extension' => $packageId > 0 && (($remainingSeconds !== null && $remainingSeconds > 0) || ($remainingSeconds === null && $isActive)),
            ];
        })->values();

        $connectionRows = collect();
        $connectionIds = [];
        $totalOnlineSeconds = 0;
        $latestSeenFromConnections = null;

        if ($this->hasTable('connections') && $this->hasColumn('connections', 'id')) {
            $connectionQuery = Connection::query();
            $hasConnectionMatch = false;

            if ($this->hasColumn('connections', 'username') && $username !== '') {
                $connectionQuery->where('username', $username);
                $hasConnectionMatch = true;
            }

            if ($this->hasColumn('connections', 'mac_address') && $mac !== '') {
                if ($hasConnectionMatch) {
                    $connectionQuery->orWhere('mac_address', $mac);
                } else {
                    $connectionQuery->where('mac_address', $mac);
                    $hasConnectionMatch = true;
                }
            }

            if ($hasConnectionMatch) {
                if ($this->hasColumn('connections', 'started_at')) {
                    $connectionQuery->orderByDesc('started_at');
                }
                $connectionQuery->orderByDesc('id');

                $connectionColumns = ['id'];
                foreach ([
                    'package_id',
                    'status',
                    'started_at',
                    'ended_at',
                    'expires_at',
                    'updated_at',
                    'created_at',
                    'ip_address',
                    'mac_address',
                    'bytes_in',
                    'bytes_out',
                    'start_bytes_in',
                    'start_bytes_out',
                ] as $column) {
                    if ($this->hasColumn('connections', $column)) {
                        $connectionColumns[] = $column;
                    }
                }

                $connectionRows = $connectionQuery->limit(200)->get(array_values(array_unique($connectionColumns)));
                $connectionIds = $connectionRows
                    ->pluck('id')
                    ->map(fn($id) => (int)$id)
                    ->filter(fn(int $id) => $id > 0)
                    ->values()
                    ->all();

                foreach ($connectionRows as $connection) {
                    $startedAt = $connection->started_at ? Carbon::parse($connection->started_at) : ($connection->created_at ? Carbon::parse($connection->created_at) : null);
                    $endedAt = $resolveConnectionEndedAt($connection);

                    if ($startedAt) {
                        if ($endedAt) {
                            $endAt = $endedAt->greaterThan($startedAt) ? $endedAt : $startedAt;
                        } else {
                            $endAt = $now;
                        }
                        $totalOnlineSeconds += max(0, $startedAt->diffInSeconds($endAt, false));
                    }

                    $expiresAt = $connection->expires_at ? Carbon::parse($connection->expires_at) : null;
                    $updatedAt = $connection->updated_at ? Carbon::parse($connection->updated_at) : null;
                    $candidateSeen = $endedAt ?: $updatedAt ?: $expiresAt ?: $startedAt;
                    if ($candidateSeen && (!$latestSeenFromConnections || $candidateSeen->greaterThan($latestSeenFromConnections))) {
                        $latestSeenFromConnections = $candidateSeen->copy();
                    }
                }
            }
        }

        $connectionIdIndex = array_fill_keys($connectionIds, true);

        $connectionHistory = $connectionRows
            ->map(function (Connection $connection) use ($packageDataForId, $now, $resolveConnectionEndedAt, $resolveConnectionUsage) {
                $packageId = (int)($connection->package_id ?? 0);
                $packageData = $packageId > 0 ? $packageDataForId($packageId) : null;

                $startsAt = $connection->started_at ? Carbon::parse($connection->started_at) : ($connection->created_at ? Carbon::parse($connection->created_at) : null);
                $expiresAt = $connection->expires_at ? Carbon::parse($connection->expires_at) : null;
                $endedAt = $resolveConnectionEndedAt($connection);
                $status = strtolower(trim((string)($connection->status ?? ($expiresAt && $expiresAt->isPast() ? 'expired' : 'active'))));
                if ($status === '') {
                    $status = 'active';
                }

                $durationMinutes = (int)($packageData['duration_minutes'] ?? 0);
                if ($durationMinutes <= 0 && $startsAt && $expiresAt && $expiresAt->greaterThan($startsAt)) {
                    $durationMinutes = (int)$startsAt->diffInMinutes($expiresAt);
                }

                $remainingSeconds = $expiresAt ? $now->diffInSeconds($expiresAt, false) : null;
                $isExpired = $remainingSeconds !== null ? $remainingSeconds <= 0 : $status === 'expired';
                $isActive = !$isExpired && $status === 'active';

                $remainingLabel = '-';
                if ($endedAt || $status === 'terminated') {
                    $remainingLabel = 'Ended';
                } elseif ($remainingSeconds !== null) {
                    $remainingLabel = $remainingSeconds <= 0
                        ? 'Expired'
                        : $this->formatSecondsHuman($remainingSeconds);
                } elseif ($isActive) {
                    $remainingLabel = 'Active';
                }

                $onlineSeconds = null;
                if ($startsAt) {
                    $endAt = $endedAt ?: ($isActive ? $now : ($expiresAt ?: $now));
                    if ($endAt->lessThan($startsAt)) {
                        $endAt = $startsAt;
                    }
                    $onlineSeconds = max(0, $startsAt->diffInSeconds($endAt, false));
                }

                $usage = $resolveConnectionUsage($connection);

                return [
                    'id' => (int)$connection->id,
                    'source' => 'connection',
                    'connection_id' => (int)$connection->id,
                    'package_id' => $packageId > 0 ? $packageId : null,
                    'package_name' => (string)($packageData['name'] ?? 'Package'),
                    'price_paid' => (float)($packageData['price'] ?? 0),
                    'type' => 'hotspot',
                    'status' => $status,
                    'starts_at' => $startsAt?->toDateTimeString(),
                    'ended_at' => $endedAt?->toDateTimeString(),
                    'expires_at' => $expiresAt?->toDateTimeString(),
                    'ip_address' => (string)($connection->ip_address ?? ''),
                    'mac_address' => (string)($connection->mac_address ?? ''),
                    'duration_minutes' => $durationMinutes > 0 ? $durationMinutes : null,
                    'duration_label' => $durationMinutes > 0 ? $this->formatSecondsHuman($durationMinutes * 60) : '-',
                    'time_remaining' => $remainingLabel,
                    'is_active' => $isActive,
                    'is_expired' => $isExpired,
                    'eligible_extension' => $packageId > 0 && (($remainingSeconds !== null && $remainingSeconds > 0) || ($remainingSeconds === null && $isActive)),
                    'online_seconds' => $onlineSeconds,
                    'online_duration' => $onlineSeconds !== null ? $this->formatSecondsHuman($onlineSeconds) : '-',
                    'usage_bytes_in' => $usage['bytes_in'],
                    'usage_bytes_out' => $usage['bytes_out'],
                    'usage_total_bytes' => $usage['total'],
                    'usage_total' => $usage['total_human'],
                ];
            })
            ->values();

        $transactions = collect();
        if ($this->hasTable('megapayments')) {
            $transactionQuery = MegaPayment::query()->orderByDesc('id');

            if ($customer?->id && $this->hasColumn('megapayments', 'customer_id')) {
                $transactionQuery->where(function ($query) use ($customer) {
                    $query->where('customer_id', $customer->id);
                    if ($this->hasColumn('megapayments', 'purpose')) {
                        $query->orWhere('purpose', 'hotspot_access');
                    }
                });
            } elseif ($this->hasColumn('megapayments', 'purpose')) {
                $transactionQuery->where('purpose', 'hotspot_access');
            }

            $transactionColumns = ['id'];
            foreach ([
                'reference',
                'purpose',
                'channel',
                'payable_type',
                'payable_id',
                'customer_id',
                'msisdn',
                'amount',
                'status',
                'response_description',
                'mpesa_receipt',
                'transaction_id',
                'meta',
                'initiated_at',
                'completed_at',
                'failed_at',
                'created_at',
            ] as $column) {
                if ($this->hasColumn('megapayments', $column)) {
                    $transactionColumns[] = $column;
                }
            }

            $candidates = $transactionQuery->limit($customer?->id ? 300 : 500)->get($transactionColumns);

            $transactions = $candidates
                ->filter(function (MegaPayment $payment) use ($customer, $usernameKey, $mac, $connectionIdIndex) {
                    $meta = is_array($payment->meta) ? $payment->meta : [];
                    $purpose = strtolower((string)($payment->purpose ?? ''));
                    $channel = strtolower((string)($payment->channel ?? ''));
                    $flow = strtolower((string)($meta['flow'] ?? ''));
                    $payableType = (string)($payment->payable_type ?? '');

                    $isHotspotFlow = $purpose === 'hotspot_access'
                        || $channel === 'portal_connect'
                        || $flow === 'connect.hotspot'
                        || $payableType === Connection::class
                        || $payableType === Package::class;

                    if (!$isHotspotFlow) {
                        return false;
                    }

                    if (
                        $customer?->id
                        && $this->hasColumn('megapayments', 'customer_id')
                        && (int)($payment->customer_id ?? 0) === (int)$customer->id
                    ) {
                        return true;
                    }

                    $metaUsername = strtolower(trim((string)($meta['username'] ?? '')));
                    $metaMac = strtolower(trim((string)($meta['mac'] ?? $meta['mac_address'] ?? '')));

                    if ($usernameKey !== '' && $metaUsername !== '' && $metaUsername === $usernameKey) {
                        return true;
                    }

                    if ($mac !== '' && $metaMac !== '' && $metaMac === $mac) {
                        return true;
                    }

                    if (
                        $payableType === Connection::class
                        && $this->hasColumn('megapayments', 'payable_id')
                        && isset($connectionIdIndex[(int)($payment->payable_id ?? 0)])
                    ) {
                        return true;
                    }

                    return false;
                })
                ->take(60)
                ->map(function (MegaPayment $payment) use ($packageDataForId) {
                    $meta = is_array($payment->meta) ? $payment->meta : [];
                    $status = strtolower((string)($payment->status ?? 'pending'));

                    $packageId = (int)($meta['package_id'] ?? 0);
                    if ($packageId <= 0 && (string)($payment->payable_type ?? '') === Package::class) {
                        $packageId = (int)($payment->payable_id ?? 0);
                    }
                    $packageData = $packageId > 0 ? $packageDataForId($packageId) : null;

                    $attemptedAt = $payment->initiated_at ?: $payment->created_at;
                    $paidAt = $payment->completed_at ?: ($status === 'completed' ? $attemptedAt : null);

                    return [
                        'id' => (int)$payment->id,
                        'reference' => (string)($payment->reference ?? ''),
                        'status' => $status !== '' ? $status : 'pending',
                        'amount' => (float)($payment->amount ?? 0),
                        'currency' => 'KES',
                        'method' => 'mpesa',
                        'msisdn' => (string)($payment->msisdn ?? ''),
                        'package_id' => $packageId > 0 ? $packageId : null,
                        'package_name' => (string)($meta['package_name'] ?? ($packageData['name'] ?? 'Package')),
                        'duration_label' => (string)($meta['time_label'] ?? ($packageData['duration_label'] ?? '-')),
                        'channel' => (string)($payment->channel ?? 'portal_connect'),
                        'receipt' => (string)($payment->mpesa_receipt ?? $payment->transaction_id ?? ''),
                        'transaction_code' => (string)($payment->mpesa_receipt ?? $payment->transaction_id ?? ''),
                        'response_description' => (string)($payment->response_description ?? ''),
                        'attempted_at' => $attemptedAt?->toDateTimeString(),
                        'paid_at' => $paidAt?->toDateTimeString(),
                        'created_at' => optional($payment->created_at)->toDateTimeString(),
                    ];
                })
                ->values();
        }

        $paymentDerivedHistory = $transactions
            ->filter(function (array $txn) {
                return ($txn['status'] ?? '') === 'completed' && (int)($txn['package_id'] ?? 0) > 0;
            })
            ->map(function (array $txn) use ($packageDataForId, $now) {
                $packageId = (int)($txn['package_id'] ?? 0);
                $packageData = $packageId > 0 ? $packageDataForId($packageId) : null;

                $startsAtRaw = $txn['paid_at'] ?? $txn['attempted_at'] ?? $txn['created_at'] ?? null;
                $startsAt = $startsAtRaw ? Carbon::parse($startsAtRaw) : null;

                $durationMinutes = (int)($packageData['duration_minutes'] ?? 0);
                if ($durationMinutes <= 0) {
                    $durationSeconds = $this->parseDurationToSeconds((string)($txn['duration_label'] ?? ''));
                    if ($durationSeconds > 0) {
                        $durationMinutes = (int)ceil($durationSeconds / 60);
                    }
                }

                $expiresAt = ($startsAt && $durationMinutes > 0) ? $startsAt->copy()->addMinutes($durationMinutes) : null;
                $remainingSeconds = $expiresAt ? $now->diffInSeconds($expiresAt, false) : null;
                $isExpired = $remainingSeconds !== null ? $remainingSeconds <= 0 : false;
                $isActive = !$isExpired && $expiresAt !== null;

                $remainingLabel = '-';
                if ($remainingSeconds !== null) {
                    $remainingLabel = $remainingSeconds <= 0
                        ? 'Expired'
                        : $this->formatSecondsHuman($remainingSeconds);
                }

                return [
                    'id' => (int)($txn['id'] ?? 0),
                    'source' => 'payment',
                    'package_id' => $packageId,
                    'package_name' => (string)($txn['package_name'] ?? ($packageData['name'] ?? 'Package')),
                    'price_paid' => (float)($txn['amount'] ?? ($packageData['price'] ?? 0)),
                    'type' => 'hotspot',
                    'status' => $isExpired ? 'expired' : ($isActive ? 'active' : 'completed'),
                    'starts_at' => $startsAt?->toDateTimeString(),
                    'expires_at' => $expiresAt?->toDateTimeString(),
                    'duration_minutes' => $durationMinutes > 0 ? $durationMinutes : null,
                    'duration_label' => $durationMinutes > 0 ? $this->formatSecondsHuman($durationMinutes * 60) : ((string)($txn['duration_label'] ?? '-') ?: '-'),
                    'time_remaining' => $remainingLabel,
                    'is_active' => $isActive,
                    'is_expired' => $isExpired,
                    'eligible_extension' => $packageId > 0 && $remainingSeconds !== null && $remainingSeconds > 0,
                    'transaction_reference' => (string)($txn['reference'] ?? ''),
                ];
            })
            ->values();

        $displayHistory = $subscriptionHistory
            ->concat($paymentDerivedHistory)
            ->sortByDesc(function (array $row) {
                $sortAt = (string)($row['starts_at'] ?? $row['created_at'] ?? $row['expires_at'] ?? '');
                return $sortAt !== '' ? (strtotime($sortAt) ?: 0) : 0;
            })
            ->values();

        $seenHistory = [];
        $displayHistory = $displayHistory
            ->filter(function (array $row) use (&$seenHistory) {
                $packageId = (int)($row['package_id'] ?? 0);
                $startKey = substr((string)($row['starts_at'] ?? ''), 0, 16);
                $expiryKey = substr((string)($row['expires_at'] ?? ''), 0, 16);
                $statusKey = (string)($row['status'] ?? '');
                $source = (string)($row['source'] ?? '');
                $fallbackId = (string)($row['id'] ?? '');

                $key = $packageId . '|' . $startKey . '|' . $expiryKey . '|' . $statusKey;
                if ($key === '0||||') {
                    $key = $source . '|' . $fallbackId;
                }

                if (isset($seenHistory[$key])) {
                    return false;
                }
                $seenHistory[$key] = true;
                return true;
            })
            ->values();

        $activeSubscription = $displayHistory->first(function (array $subscription) {
            return (bool)($subscription['is_active'] ?? false) && !empty($subscription['package_id']);
        }) ?: $connectionHistory->first(function (array $session) {
            return (bool)($session['is_active'] ?? false) && !empty($session['package_id']);
        });

        $eligiblePackageIds = $displayHistory
            ->filter(fn(array $subscription) => !empty($subscription['package_id']) && (bool)($subscription['eligible_extension'] ?? false))
            ->pluck('package_id')
            ->merge(
                $connectionHistory
                    ->filter(fn(array $session) => !empty($session['package_id']) && (bool)($session['eligible_extension'] ?? false))
                    ->pluck('package_id')
                    ->all()
            )
            ->map(fn($id) => (int)$id)
            ->filter(fn(int $id) => $id > 0)
            ->unique()
            ->values();

        $previousPackageIds = collect()
            ->merge($displayHistory->pluck('package_id')->all())
            ->merge(
                $transactions
                    ->filter(fn(array $txn) => ($txn['status'] ?? '') === 'completed')
                    ->pluck('package_id')
                    ->all()
            )
            ->merge($connectionHistory->pluck('package_id')->all())
            ->merge([(int)($packageInfo['package_id'] ?? 0)])
            ->map(fn($id) => (int)$id)
            ->filter(fn(int $id) => $id > 0)
            ->unique()
            ->values();

        $hotspotPlanIds = collect($plans)
            ->filter(function ($plan) {
                $category = strtolower((string)(is_array($plan) ? ($plan['category'] ?? 'hotspot') : ($plan->category ?? 'hotspot')));
                return $category !== 'metered';
            })
            ->map(fn($plan) => (int)(is_array($plan) ? ($plan['id'] ?? 0) : ($plan->id ?? 0)))
            ->map(fn($id) => (int)$id)
            ->filter(fn(int $id) => $id > 0)
            ->unique()
            ->values();

        $defaultPackageId = (int)($activeSubscription['package_id'] ?? ($packageInfo['package_id'] ?? 0));
        if ($defaultPackageId <= 0) {
            $defaultPackageId = (int)($eligiblePackageIds->first() ?? 0);
        }
        if ($defaultPackageId <= 0) {
            $defaultPackageId = (int)($previousPackageIds->first() ?? 0);
        }

        if ($defaultPackageId > 0 && !$eligiblePackageIds->contains($defaultPackageId)) {
            $eligiblePackageIds = $eligiblePackageIds->prepend($defaultPackageId)->unique()->values();
        }

        $eligiblePackageMap = array_fill_keys($eligiblePackageIds->all(), true);

        $extensionOptions = $eligiblePackageIds
            ->map(function (int $packageId) use ($packageDataForId) {
                $packageData = $packageDataForId($packageId);
                if (!$packageData) {
                    return null;
                }

                $durationMinutes = (int)($packageData['duration_minutes'] ?? 0);
                return [
                    'id' => $packageId,
                    'name' => (string)($packageData['name'] ?? 'Package'),
                    'price' => (float)($packageData['price'] ?? 0),
                    'category' => (string)($packageData['category'] ?? 'hotspot'),
                    'duration_minutes' => $durationMinutes > 0 ? $durationMinutes : null,
                    'duration_label' => $durationMinutes > 0 ? $this->formatSecondsHuman($durationMinutes * 60) : '-',
                    'force_only' => false,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $forcePackageIds = $previousPackageIds
            ->merge($hotspotPlanIds)
            ->unique()
            ->values();

        if ($defaultPackageId > 0 && !$forcePackageIds->contains($defaultPackageId)) {
            $forcePackageIds = $forcePackageIds->prepend($defaultPackageId)->unique()->values();
        }

        $forceExtensionOptions = $forcePackageIds
            ->map(function (int $packageId) use ($packageDataForId, $eligiblePackageMap) {
                $packageData = $packageDataForId($packageId);
                if (!$packageData) {
                    return null;
                }

                $durationMinutes = (int)($packageData['duration_minutes'] ?? 0);
                return [
                    'id' => $packageId,
                    'name' => (string)($packageData['name'] ?? 'Package'),
                    'price' => (float)($packageData['price'] ?? 0),
                    'category' => (string)($packageData['category'] ?? 'hotspot'),
                    'duration_minutes' => $durationMinutes > 0 ? $durationMinutes : null,
                    'duration_label' => $durationMinutes > 0 ? $this->formatSecondsHuman($durationMinutes * 60) : '-',
                    'force_only' => !isset($eligiblePackageMap[$packageId]),
                ];
            })
            ->filter()
            ->values()
            ->all();

        $recentPayments = $transactions
            ->filter(fn(array $txn) => ($txn['status'] ?? '') === 'completed')
            ->map(function (array $txn) {
                return [
                    'amount' => (float)($txn['amount'] ?? 0),
                    'currency' => (string)($txn['currency'] ?? 'KES'),
                    'method' => (string)($txn['method'] ?? 'mpesa'),
                    'transaction_code' => (string)($txn['transaction_code'] ?? ''),
                    'reference' => (string)($txn['reference'] ?? ''),
                    'status' => 'completed',
                    'paid_at' => $txn['paid_at'] ?? $txn['attempted_at'] ?? null,
                    'created_at' => $txn['created_at'] ?? $txn['attempted_at'] ?? null,
                ];
            })
            ->take(10)
            ->values()
            ->all();

        $routerLastSeenRaw = trim((string)($hotspotUser['last-seen'] ?? ''));
        $routerLastSeenAt = null;
        if ($routerLastSeenRaw !== '') {
            try {
                $routerLastSeenAt = Carbon::parse($routerLastSeenRaw);
            } catch (\Throwable) {
                $routerLastSeenAt = null;
            }
        }

        $resolvedLastSeenAt = $routerLastSeenAt;
        if ($latestSeenFromConnections && (!$resolvedLastSeenAt || $latestSeenFromConnections->greaterThan($resolvedLastSeenAt))) {
            $resolvedLastSeenAt = $latestSeenFromConnections->copy();
        }

        $lastSeenLabel = $routerLastSeenRaw !== ''
            ? $routerLastSeenRaw
            : ($resolvedLastSeenAt?->toDateTimeString() ?? '-');

        return [
            'subscriptions' => $displayHistory->all(),
            'sessions' => $connectionHistory->all(),
            'transactions' => $transactions->all(),
            'recent_payments' => $recentPayments,
            'summary' => [
                'last_seen' => $lastSeenLabel,
                'last_seen_at' => $resolvedLastSeenAt?->toDateTimeString(),
                'total_online_seconds' => max(0, (int)$totalOnlineSeconds),
                'total_online' => $totalOnlineSeconds > 0 ? $this->formatSecondsHuman($totalOnlineSeconds) : '-',
            ],
            'extension' => [
                'default_package_id' => $defaultPackageId > 0 ? $defaultPackageId : null,
                'eligible_package_ids' => $eligiblePackageIds->all(),
                'previous_package_ids' => $previousPackageIds->all(),
                'force_allowed' => true,
            ],
            'extension_options' => $extensionOptions,
            'force_extension_options' => $forceExtensionOptions,
        ];
    }

    private function resolveUserSubscription(string $username, ?Customer $customer = null): ?Subscription
    {
        if (!$this->hasTable('subscriptions')) {
            return null;
        }

        $query = Subscription::query();
        $hasMatch = false;

        if ($this->hasColumn('subscriptions', 'username')) {
            $query->where('username', $username);
            $hasMatch = true;
        } elseif ($customer?->id && $this->hasColumn('subscriptions', 'customer_id')) {
            $query->where('customer_id', $customer->id);
            $hasMatch = true;
        }

        if (!$hasMatch) {
            return null;
        }

        if ($this->hasColumn('subscriptions', 'status')) {
            $query->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END");
        }
        if ($this->hasColumn('subscriptions', 'starts_at')) {
            $query->orderByDesc('starts_at');
        }

        return $query->orderByDesc('id')->first();
    }

    private function resolveLatestConnection(string $username): ?Connection
    {
        if (
            !$this->hasTable('connections')
            || !$this->hasColumn('connections', 'username')
        ) {
            return null;
        }

        $query = Connection::query()->where('username', $username);
        if ($this->hasColumn('connections', 'status')) {
            $query->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END");
        }
        if ($this->hasColumn('connections', 'started_at')) {
            $query->orderByDesc('started_at');
        }

        return $query->orderByDesc('id')->first();
    }

    private function packageDurationMinutes(?Package $package): int
    {
        if (!$package) {
            return 0;
        }

        $minutes = (int)($package->duration_minutes ?? 0);
        if ($minutes > 0) {
            return $minutes;
        }

        $hours = (int)($package->duration ?? 0);
        if ($hours > 0) {
            return $hours * 60;
        }

        return 0;
    }

    private function parseDurationToSeconds(?string $value): int
    {
        $raw = strtolower(trim((string)$value));
        if ($raw === '') {
            return 0;
        }

        $total = 0;
        if (preg_match_all('/(\d+)\s*([wdhms])/', $raw, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $count = (int)$match[1];
                $unit = $match[2];
                $multiplier = match ($unit) {
                    'w' => 7 * 24 * 3600,
                    'd' => 24 * 3600,
                    'h' => 3600,
                    'm' => 60,
                    default => 1,
                };
                $total += $count * $multiplier;
            }

            return $total;
        }

        if (preg_match('/^\d+:\d{1,2}:\d{1,2}$/', $raw)) {
            [$h, $m, $s] = array_map('intval', explode(':', $raw));
            return ($h * 3600) + ($m * 60) + $s;
        }
        if (preg_match('/^\d+:\d{1,2}$/', $raw)) {
            [$m, $s] = array_map('intval', explode(':', $raw));
            return ($m * 60) + $s;
        }

        return (int)$raw;
    }

    private function formatSecondsHuman(int $seconds): string
    {
        $secs = max(0, $seconds);
        if ($secs === 0) {
            return '0m';
        }

        $days = intdiv($secs, 86400);
        $secs %= 86400;
        $hours = intdiv($secs, 3600);
        $secs %= 3600;
        $mins = intdiv($secs, 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . 'd';
        }
        if ($hours > 0) {
            $parts[] = $hours . 'h';
        }
        if ($mins > 0 || empty($parts)) {
            $parts[] = $mins . 'm';
        }

        return implode(' ', $parts);
    }

    public function publicStatus(string $token)
    {
        $username = $this->decodeCustomerStatusToken($token);
        if (!$username) {
            abort(404);
        }

        $customer = Customer::query()
            ->where('username', $username)
            ->orderByDesc('id')
            ->first();
        if (!$customer) {
            abort(404);
        }

        $billing = HotspotUserBilling::query()->where('username', $username)->first();
        $packageId = null;
        if ($this->hasColumn('hotspot_user_billings', 'package_id')) {
            $packageId = $billing?->package_id;
        }
        if (!$packageId && $this->hasColumn('customers', 'package_id')) {
            $packageId = $customer->package_id;
        }
        $package = $packageId ? Package::find($packageId) : null;

        $invoiceSelect = ['id', 'invoice_number', 'amount', 'status', 'created_at'];
        foreach ([
            'invoice_status',
            'total_amount',
            'paid_amount',
            'balance_amount',
            'currency',
            'due_date',
            'issued_at',
            'notes',
        ] as $column) {
            if ($this->hasColumn('invoices', $column)) {
                $invoiceSelect[] = $column;
            }
        }

        $invoices = Invoice::query()
            ->where('customer_id', $customer->id)
            ->orderByDesc('id')
            ->limit(20)
            ->get($invoiceSelect)
            ->map(function (Invoice $invoice) {
                try {
                    return app(InvoiceBillingService::class)->recalculate($invoice);
                } catch (\Throwable) {
                    return $invoice;
                }
            });

        $sumAmountCol = $this->hasColumn('invoices', 'total_amount') ? 'total_amount' : 'amount';
        $sumPaidCol = $this->hasColumn('invoices', 'paid_amount') ? 'paid_amount' : null;
        $sumBalanceCol = $this->hasColumn('invoices', 'balance_amount') ? 'balance_amount' : null;
        $statusCol = $this->hasColumn('invoices', 'invoice_status')
            ? 'invoice_status'
            : ($this->hasColumn('invoices', 'status') ? 'status' : null);

        $totalBilled = (float)Invoice::query()->where('customer_id', $customer->id)->sum($sumAmountCol);
        $totalPaid = $sumPaidCol
            ? (float)Invoice::query()->where('customer_id', $customer->id)->sum($sumPaidCol)
            : (float)Payment::query()->where('customer_id', $customer->id)->sum('amount');
        $totalDue = $sumBalanceCol
            ? (float)Invoice::query()->where('customer_id', $customer->id)->sum($sumBalanceCol)
            : max(0, $totalBilled - $totalPaid);

        $dueInvoicesQuery = Invoice::query()->where('customer_id', $customer->id);
        if ($statusCol === 'invoice_status') {
            $dueInvoicesQuery->whereNotIn('invoice_status', ['paid', 'cancelled']);
        } elseif ($statusCol === 'status') {
            $dueInvoicesQuery->where('status', '!=', 'paid');
        }
        if ($this->hasColumn('invoices', 'due_date')) {
            $dueInvoicesQuery->orderBy('due_date');
        } else {
            $dueInvoicesQuery->orderByDesc('id');
        }
        $dueInvoices = $dueInvoicesQuery->limit(10)->get($invoiceSelect)->map(function (Invoice $invoice) {
            try {
                return app(InvoiceBillingService::class)->recalculate($invoice);
            } catch (\Throwable) {
                return $invoice;
            }
        });

        $latestInvoice = $invoices->first();
        $nextDueDate = $dueInvoices->first()?->due_date;

        return view('customers.public-status', [
            'customer' => $customer,
            'package' => $package,
            'billing' => $billing,
            'invoices' => $invoices,
            'dueInvoices' => $dueInvoices,
            'summary' => [
                'total_billed' => round($totalBilled, 2),
                'total_paid' => round($totalPaid, 2),
                'total_due' => round($totalDue, 2),
                'open_invoices' => (int)$dueInvoices->count(),
                'latest_billing_date' => optional($latestInvoice?->created_at)->toDateString(),
                'next_due_date' => $nextDueDate ? Carbon::parse($nextDueDate)->toDateString() : null,
            ],
            'ratePerMb' => (float)($billing->rate_per_gb ?? $package?->price ?? 0),
            'rateMode' => strtolower((string)($package?->category ?? 'metered')) === 'hotspot' ? 'hotspot' : 'metered',
            'statusToken' => $token,
        ]);
    }

    private function buildCustomerStatusUrl(string $username): string
    {
        $token = $this->encodeCustomerStatusToken($username);
        return url('/account/status/' . $token);
    }

    private function encodeCustomerStatusToken(string $username): string
    {
        $encrypted = Crypt::encryptString($username);
        return rtrim(strtr(base64_encode($encrypted), '+/', '-_'), '=');
    }

    private function decodeCustomerStatusToken(string $token): ?string
    {
        try {
            $normalized = strtr($token, '-_', '+/');
            $padding = strlen($normalized) % 4;
            if ($padding > 0) {
                $normalized .= str_repeat('=', 4 - $padding);
            }

            $decoded = base64_decode($normalized, true);
            if ($decoded === false) {
                return null;
            }
            return (string)Crypt::decryptString($decoded);
        } catch (\Throwable) {
            return null;
        }
    }

    private function sendNewUserSms(
        string $phone,
        string $username,
        string $password,
        string $planName,
        string $profile,
        float $ratePerMb,
        string $billingMode,
        string $statusUrl
    ): array {
        $normalized = $this->normalizePhone($phone);
        if ($normalized === '') {
            return ['success' => false, 'message' => 'Customer phone missing.'];
        }

        $isHotspotMode = strtolower(trim($billingMode)) === 'hotspot';
        $rateLine = $isHotspotMode
            ? sprintf('Price: KES %.2f per package.', $ratePerMb)
            : sprintf('Rate: KES %.2f per MB.', $ratePerMb);

        $text = sprintf(
            "NetBil account created. Login: %s / %s. Plan: %s (%s). %s Account: %s",
            $username,
            $password,
            $planName !== '' ? $planName : 'N/A',
            $profile !== '' ? $profile : 'default',
            $rateLine,
            $statusUrl
        );

        try {
            $result = app(AdvantaSmsService::class)->send($normalized, $text);

            $this->logSmsMessage(
                phone: $normalized,
                text: $text,
                success: (bool)($result['success'] ?? false),
                messageId: $result['message_id'] ?? null,
                gatewayResponse: $result
            );

            return $result;
        } catch (\Throwable $e) {
            Log::error('Create user SMS failed', [
                'username' => $username,
                'phone' => $normalized,
                'error' => $e->getMessage(),
            ]);

            $this->logSmsMessage(
                phone: $normalized,
                text: $text,
                success: false,
                messageId: null,
                gatewayResponse: ['error' => $e->getMessage()]
            );

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function logSmsMessage(
        string $phone,
        string $text,
        bool $success,
        ?string $messageId = null,
        ?array $gatewayResponse = null
    ): void {
        if (!$this->hasTable('messages')) {
            return;
        }

        $payload = [];
        if ($this->hasColumn('messages', 'phone')) {
            $payload['phone'] = $phone;
        }
        if ($this->hasColumn('messages', 'text')) {
            $payload['text'] = $text;
        }
        if ($this->hasColumn('messages', 'sender')) {
            $payload['sender'] = 'advanta';
        }
        if ($this->hasColumn('messages', 'status')) {
            $payload['status'] = $success ? 'SENT' : 'FAILED';
        }
        if ($this->hasColumn('messages', 'message_id')) {
            $payload['message_id'] = $messageId;
        }
        if ($this->hasColumn('messages', 'gateway_response')) {
            $payload['gateway_response'] = $gatewayResponse;
        }
        if ($this->hasColumn('messages', 'sent_at')) {
            $payload['sent_at'] = now();
        }

        if (empty($payload)) {
            return;
        }

        try {
            Message::query()->create($payload);
        } catch (\Throwable $e) {
            Log::warning('Unable to persist SMS log', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function normalizePhone(string $value): string
    {
        $p = preg_replace('/[^\d+]/', '', trim($value));
        if (!$p) {
            return '';
        }

        if (str_starts_with($p, '+')) {
            $p = ltrim($p, '+');
        }

        if (preg_match('/^0(7\d{8})$/', $p, $m)) {
            return '254' . $m[1];
        }
        if (preg_match('/^(7\d{8})$/', $p, $m)) {
            return '254' . $m[1];
        }

        return $p;
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (!array_key_exists($key, self::$columnCache)) {
            try {
                self::$columnCache[$key] = Schema::hasColumn($table, $column);
            } catch (\Throwable) {
                self::$columnCache[$key] = false;
            }
        }

        return self::$columnCache[$key];
    }

    private function hasTable(string $table): bool
    {
        if (!array_key_exists($table, self::$tableCache)) {
            try {
                self::$tableCache[$table] = Schema::hasTable($table);
            } catch (\Throwable) {
                self::$tableCache[$table] = false;
            }
        }

        return self::$tableCache[$table];
    }
}
