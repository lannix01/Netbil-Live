<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Connection;
use App\Models\ConnectionUsageSample;
use App\Models\Customer;
use App\Models\HotspotUserBilling;
use App\Models\Invoice;
use App\Models\MegaPayment;
use App\Models\Package;
use App\Models\Subscription;
use App\Services\MegaPayClient;
use App\Services\MikrotikService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ConnectionController extends Controller
{
    private const DEMO_METERED_PORTAL_USERNAME = 'demo';
    private const DEMO_METERED_PORTAL_PASSWORD = 'password';
    private static array $schemaCache = [];

    /**
     * Public captive portal page.
     */
    public function index(Request $request)
    {
        $packageQuery = Package::query()
            ->orderBy('price', 'asc')
            ->orderBy('name', 'asc');

        if ($this->hasColumn('packages', 'status')) {
            $packageQuery->where('status', '!=', 'archived');
        }
        if ($this->hasColumn('packages', 'is_active')) {
            $packageQuery->where('is_active', true);
        }
        if ($this->hasColumn('packages', 'category')) {
            $packageQuery->where('category', 'hotspot');
        }

        $packages = $packageQuery->get();

        $ads = collect();
        if ($this->hasTable('ads')) {
            try {
                $ads = Ad::query()
                    ->active()
                    ->orderByDesc('priority')
                    ->orderByDesc('id')
                    ->get();
            } catch (\Throwable $e) {
                Log::warning('Portal ads query failed', ['error' => $e->getMessage()]);
            }
        }

        return view('connect.index', [
            'packages' => $packages,
            'ads' => $ads,
            'portal' => [
                'mac' => (string)$request->input('mac', ''),
                'ip' => (string)($request->input('ip', '') ?: $request->ip()),
                'link_login' => (string)$request->input('link-login', ''),
            ],
        ]);
    }

    /**
     * Public demo portal page.
     * Uses router-provided MAC when available, otherwise falls back to a deterministic synthetic MAC.
     */
    public function demo(Request $request)
    {
        $portal = $this->resolvePortalContext($request, true);

        return view('connect.demo', [
            'hotspotPackages' => $this->portalPackages('hotspot'),
            'meteredPackages' => $this->portalPackages('metered'),
            'portal' => $portal,
            'demoPinConfigured' => $this->isDemoPinConfigured(),
            'demoRateLimitPerMinute' => $this->demoRateLimitPerMinute(),
            'demoMeteredUsername' => self::DEMO_METERED_PORTAL_USERNAME,
            'demoMeteredPassword' => self::DEMO_METERED_PORTAL_PASSWORD,
        ]);
    }

    /**
     * Starts STK push for a hotspot plan.
     * Supports demo hotspot requests when `demo=true`.
     */
    public function requestHotspotPayment(Request $request, MegaPayClient $client, MikrotikService $mikrotik): JsonResponse
    {
        $data = $request->validate([
            'package_id' => 'required|integer|exists:packages,id',
            'msisdn' => 'required|string|max:20',
            'mac' => 'required|string|max:100',
            'ip' => 'nullable|string|max:100',
            'demo' => 'nullable|boolean',
            'pin' => 'nullable|string|max:64',
        ]);

        $isDemo = filter_var($data['demo'] ?? false, FILTER_VALIDATE_BOOL);
        if ($isDemo) {
            $pinError = $this->demoPinValidationError((string)($data['pin'] ?? ''));
            if ($pinError !== null) {
                return response()->json([
                    'ok' => false,
                    'message' => $pinError['message'],
                ], $pinError['status']);
            }
        }

        $package = Package::findOrFail((int)$data['package_id']);
        if ($this->hasColumn('packages', 'category')) {
            $category = strtolower((string)($package->category ?? 'hotspot'));
            if ($category !== 'hotspot') {
                return response()->json([
                    'ok' => false,
                    'message' => 'Selected plan is not available under hotspot.',
                ], 422);
            }
        }

        $portal = $this->resolvePortalContext($request, $isDemo);
        $mac = trim((string)($portal['mac'] ?? ''));
        $ip = trim((string)($portal['ip'] ?? ''));

        if ($mac === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Device MAC could not be resolved for this request.',
            ], 422);
        }

        if (!$isDemo) {
            $existing = Connection::query()
                ->where('mac_address', $mac)
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->latest('id')
                ->first();

            if ($existing) {
                $existingPackage = Package::find((int)($existing->package_id ?? 0)) ?: $package;
                $result = $this->ensureHotspotSessionConnection($existing, $existingPackage, $ip, $mikrotik);
                if (!($result['connected'] ?? false)) {
                    return response()->json([
                        'ok' => false,
                        'message' => $result['message'] ?? 'An active package exists, but automatic reconnect failed.',
                    ], 500);
                }

                /** @var Connection $connection */
                $connection = $result['connection'];

                return response()->json([
                    'ok' => true,
                    'connected' => true,
                    'reconnected' => (bool)($result['reconnected'] ?? false),
                    'message' => ($result['reconnected'] ?? false)
                        ? 'Existing active package restored.'
                        : 'Already connected on an active package.',
                    'connection_id' => $connection->id,
                    ...$this->statusPayload($connection),
                ]);
            }
        }

        $reference = $isDemo
            ? $this->nextDemoReference('hotspot')
            : $this->nextHotspotReference($package->id, $mac);
        $amount = max(1, (int)round((float)($package->price ?? 0)));
        $msisdn = MegaPayClient::normalizeMsisdn((string)$data['msisdn']);

        $durationMinutes = $this->packageDurationMinutes($package);
        $timeLabel = $this->humanTime($durationMinutes);
        $speed = trim((string)($package->speed ?? $package->rate_limit ?? ''));
        $meta = [
            'flow' => $isDemo ? 'connect.demo.hotspot' : 'connect.hotspot',
            'package_id' => $package->id,
            'package_name' => $package->name,
            'speed' => $speed,
            'time_label' => $timeLabel,
            'duration_minutes' => $durationMinutes,
            'data_limit' => $package->data_limit ?? null,
            'mac' => $mac,
            'ip' => $ip,
        ];

        if ($isDemo) {
            $demoIdentity = $this->buildDemoIdentity('hotspot', $mac);
            $meta['demo_user'] = true;
            $meta['identity_source'] = (string)($portal['mac_source'] ?? 'synthetic');
            $meta['demo_username'] = $demoIdentity['username'];
        }

        $createPayload = $this->onlyExistingColumns('megapayments', [
            'reference' => $reference,
            'purpose' => 'hotspot_access',
            'channel' => $isDemo ? 'demo_portal' : 'portal_connect',
            'payable_type' => Package::class,
            'payable_id' => $package->id,
            'customer_id' => null,
            'initiated_by' => auth()->check() ? auth()->id() : null,
            'msisdn' => $msisdn,
            'amount' => $amount,
            'status' => 'pending',
            'initiated_at' => now(),
            'meta' => $meta,
        ]);

        $payment = MegaPayment::create($createPayload);

        try {
            $resp = $client->initiateStk($amount, $msisdn, $reference);
            $updatePayload = $this->onlyExistingColumns('megapayments', [
                'transaction_request_id' => $resp['transaction_request_id'] ?? null,
                'merchant_request_id' => $resp['MerchantRequestID'] ?? ($resp['merchant_request_id'] ?? null),
                'checkout_request_id' => $resp['CheckoutRequestID'] ?? ($resp['checkout_request_id'] ?? null),
                'response_description' => $resp['message'] ?? ($resp['massage'] ?? null),
                'response_code' => isset($resp['ResponseCode']) ? (int)$resp['ResponseCode'] : null,
            ]);

            if (isset($resp['ResponseCode']) && (int)$resp['ResponseCode'] !== 0) {
                $updatePayload = array_merge($updatePayload, $this->onlyExistingColumns('megapayments', [
                    'status' => 'failed',
                    'failed_at' => now(),
                ]));
            }

            if (!empty($updatePayload)) {
                $payment->fill($updatePayload);
                $payment->save();
            }
        } catch (\Throwable $e) {
            $payment->fill($this->onlyExistingColumns('megapayments', [
                'status' => 'failed',
                'response_description' => 'STK request failed: ' . $e->getMessage(),
                'failed_at' => now(),
            ]));
            $payment->save();

            Log::error('Hotspot STK initiate failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Failed to send STK request. Please retry.',
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'connected' => false,
            'message' => $isDemo
                ? 'STK request sent. Complete payment on your phone to start the demo session.'
                : 'STK request sent. Complete payment on your phone.',
            'reference' => $reference,
            'amount' => $amount,
            'msisdn' => $msisdn,
            'package' => [
                'id' => $package->id,
                'name' => $package->name,
                'speed' => $speed,
                'time' => $timeLabel,
                'price' => (float)($package->price ?? 0),
            ],
        ]);
    }

    /**
     * Polls hotspot payment status and auto-connects user when completed.
     */
    public function hotspotPaymentStatus(Request $request, MegaPayClient $client, MikrotikService $mikrotik): JsonResponse
    {
        $data = $request->validate([
            'reference' => 'required|string|max:64',
        ]);

        $payment = MegaPayment::query()
            ->where('reference', trim((string)$data['reference']))
            ->first();

        if (!$payment) {
            return response()->json([
                'ok' => false,
                'message' => 'Payment reference not found.',
            ], 404);
        }

        if (strtolower((string)$payment->status) === 'pending') {
            $payment = $this->refreshMegaPaymentStatus($payment, $client);
        }

        $status = strtolower((string)($payment->status ?? 'pending'));
        if ($status === 'completed') {
            $result = $this->isDemoHotspotPayment($payment)
                ? $this->activateDemoHotspotConnectionFromPayment($payment, $mikrotik)
                : $this->activateHotspotConnectionFromPayment($payment, $mikrotik);
            if (!($result['connected'] ?? false)) {
                return response()->json([
                    'ok' => false,
                    'status' => 'completed',
                    'message' => $result['message'] ?? 'Payment is complete but connection setup failed.',
                ], 500);
            }

            /** @var Connection $connection */
            $connection = $result['connection'];

            return response()->json([
                'ok' => true,
                'status' => 'completed',
                'connected' => true,
                'reconnected' => (bool)($result['reconnected'] ?? false),
                'message' => $result['message'] ?? 'Payment received. Connecting to hotspot.',
                'connection_id' => $connection->id,
                ...$this->statusPayload($connection),
            ]);
        }

        if (in_array($status, ['failed', 'cancelled', 'timeout', 'expired'], true)) {
            return response()->json([
                'ok' => false,
                'status' => $status,
                'connected' => false,
                'message' => (string)($payment->response_description ?: 'Payment was not completed.'),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'status' => $status ?: 'pending',
            'connected' => false,
            'message' => 'Waiting for payment confirmation.',
        ]);
    }

    /**
     * Backward-compatible connect endpoint.
     * Expects paid reference and finalizes hotspot provisioning.
     */
    public function connectHotspot(Request $request, MikrotikService $mikrotik, MegaPayClient $client): JsonResponse
    {
        $data = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'mac' => 'required|string|max:100',
            'ip' => 'required|string|max:100',
            'reference' => 'nullable|string|max:64',
        ]);

        $reference = trim((string)($data['reference'] ?? ''));
        if ($reference === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Payment authorization missing. Start payment from hotspot packages first.',
            ], 422);
        }

        $payment = MegaPayment::query()
            ->where('reference', $reference)
            ->first();

        if (!$payment) {
            return response()->json([
                'ok' => false,
                'message' => 'Payment reference not found.',
            ], 404);
        }

        if (strtolower((string)$payment->status) === 'pending') {
            $payment = $this->refreshMegaPaymentStatus($payment, $client);
        }

        if (strtolower((string)$payment->status) !== 'completed') {
            return response()->json([
                'ok' => false,
                'message' => 'Payment has not completed yet.',
                'status' => $payment->status,
            ], 422);
        }

        $meta = is_array($payment->meta) ? $payment->meta : [];
        $meta['package_id'] = (int)$data['package_id'];
        $meta['mac'] = trim((string)$data['mac']);
        $meta['ip'] = trim((string)$data['ip']);
        $payment->fill($this->onlyExistingColumns('megapayments', ['meta' => $meta]));
        $payment->save();

        $result = $this->activateHotspotConnectionFromPayment($payment, $mikrotik);
        if (!($result['connected'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => $result['message'] ?? 'Unable to connect hotspot user.',
            ], 500);
        }

        /** @var Connection $connection */
        $connection = $result['connection'];

        return response()->json([
            'ok' => true,
            'reconnected' => (bool)($result['reconnected'] ?? false),
            'message' => $result['message'] ?? 'Connected successfully.',
            'data' => [
                'connection_id' => $connection->id,
                'expires_at' => optional($connection->expires_at)->toDateTimeString(),
                ...$this->statusPayload($connection),
            ],
        ]);
    }

    /**
     * Metered user login flow.
     * Existing account required. Blocks access if overdue invoices exist.
     */
    public function connectMetered(Request $request, MikrotikService $mikrotik): JsonResponse
    {
        $data = $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'mac' => 'required|string|max:100',
            'ip' => 'required|string|max:100',
        ]);

        $username = trim((string)$data['username']);
        $password = (string)$data['password'];
        $mac = trim((string)$data['mac']);
        $ip = trim((string)$data['ip']);

        $customer = Customer::query()
            ->where('username', $username)
            ->orderByDesc('id')
            ->first();

        if (!$customer) {
            return response()->json([
                'ok' => false,
                'message' => 'No metered account found for that username.',
            ], 404);
        }

        if ($this->hasColumn('customers', 'status') && strtolower((string)$customer->status) !== 'active') {
            return response()->json([
                'ok' => false,
                'message' => 'This account is inactive.',
            ], 422);
        }

        $subscription = null;
        if ($this->hasTable('subscriptions')) {
            $subQuery = Subscription::query()
                ->where('customer_id', $customer->id);

            if ($this->hasColumn('subscriptions', 'username')) {
                $subQuery->where('username', $username);
            }
            if ($this->hasColumn('subscriptions', 'type')) {
                $subQuery->whereIn('type', ['metered', 'hotspot']);
                $subQuery->orderByRaw("CASE WHEN type = 'metered' THEN 0 ELSE 1 END");
            }

            $subscription = $subQuery->orderByDesc('id')->first();
        }

        $validCredentials = false;
        if ($subscription && $this->hasColumn('subscriptions', 'password')) {
            $stored = $this->decryptSubscriptionPassword((string)$subscription->password);
            if ($stored !== null && hash_equals($stored, $password)) {
                $validCredentials = true;
            }
        }

        if (!$validCredentials) {
            $validCredentials = $mikrotik->verifyHotspotCredentials($username, $password);
        }

        if (!$validCredentials) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid username or password.',
            ], 422);
        }

        $package = $this->resolveMeteredPackage($customer, $subscription, $username);
        if (!$package) {
            return response()->json([
                'ok' => false,
                'message' => 'No metered plan is assigned to this account.',
            ], 422);
        }

        if ($this->hasColumn('packages', 'category')) {
            $category = strtolower((string)($package->category ?? 'hotspot'));
            if ($category !== 'metered') {
                return response()->json([
                    'ok' => false,
                    'message' => 'Assigned plan is not a metered category plan.',
                ], 422);
            }
        }

        if ($this->hasOverdueInvoices((int)$customer->id)) {
            try {
                $mikrotik->disconnectActiveUser($username, $mac);
            } catch (\Throwable) {
            }

            return response()->json([
                'ok' => false,
                'message' => 'Connection blocked. This account has overdue invoice(s).',
            ], 422);
        }

        $profile = trim((string)(
            $package->mk_profile
            ?? $package->mikrotik_profile
            ?? 'default'
        ));
        $rateLimit = trim((string)(
            $package->rate_limit
            ?? $package->speed
            ?? ''
        ));

        try {
            $mikrotik->connectMeteredUser(
                username: $username,
                password: $password,
                profile: $profile,
                ip: $ip,
                mac: $mac,
                rateLimit: $rateLimit
            );
        } catch (\Throwable $e) {
            Log::error('Metered connect failed', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Failed to connect to hotspot gateway.',
            ], 500);
        }

        $connection = Connection::query()
            ->where('username', $username)
            ->where('mac_address', $mac)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (!$connection) {
            $connection = $this->createMeteredConnectionRecord(
                package: $package,
                mac: $mac,
                ip: $ip,
                username: $username,
            );
        }

        $this->upsertMeteredSubscription($customer, $package, $username, $password, $mac, $profile, $ip);

        return response()->json([
            'ok' => true,
            'message' => 'Metered user authenticated and connected.',
            'data' => [
                'connection_id' => $connection->id,
                ...$this->statusPayload($connection),
            ],
        ]);
    }

    /**
     * Starts a demo session.
     * Metered demo provisions directly; hotspot demo now goes through STK payment first.
     */
    public function startDemo(Request $request, MikrotikService $mikrotik): JsonResponse
    {
        $data = $request->validate([
            'mode' => 'required|string|in:hotspot,metered',
            'package_id' => 'nullable|integer|exists:packages,id',
            'pin' => 'required|string|max:64',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'mac' => 'nullable|string|max:100',
            'ip' => 'nullable|string|max:100',
        ]);

        $pinError = $this->demoPinValidationError((string)($data['pin'] ?? ''));
        if ($pinError !== null) {
            return response()->json([
                'ok' => false,
                'message' => $pinError['message'],
            ], $pinError['status']);
        }

        $mode = strtolower(trim((string)$data['mode']));
        if ($mode === 'hotspot') {
            return response()->json([
                'ok' => false,
                'message' => 'Hotspot demo now requires STK payment. Use Pay and Connect from the package card.',
            ], 422);
        }

        if ($mode === 'metered') {
            $meteredAccessError = $this->demoMeteredAccessValidationError(
                username: (string)($data['username'] ?? ''),
                password: (string)($data['password'] ?? '')
            );

            if ($meteredAccessError !== null) {
                return response()->json([
                    'ok' => false,
                    'message' => $meteredAccessError,
                ], 422);
            }
        }

        $portal = $this->resolvePortalContext($request, true);
        $requestedPackageId = (int)($data['package_id'] ?? 0);
        $package = $this->resolveDemoPackage($mode, $requestedPackageId);

        if (!$package) {
            return response()->json([
                'ok' => false,
                'message' => $mode === 'metered'
                    ? 'No metered demo plan is available. Create an active metered package first.'
                    : 'No hotspot demo plan is available. Create an active hotspot package first.',
            ], 422);
        }

        $demoIdentity = $this->buildDemoIdentity($mode, $portal['mac']);
        $username = $demoIdentity['username'];
        $password = $demoIdentity['password'];
        $profile = trim((string)(
            $package->mk_profile
            ?? $package->mikrotik_profile
            ?? 'default'
        ));
        $rateLimit = trim((string)(
            $package->rate_limit
            ?? $package->speed
            ?? ''
        ));
        $now = now();

        try {
            DB::beginTransaction();

            try {
                $mikrotik->disconnectActiveUser($username, $portal['mac']);
            } catch (\Throwable $disconnectError) {
                Log::warning('Demo pre-disconnect failed', [
                    'mode' => $mode,
                    'username' => $username,
                    'mac' => $portal['mac'],
                    'error' => $disconnectError->getMessage(),
                ]);
            }

            $customer = $this->upsertDemoCustomer(
                username: $username,
                package: $package,
                ip: $portal['ip'],
                mode: $mode,
            );

            $this->upsertDemoBilling($username, $customer, $package);
            $this->terminateActiveDemoConnections($username);

            if ($mode === 'hotspot') {
                $expiresAt = $this->demoHotspotExpiresAt($package, $now);

                $this->storeDemoSubscription(
                    customer: $customer,
                    package: $package,
                    username: $username,
                    password: $password,
                    mac: $portal['mac'],
                    profile: $profile,
                    mode: 'hotspot',
                    startedAt: $now,
                    expiresAt: $expiresAt,
                    createNew: true,
                    extraMeta: [
                        'demo_user' => true,
                        'simulated' => true,
                        'mode' => 'hotspot',
                        'identity_source' => $portal['mac_source'],
                        'ip' => $portal['ip'],
                    ],
                );

                $connection = $this->createHotspotConnectionRecord(
                    package: $package,
                    mac: $portal['mac'],
                    ip: $portal['ip'],
                    username: $username,
                    expiresAt: $expiresAt,
                    startedAt: $now,
                );

                $mikrotik->connectNamedHotspot(
                    package: $package,
                    username: $username,
                    ip: $portal['ip'],
                    mac: $portal['mac'],
                    password: $password,
                    comment: 'demo_user hotspot via /connect/demo',
                );

                $this->syncConnectionStartCounters($connection, $mikrotik->getHotspotUserStats($username));
                $this->createDemoHotspotPayment(
                    customer: $customer,
                    package: $package,
                    connection: $connection,
                    username: $username,
                    portal: $portal,
                );

                DB::commit();

                return response()->json([
                    'ok' => true,
                    'demo' => true,
                    'mode' => 'hotspot',
                    'message' => 'Demo hotspot session connected.',
                    'data' => [
                        'username' => $username,
                        'connection_id' => $connection->id,
                        ...$this->statusPayload($connection),
                    ],
                    ...$this->statusPayload($connection),
                ]);
            }

            $mikrotik->connectMeteredUser(
                username: $username,
                password: $password,
                profile: $profile,
                ip: $portal['ip'],
                mac: $portal['mac'],
                rateLimit: $rateLimit,
                comment: 'metered demo_user via /connect/demo',
            );

            $this->storeDemoSubscription(
                customer: $customer,
                package: $package,
                username: $username,
                password: $password,
                mac: $portal['mac'],
                profile: $profile,
                mode: 'metered',
                startedAt: $now,
                expiresAt: null,
                createNew: false,
                extraMeta: [
                    'demo_user' => true,
                    'simulated' => true,
                    'mode' => 'metered',
                    'identity_source' => $portal['mac_source'],
                    'ip' => $portal['ip'],
                ],
            );

            $connection = $this->createMeteredConnectionRecord(
                package: $package,
                mac: $portal['mac'],
                ip: $portal['ip'],
                username: $username,
                startedAt: $now,
            );

            $this->syncConnectionStartCounters($connection, $mikrotik->getHotspotUserStats($username));

            DB::commit();

            return response()->json([
                'ok' => true,
                'demo' => true,
                'mode' => 'metered',
                'message' => 'Demo metered session connected.',
                'data' => [
                    'username' => $username,
                    'connection_id' => $connection->id,
                    ...$this->statusPayload($connection),
                ],
                ...$this->statusPayload($connection),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Demo connect failed', [
                'mode' => $mode,
                'mac' => $portal['mac'],
                'ip' => $portal['ip'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Could not start the demo session. Check the hotspot gateway and try again.',
            ], 500);
        }
    }

    /**
     * Connection status page.
     */
    public function status(Connection $connection)
    {
        $connection->load('package');
        $package = $connection->package;
        $mode = strtolower((string)($package?->category ?? 'hotspot'));

        if ($mode === 'hotspot' && $connection->status === 'active' && $connection->expires_at && $connection->expires_at->isPast()) {
            $connection->status = 'expired';
            $connection->save();
        }

        return view('connect.status', [
            'connection' => $connection,
            'package' => $package,
            'mode' => $mode,
        ]);
    }

    /**
     * Connection polling endpoint.
     */
    public function poll(Connection $connection): JsonResponse
    {
        $connection->load('package');
        $package = $connection->package;
        $mode = strtolower((string)($package?->category ?? 'hotspot'));

        if ($mode === 'hotspot' && $connection->status === 'active' && $connection->expires_at && $connection->expires_at->isPast()) {
            $connection->status = 'expired';
            $connection->save();
        }

        return response()->json([
            'ok' => true,
            'connection' => [
                'id' => $connection->id,
                'status' => $connection->status,
                'mode' => $mode,
                'started_at' => optional($connection->started_at)->toDateTimeString(),
                'expires_at' => optional($connection->expires_at)->toDateTimeString(),
                'package' => [
                    'id' => $package?->id,
                    'name' => $package?->name,
                    'speed' => $package?->speed,
                    'price' => $package?->price,
                ],
            ],
        ]);
    }

    /**
     * Captive portal success page shown right after payment + connection.
     */
    public function hotspotSuccess(Connection $connection)
    {
        $connection->load('package');
        $package = $connection->package;
        $mode = strtolower((string)($package?->category ?? 'hotspot'));

        if ($mode === 'hotspot' && $connection->status === 'active' && $connection->expires_at && $connection->expires_at->isPast()) {
            $connection->status = 'expired';
            $connection->save();
        }

        return view('connect.success', [
            'connection' => $connection,
            'package' => $package,
            'mode' => $mode,
            'statusUrl' => route('connect.status', $connection, false),
        ]);
    }

    private function statusPayload(Connection $connection): array
    {
        return [
            'status_url' => route('connect.status', $connection, false),
            'status_poll_url' => route('connect.status.poll', $connection, false),
            'success_url' => route('connect.hotspot.success', $connection, false),
        ];
    }

    private function ensureHotspotSessionConnection(
        Connection $connection,
        Package $package,
        string $ip,
        MikrotikService $mikrotik,
        ?MegaPayment $payment = null
    ): array {
        $mac = trim((string)($connection->mac_address ?? ''));
        $username = trim((string)($connection->username ?? ''));
        if ($username === '' && $mac !== '') {
            $username = $this->hotspotUsernameFromMac($mac);
        }

        if ($mac === '' || $username === '') {
            return [
                'connected' => false,
                'message' => 'Active package exists, but router identity is incomplete.',
            ];
        }

        $activeSession = null;
        try {
            $activeSession = $mikrotik->getActiveSession($mac, null);
        } catch (\Throwable $e) {
            Log::warning('Hotspot active session lookup failed', [
                'connection_id' => $connection->id,
                'mac' => $mac,
                'error' => $e->getMessage(),
            ]);
        }

        $hotspotUser = null;
        try {
            $hotspotUser = $mikrotik->getHotspotUserStats($username);
        } catch (\Throwable $e) {
            Log::warning('Hotspot user stats lookup failed', [
                'connection_id' => $connection->id,
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
        }

        if ($activeSession && $this->shouldRotateConnectionForLiveSession($connection, $activeSession)) {
            return $this->rotateHotspotConnectionForLiveSession(
                connection: $connection,
                package: $package,
                ip: $ip,
                activeSession: $activeSession,
                hotspotUser: $hotspotUser,
                payment: $payment,
            );
        }

        if ($activeSession) {
            $updates = [];
            if ($ip !== '' && $this->hasColumn('connections', 'ip_address') && (string)($connection->ip_address ?? '') !== $ip) {
                $updates['ip_address'] = $ip;
            }
            if ($this->hasColumn('connections', 'status') && strtolower((string)($connection->status ?? '')) !== 'active') {
                $updates['status'] = 'active';
            }
            if (!empty($updates)) {
                $connection->fill($updates);
                $connection->save();
            }

            if ($payment) {
                $this->syncPaymentConnection($payment, $connection);
            }

            return [
                'connected' => true,
                'reconnected' => false,
                'message' => 'Already connected on an active package.',
                'connection' => $connection,
            ];
        }

        return $this->reconnectHotspotSession(
            connection: $connection,
            package: $package,
            ip: $ip,
            mikrotik: $mikrotik,
            hotspotUser: $hotspotUser,
            payment: $payment,
        );
    }

    private function shouldRotateConnectionForLiveSession(Connection $connection, ?array $activeSession): bool
    {
        if (!$activeSession || !$connection->started_at) {
            return false;
        }

        $routerStartedAt = $this->resolveSessionStartedAt($activeSession);
        if (!$routerStartedAt) {
            return false;
        }

        return $routerStartedAt->greaterThan($connection->started_at->copy()->addSeconds(15));
    }

    private function resolveSessionStartedAt(?array $activeSession)
    {
        if (!$activeSession) {
            return null;
        }

        $uptimeSeconds = $this->parseDurationToSeconds((string)($activeSession['uptime'] ?? ''));
        if ($uptimeSeconds <= 0) {
            return null;
        }

        return now()->copy()->subSeconds($uptimeSeconds);
    }

    private function rotateHotspotConnectionForLiveSession(
        Connection $connection,
        Package $package,
        string $ip,
        array $activeSession,
        ?array $hotspotUser = null,
        ?MegaPayment $payment = null
    ): array {
        $mac = trim((string)($connection->mac_address ?? ''));
        $username = trim((string)($connection->username ?? ''));
        if ($username === '' && $mac !== '') {
            $username = $this->hotspotUsernameFromMac($mac);
        }

        $startedAt = $this->resolveSessionStartedAt($activeSession) ?: now();
        $sessionIp = trim((string)($ip !== '' ? $ip : ($activeSession['address'] ?? $connection->ip_address ?? '')));
        $activeSessionBytesIn = max(0, (int)($activeSession['bytes-in'] ?? 0));
        $activeSessionBytesOut = max(0, (int)($activeSession['bytes-out'] ?? 0));
        $startCounters = [
            'bytes-in' => max(0, (int)($hotspotUser['bytes-in'] ?? 0) - $activeSessionBytesIn),
            'bytes-out' => max(0, (int)($hotspotUser['bytes-out'] ?? 0) - $activeSessionBytesOut),
        ];

        DB::beginTransaction();
        try {
            $this->closeConnectionSession($connection, null, 'terminated', $startedAt);

            $replacement = $this->createHotspotConnectionRecord(
                package: $package,
                mac: $mac,
                ip: $sessionIp,
                username: $username,
                expiresAt: $connection->expires_at,
                startedAt: $startedAt,
                startCounters: $startCounters,
            );

            if ($payment) {
                $this->syncPaymentConnection($payment, $replacement);
            }

            DB::commit();

            return [
                'connected' => true,
                'reconnected' => true,
                'message' => 'Existing active package restored.',
                'connection' => $replacement,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Hotspot live-session rotation failed', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'connected' => false,
                'message' => 'Active package detected, but session tracking could not be refreshed.',
            ];
        }
    }

    private function reconnectHotspotSession(
        Connection $connection,
        Package $package,
        string $ip,
        MikrotikService $mikrotik,
        ?array $hotspotUser = null,
        ?MegaPayment $payment = null
    ): array {
        $mac = trim((string)($connection->mac_address ?? ''));
        $username = trim((string)($connection->username ?? ''));
        if ($username === '' && $mac !== '') {
            $username = $this->hotspotUsernameFromMac($mac);
        }

        DB::beginTransaction();
        try {
            $this->closeConnectionSession($connection, $hotspotUser, 'terminated');

            $replacement = $this->createHotspotConnectionRecord(
                package: $package,
                mac: $mac,
                ip: $ip !== '' ? $ip : (string)($connection->ip_address ?? ''),
                username: $username,
                expiresAt: $connection->expires_at,
            );

            $mikrotik->connectHotspot(package: $package, ip: (string)($replacement->ip_address ?? $ip), mac: $mac);
            $this->syncConnectionStartCounters($replacement, $mikrotik->getHotspotUserStats($username));

            if ($payment) {
                $this->syncPaymentConnection($payment, $replacement);
            }

            DB::commit();

            return [
                'connected' => true,
                'reconnected' => true,
                'message' => 'Existing active package restored.',
                'connection' => $replacement,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Hotspot reconnect failed', [
                'connection_id' => $connection->id,
                'mac' => $mac,
                'error' => $e->getMessage(),
            ]);

            return [
                'connected' => false,
                'message' => 'Existing package is valid, but reconnect failed at the hotspot gateway.',
            ];
        }
    }

    private function createHotspotConnectionRecord(
        Package $package,
        string $mac,
        string $ip,
        string $username,
        $expiresAt = null,
        $startedAt = null,
        array $startCounters = []
    ): Connection {
        $resolvedExpiresAt = $expiresAt;
        if (!$resolvedExpiresAt) {
            $durationMinutes = $this->packageDurationMinutes($package);
            $resolvedExpiresAt = $durationMinutes > 0
                ? now()->addMinutes($durationMinutes)
                : now()->addDays(30);
        }

        $resolvedStartedAt = $startedAt ?: now();
        $startBytesIn = max(0, (int)($startCounters['bytes-in'] ?? $startCounters['bytes_in'] ?? 0));
        $startBytesOut = max(0, (int)($startCounters['bytes-out'] ?? $startCounters['bytes_out'] ?? 0));

        $connection = Connection::create($this->onlyExistingColumns('connections', [
            'mac_address' => $mac,
            'ip_address' => $ip,
            'package_id' => $package->id,
            'username' => $username,
            'started_at' => $resolvedStartedAt,
            'expires_at' => $resolvedExpiresAt,
            'status' => 'active',
            'start_bytes_in' => $startBytesIn,
            'start_bytes_out' => $startBytesOut,
            'bytes_in' => 0,
            'bytes_out' => 0,
        ]));

        $this->recordConnectionUsageSample(
            connection: $connection,
            recordedAt: $resolvedStartedAt,
            uptimeSeconds: 0,
            bytesIn: 0,
            bytesOut: 0,
            source: 'session_start',
        );

        return $connection;
    }

    private function closeConnectionSession(
        Connection $connection,
        ?array $hotspotUser = null,
        string $status = 'terminated',
        $endedAt = null
    ): void {
        $updates = [];

        if ($this->hasColumn('connections', 'ended_at')) {
            $resolvedEndedAt = $endedAt ?: now();
            if ($connection->started_at && $resolvedEndedAt->lessThan($connection->started_at)) {
                $resolvedEndedAt = $connection->started_at->copy();
            }
            $updates['ended_at'] = $resolvedEndedAt;
        }

        if ($hotspotUser && ($this->hasColumn('connections', 'bytes_in') || $this->hasColumn('connections', 'bytes_out'))) {
            $rawBytesIn = max(0, (int)($hotspotUser['bytes-in'] ?? $hotspotUser['bytes_in'] ?? 0));
            $rawBytesOut = max(0, (int)($hotspotUser['bytes-out'] ?? $hotspotUser['bytes_out'] ?? 0));
            $startBytesIn = $this->hasColumn('connections', 'start_bytes_in')
                ? max(0, (int)($connection->start_bytes_in ?? 0))
                : 0;
            $startBytesOut = $this->hasColumn('connections', 'start_bytes_out')
                ? max(0, (int)($connection->start_bytes_out ?? 0))
                : 0;

            if ($this->hasColumn('connections', 'bytes_in')) {
                $updates['bytes_in'] = max(0, $rawBytesIn - $startBytesIn);
            }
            if ($this->hasColumn('connections', 'bytes_out')) {
                $updates['bytes_out'] = max(0, $rawBytesOut - $startBytesOut);
            }
        }

        if ($this->hasColumn('connections', 'status')) {
            $updates['status'] = $status;
        }

        if (!empty($updates)) {
            $connection->fill($updates);
            $connection->save();
        }

        $recordedAt = $updates['ended_at'] ?? $endedAt ?? now();
        $resolvedStartedAt = $connection->started_at ?: null;
        $uptimeSeconds = $resolvedStartedAt
            ? max(0, $resolvedStartedAt->diffInSeconds($recordedAt))
            : null;

        $this->recordConnectionUsageSample(
            connection: $connection,
            recordedAt: $recordedAt,
            uptimeSeconds: $uptimeSeconds,
            bytesIn: max(0, (int)($connection->bytes_in ?? $updates['bytes_in'] ?? 0)),
            bytesOut: max(0, (int)($connection->bytes_out ?? $updates['bytes_out'] ?? 0)),
            source: $status === 'expired' ? 'session_expired' : 'session_end',
        );
    }

    private function syncConnectionStartCounters(Connection $connection, ?array $hotspotUser = null): void
    {
        if (!$hotspotUser) {
            return;
        }

        $updates = [];
        if ($this->hasColumn('connections', 'start_bytes_in')) {
            $updates['start_bytes_in'] = max(0, (int)($hotspotUser['bytes-in'] ?? $hotspotUser['bytes_in'] ?? 0));
        }
        if ($this->hasColumn('connections', 'start_bytes_out')) {
            $updates['start_bytes_out'] = max(0, (int)($hotspotUser['bytes-out'] ?? $hotspotUser['bytes_out'] ?? 0));
        }
        if ($this->hasColumn('connections', 'bytes_in')) {
            $updates['bytes_in'] = 0;
        }
        if ($this->hasColumn('connections', 'bytes_out')) {
            $updates['bytes_out'] = 0;
        }

        if (!empty($updates)) {
            $connection->fill($updates);
            $connection->save();
        }
    }

    private function syncPaymentConnection(MegaPayment $payment, Connection $connection): void
    {
        $meta = is_array($payment->meta) ? $payment->meta : [];
        $meta['connection_id'] = $connection->id;
        $meta['connected_at'] = now()->toDateTimeString();

        $payment->fill($this->onlyExistingColumns('megapayments', [
            'meta' => $meta,
            'payable_type' => Connection::class,
            'payable_id' => $connection->id,
        ]));
        $payment->save();
    }

    private function canRecordConnectionUsageSamples(): bool
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
        if (!$this->canRecordConnectionUsageSamples()) {
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
            ...$payload,
        ]);
    }

    private function activateHotspotConnectionFromPayment(MegaPayment $payment, MikrotikService $mikrotik): array
    {
        $meta = is_array($payment->meta) ? $payment->meta : [];
        $packageId = (int)($meta['package_id'] ?? 0);
        $mac = trim((string)($meta['mac'] ?? ''));
        $ip = trim((string)($meta['ip'] ?? ''));

        if ($packageId <= 0 || $mac === '' || $ip === '') {
            return [
                'connected' => false,
                'message' => 'Payment context missing package/mac/ip details.',
            ];
        }

        $package = Package::find($packageId);
        if (!$package) {
            return [
                'connected' => false,
                'message' => 'Package linked to payment was not found.',
            ];
        }

        if ($this->hasColumn('packages', 'category')) {
            $category = strtolower((string)($package->category ?? 'hotspot'));
            if ($category !== 'hotspot') {
                return [
                    'connected' => false,
                    'message' => 'Linked payment package is not hotspot category.',
                ];
            }
        }

        $existingConnectionId = (int)($meta['connection_id'] ?? 0);
        if ($existingConnectionId > 0) {
            $existing = Connection::query()->find($existingConnectionId);
            if ($existing && $existing->status === 'active' && $existing->expires_at && $existing->expires_at->isFuture()) {
                return $this->ensureHotspotSessionConnection($existing, $package, $ip, $mikrotik, $payment);
            }
        }

        $active = Connection::query()
            ->where('mac_address', $mac)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($active) {
            return $this->ensureHotspotSessionConnection($active, $package, $ip, $mikrotik, $payment);
        }

        $durationMinutes = $this->packageDurationMinutes($package);
        $expiresAt = $durationMinutes > 0
            ? now()->addMinutes($durationMinutes)
            : now()->addDays(30);

        $username = $this->hotspotUsernameFromMac($mac);

        DB::beginTransaction();
        try {
            $connection = $this->createHotspotConnectionRecord(
                package: $package,
                mac: $mac,
                ip: $ip,
                username: $username,
                expiresAt: $expiresAt,
            );

            $mikrotik->connectHotspot(package: $package, ip: $ip, mac: $mac);

            $this->syncConnectionStartCounters($connection, $mikrotik->getHotspotUserStats($username));
            $this->syncPaymentConnection($payment, $connection);

            DB::commit();

            return [
                'connected' => true,
                'message' => 'Payment received. Connection is active.',
                'connection' => $connection,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Hotspot activation failed after payment', [
                'reference' => $payment->reference,
                'error' => $e->getMessage(),
            ]);

            return [
                'connected' => false,
                'message' => 'Payment confirmed, but automatic connection failed. Please retry.',
            ];
        }
    }

    private function isDemoHotspotPayment(MegaPayment $payment): bool
    {
        $meta = is_array($payment->meta) ? $payment->meta : [];
        if (!empty($meta['demo_user'])) {
            return true;
        }

        return strtolower((string)($meta['flow'] ?? '')) === 'connect.demo.hotspot';
    }

    private function activateDemoHotspotConnectionFromPayment(MegaPayment $payment, MikrotikService $mikrotik): array
    {
        $meta = is_array($payment->meta) ? $payment->meta : [];
        $existingConnectionId = (int)($meta['connection_id'] ?? 0);
        if ($existingConnectionId > 0) {
            $existing = Connection::query()->find($existingConnectionId);
            if (
                $existing
                && strtolower((string)($existing->status ?? '')) === 'active'
                && (!$existing->expires_at || $existing->expires_at->isFuture())
            ) {
                return [
                    'connected' => true,
                    'reconnected' => false,
                    'message' => 'Payment received. Demo hotspot session is active.',
                    'connection' => $existing,
                ];
            }
        }

        $packageId = (int)($meta['package_id'] ?? 0);
        $mac = trim((string)($meta['mac'] ?? ''));
        $ip = trim((string)($meta['ip'] ?? ''));

        if ($packageId <= 0 || $mac === '' || $ip === '') {
            return [
                'connected' => false,
                'message' => 'Payment context missing package/mac/ip details.',
            ];
        }

        $package = Package::find($packageId);
        if (!$package) {
            return [
                'connected' => false,
                'message' => 'Package linked to payment was not found.',
            ];
        }

        if ($this->hasColumn('packages', 'category')) {
            $category = strtolower((string)($package->category ?? 'hotspot'));
            if ($category !== 'hotspot') {
                return [
                    'connected' => false,
                    'message' => 'Linked payment package is not hotspot category.',
                ];
            }
        }

        $demoIdentity = $this->buildDemoIdentity('hotspot', $mac);
        $username = $demoIdentity['username'];
        $password = $demoIdentity['password'];
        $profile = trim((string)(
            $package->mk_profile
            ?? $package->mikrotik_profile
            ?? 'default'
        ));
        $startedAt = $payment->completed_at ?: now();
        $expiresAt = $this->demoHotspotExpiresAt($package, $startedAt);

        DB::beginTransaction();
        try {
            try {
                $mikrotik->disconnectActiveUser($username, $mac);
            } catch (\Throwable $disconnectError) {
                Log::warning('Demo hotspot pre-disconnect failed after payment', [
                    'reference' => $payment->reference,
                    'username' => $username,
                    'mac' => $mac,
                    'error' => $disconnectError->getMessage(),
                ]);
            }

            $customer = $this->upsertDemoCustomer(
                username: $username,
                package: $package,
                ip: $ip,
                mode: 'hotspot',
            );

            $this->upsertDemoBilling($username, $customer, $package);
            $this->terminateActiveDemoConnections($username);

            $this->storeDemoSubscription(
                customer: $customer,
                package: $package,
                username: $username,
                password: $password,
                mac: $mac,
                profile: $profile,
                mode: 'hotspot',
                startedAt: $startedAt,
                expiresAt: $expiresAt,
                createNew: true,
                extraMeta: [
                    'demo_user' => true,
                    'simulated' => false,
                    'paid_via_demo_portal' => true,
                    'mode' => 'hotspot',
                    'identity_source' => (string)($meta['identity_source'] ?? 'synthetic'),
                    'ip' => $ip,
                    'payment_reference' => (string)($payment->reference ?? ''),
                    'msisdn' => (string)($payment->msisdn ?? ''),
                ],
            );

            $connection = $this->createHotspotConnectionRecord(
                package: $package,
                mac: $mac,
                ip: $ip,
                username: $username,
                expiresAt: $expiresAt,
                startedAt: $startedAt,
            );

            $mikrotik->connectNamedHotspot(
                package: $package,
                username: $username,
                ip: $ip,
                mac: $mac,
                password: $password,
                comment: 'demo_user hotspot via /connect/demo',
            );

            $this->syncConnectionStartCounters($connection, $mikrotik->getHotspotUserStats($username));

            $updatedMeta = array_merge($meta, [
                'flow' => 'connect.demo.hotspot',
                'demo_user' => true,
                'demo_username' => $username,
                'identity_source' => (string)($meta['identity_source'] ?? 'synthetic'),
                'connection_id' => $connection->id,
                'connected_at' => now()->toDateTimeString(),
            ]);

            $payment->fill($this->onlyExistingColumns('megapayments', [
                'customer_id' => $customer->id,
                'payable_type' => Connection::class,
                'payable_id' => $connection->id,
                'channel' => 'demo_portal',
                'meta' => $updatedMeta,
            ]));
            $payment->save();

            DB::commit();

            return [
                'connected' => true,
                'reconnected' => false,
                'message' => 'Payment received. Demo hotspot session is active.',
                'connection' => $connection,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Demo hotspot activation failed after payment', [
                'reference' => $payment->reference,
                'mac' => $mac,
                'error' => $e->getMessage(),
            ]);

            return [
                'connected' => false,
                'message' => 'Payment confirmed, but demo session setup failed. Please retry.',
            ];
        }
    }

    private function portalPackages(string $category)
    {
        $packageQuery = Package::query()
            ->orderBy('price', 'asc')
            ->orderBy('name', 'asc');

        if ($this->hasColumn('packages', 'status')) {
            $packageQuery->where('status', '!=', 'archived');
        }
        if ($this->hasColumn('packages', 'is_active')) {
            $packageQuery->where('is_active', true);
        }
        if ($category !== '' && $this->hasColumn('packages', 'category')) {
            $packageQuery->where('category', $category);
        }

        return $packageQuery->get();
    }

    private function resolvePortalContext(Request $request, bool $allowSyntheticMac = false): array
    {
        $ip = trim((string)($request->input('ip', '') ?: $request->ip()));
        $mac = $this->normalizeMacAddress((string)$request->input('mac', ''));
        $macSource = $mac !== '' ? 'router' : 'missing';
        $macIsSynthetic = false;

        if ($mac === '' && $allowSyntheticMac) {
            $mac = $this->syntheticMacFromRequest($request, $ip);
            $macSource = 'synthetic';
            $macIsSynthetic = true;
        }

        return [
            'mac' => $mac,
            'ip' => $ip,
            'link_login' => (string)$request->input('link-login', ''),
            'mac_source' => $macSource,
            'mac_is_synthetic' => $macIsSynthetic,
        ];
    }

    private function normalizeMacAddress(string $value): string
    {
        $raw = strtolower(trim($value));
        if ($raw === '') {
            return '';
        }

        $hex = preg_replace('/[^0-9a-f]/', '', $raw);
        if (strlen($hex) !== 12) {
            return $raw;
        }

        return implode(':', str_split($hex, 2));
    }

    private function syntheticMacFromRequest(Request $request, string $ip): string
    {
        $seed = hash('sha256', implode('|', [
            'connect-demo',
            trim($ip),
            trim((string)$request->userAgent()),
            (string)config('app.key'),
        ]));

        $bytes = [];
        for ($i = 0; $i < 6; $i++) {
            $byte = hexdec(substr($seed, $i * 2, 2));
            if ($i === 0) {
                $byte = ($byte | 0x02) & 0xfe;
            }
            $bytes[] = sprintf('%02x', $byte);
        }

        return implode(':', $bytes);
    }

    private function resolveDemoPackage(string $mode, int $requestedPackageId = 0): ?Package
    {
        if ($requestedPackageId > 0) {
            $package = Package::find($requestedPackageId);
            if (!$package) {
                return null;
            }

            if ($this->hasColumn('packages', 'status') && strtolower((string)($package->status ?? 'active')) === 'archived') {
                return null;
            }
            if ($this->hasColumn('packages', 'is_active') && !(bool)($package->is_active ?? false)) {
                return null;
            }
            if ($this->hasColumn('packages', 'category')) {
                $category = strtolower((string)($package->category ?? 'hotspot'));
                if ($category !== $mode) {
                    return null;
                }
            }

            return $package;
        }

        return $this->portalPackages($mode)->first();
    }

    private function buildDemoIdentity(string $mode, string $mac): array
    {
        $suffix = strtolower(substr(preg_replace('/[^0-9a-f]/', '', $mac), -12));
        if ($suffix === '') {
            $suffix = substr(hash('sha256', $mode . '|' . $mac), 0, 12);
        }

        $modeKey = $mode === 'metered' ? 'm' : 'h';
        $username = 'demo_user_' . $modeKey . '_' . $suffix;

        return [
            'username' => $username,
            'password' => $mode === 'metered'
                ? self::DEMO_METERED_PORTAL_PASSWORD
                : 'Demo@' . strtoupper(substr(hash('sha256', $username . '|' . $suffix), 0, 10)),
        ];
    }

    private function upsertDemoCustomer(string $username, Package $package, string $ip, string $mode): Customer
    {
        $customer = $this->hasColumn('customers', 'username')
            ? Customer::query()->firstOrNew(['username' => $username])
            : new Customer();

        $defaultName = 'Demo User (' . ucfirst($mode) . ')';
        $payload = [
            'name' => trim((string)($customer->name ?? '')) !== '' ? $customer->name : $defaultName,
            'status' => 'active',
        ];

        if ($this->hasColumn('customers', 'username')) {
            $payload['username'] = $username;
        }
        if ($this->hasColumn('customers', 'package_id')) {
            $payload['package_id'] = $package->id;
        }
        if ($this->hasColumn('customers', 'ip')) {
            $payload['ip'] = $ip;
        }

        $customer->fill($this->onlyExistingColumns('customers', $payload));
        $customer->save();

        return $customer;
    }

    private function upsertDemoBilling(string $username, Customer $customer, Package $package): void
    {
        if (!$this->hasTable('hotspot_user_billings')) {
            return;
        }

        $values = [
            'customer_id' => $customer->id,
            // Legacy column name retained: hotspot uses flat package amount, metered uses per-MB rate.
            'rate_per_gb' => (float)($package->price ?? 0),
            'currency' => 'KES',
        ];

        if ($this->hasColumn('hotspot_user_billings', 'package_id')) {
            $values['package_id'] = $package->id;
        }
        if ($this->hasColumn('hotspot_user_billings', 'notify_customer')) {
            $values['notify_customer'] = false;
        }

        HotspotUserBilling::query()->updateOrCreate(
            ['username' => $username],
            $this->onlyExistingColumns('hotspot_user_billings', $values)
        );
    }

    private function terminateActiveDemoConnections(string $username): void
    {
        if (!$this->hasTable('connections') || !$this->hasColumn('connections', 'username')) {
            return;
        }

        $query = Connection::query()->where('username', $username);
        if ($this->hasColumn('connections', 'status')) {
            $query->where('status', 'active');
        }

        $query
            ->orderByDesc('id')
            ->get()
            ->each(function (Connection $connection) {
                $this->closeConnectionSession($connection, null, 'terminated');
            });
    }

    private function demoHotspotExpiresAt(Package $package, $startedAt = null)
    {
        $base = $startedAt ? Carbon::parse($startedAt) : now();
        $durationMinutes = $this->packageDurationMinutes($package);

        return $durationMinutes > 0
            ? $base->copy()->addMinutes($durationMinutes)
            : $base->copy()->addDays(30);
    }

    private function storeDemoSubscription(
        Customer $customer,
        Package $package,
        string $username,
        string $password,
        string $mac,
        string $profile,
        string $mode,
        $startedAt,
        $expiresAt,
        bool $createNew,
        array $extraMeta = []
    ): ?Subscription {
        if (!$this->hasTable('subscriptions')) {
            return null;
        }

        $values = $this->onlyExistingColumns('subscriptions', [
            'customer_id' => $customer->id,
            'package_id' => $package->id,
            'type' => $mode,
            'username' => $username,
            'password' => encrypt($password),
            'mac_address' => $mac,
            'mk_profile' => $profile,
            'starts_at' => $startedAt,
            'expires_at' => $expiresAt,
            'price_paid' => (float)($package->price ?? 0),
            'status' => 'active',
            'meta' => array_merge([
                'source' => 'connect.demo',
                'demo_user' => true,
            ], $extraMeta),
        ]);

        if ($createNew) {
            return Subscription::query()->create($values);
        }

        $match = [];
        if ($this->hasColumn('subscriptions', 'customer_id')) {
            $match['customer_id'] = $customer->id;
        }
        if ($this->hasColumn('subscriptions', 'type')) {
            $match['type'] = $mode;
        }
        if ($this->hasColumn('subscriptions', 'username')) {
            $match['username'] = $username;
        }

        if ($match === []) {
            return Subscription::query()->create($values);
        }

        return Subscription::query()->updateOrCreate($match, $values);
    }

    private function createMeteredConnectionRecord(
        Package $package,
        string $mac,
        string $ip,
        string $username,
        $startedAt = null
    ): Connection {
        $resolvedStartedAt = $startedAt ? Carbon::parse($startedAt) : now();

        $connection = Connection::create($this->onlyExistingColumns('connections', [
            'mac_address' => $mac,
            'ip_address' => $ip,
            'package_id' => $package->id,
            'username' => $username,
            'started_at' => $resolvedStartedAt,
            // Metered access is billing-cycle controlled, not short session-time controlled.
            'expires_at' => $resolvedStartedAt->copy()->addYears(10),
            'status' => 'active',
            'start_bytes_in' => 0,
            'start_bytes_out' => 0,
            'bytes_in' => 0,
            'bytes_out' => 0,
        ]));

        $this->recordConnectionUsageSample(
            connection: $connection,
            recordedAt: $resolvedStartedAt,
            uptimeSeconds: 0,
            bytesIn: 0,
            bytesOut: 0,
            source: 'session_start',
        );

        return $connection;
    }

    private function createDemoHotspotPayment(
        Customer $customer,
        Package $package,
        Connection $connection,
        string $username,
        array $portal
    ): ?MegaPayment {
        if (!$this->hasTable('megapayments')) {
            return null;
        }

        $reference = $this->nextDemoReference('hotspot');
        $durationMinutes = $this->packageDurationMinutes($package);
        $meta = [
            'flow' => 'connect.demo.hotspot',
            'demo_user' => true,
            'simulated' => true,
            'package_id' => $package->id,
            'package_name' => $package->name,
            'speed' => trim((string)($package->speed ?? $package->rate_limit ?? '')),
            'time_label' => $this->humanTime($durationMinutes),
            'duration_minutes' => $durationMinutes,
            'data_limit' => $package->data_limit ?? null,
            'username' => $username,
            'mac' => (string)($portal['mac'] ?? ''),
            'ip' => (string)($portal['ip'] ?? ''),
            'identity_source' => (string)($portal['mac_source'] ?? 'synthetic'),
            'connection_id' => $connection->id,
            'connected_at' => now()->toDateTimeString(),
        ];

        return MegaPayment::query()->create($this->onlyExistingColumns('megapayments', [
            'reference' => $reference,
            'purpose' => 'hotspot_access',
            'channel' => 'demo_portal',
            'payable_type' => Connection::class,
            'payable_id' => $connection->id,
            'customer_id' => $customer->id,
            'initiated_by' => auth()->check() ? auth()->id() : null,
            'msisdn' => null,
            'amount' => (float)($package->price ?? 0),
            'status' => 'completed',
            'response_code' => 0,
            'response_description' => 'Demo portal auto-authorized.',
            'mpesa_receipt' => 'DEMO-' . $connection->id,
            'transaction_id' => $reference,
            'meta' => $meta,
            'initiated_at' => now(),
            'completed_at' => now(),
        ]));
    }

    private function configuredDemoPin(): string
    {
        return trim((string)config('netbil.demo.pin', ''));
    }

    private function isDemoPinConfigured(): bool
    {
        return $this->configuredDemoPin() !== '';
    }

    private function demoRateLimitPerMinute(): int
    {
        $perMinute = (int)config('netbil.demo.rate_limit_per_minute', 6);
        return $perMinute > 0 ? $perMinute : 6;
    }

    private function demoPinValidationError(string $providedPin): ?array
    {
        $configuredPin = $this->configuredDemoPin();
        if ($configuredPin === '') {
            return [
                'status' => 503,
                'message' => 'Demo PIN is not configured. Set NETBIL_DEMO_PIN on the server first.',
            ];
        }

        if (!hash_equals($configuredPin, trim($providedPin))) {
            return [
                'status' => 422,
                'message' => 'Invalid demo PIN.',
            ];
        }

        return null;
    }

    private function demoMeteredAccessValidationError(string $username, string $password): ?string
    {
        $resolvedUsername = strtolower(trim($username));
        $resolvedPassword = trim($password);

        if ($resolvedUsername === '' || $resolvedPassword === '') {
            return 'Enter the demo metered credentials to continue.';
        }

        if (
            $resolvedUsername !== self::DEMO_METERED_PORTAL_USERNAME
            || $resolvedPassword !== self::DEMO_METERED_PORTAL_PASSWORD
        ) {
            return 'Use username "' . self::DEMO_METERED_PORTAL_USERNAME . '" and password "' . self::DEMO_METERED_PORTAL_PASSWORD . '" for metered demo access.';
        }

        return null;
    }

    private function nextDemoReference(string $mode): string
    {
        do {
            $reference = 'DEMO_' . strtoupper(substr($mode, 0, 2)) . '_' . now()->format('YmdHis') . '_' . strtoupper(Str::random(6));
        } while (MegaPayment::query()->where('reference', $reference)->exists());

        return $reference;
    }

    private function refreshMegaPaymentStatus(MegaPayment $payment, MegaPayClient $client): MegaPayment
    {
        $status = strtolower((string)$payment->status);
        if ($status !== 'pending') {
            return $payment;
        }

        if (!config('megapay.status_poll_enabled')) {
            return $payment;
        }

        $transactionRequestId = (string)($payment->transaction_request_id ?? '');
        if ($transactionRequestId === '') {
            return $payment;
        }

        try {
            $resp = $client->transactionStatus($transactionRequestId);
            $remoteStatus = strtolower((string)($resp['TransactionStatus'] ?? ''));
            $receipt = (string)($resp['TransactionReceipt'] ?? '');
            $resultDesc = (string)($resp['ResultDesc'] ?? '');

            if ($remoteStatus === 'completed') {
                $payment->fill($this->onlyExistingColumns('megapayments', [
                    'status' => 'completed',
                    'mpesa_receipt' => $receipt !== '' ? $receipt : $payment->mpesa_receipt,
                    'response_description' => $resultDesc !== '' ? $resultDesc : $payment->response_description,
                    'completed_at' => $payment->completed_at ?: now(),
                ]));
                $payment->save();
                return $payment;
            }

            if (in_array($remoteStatus, ['failed', 'cancelled', 'timeout', 'expired'], true)) {
                $payment->fill($this->onlyExistingColumns('megapayments', [
                    'status' => $remoteStatus,
                    'response_description' => $resultDesc !== '' ? $resultDesc : $payment->response_description,
                    'failed_at' => now(),
                ]));
                $payment->save();
            }
        } catch (\Throwable $e) {
            Log::warning('MegaPay status refresh failed for hotspot flow', [
                'reference' => $payment->reference,
                'error' => $e->getMessage(),
            ]);
        }

        return $payment->fresh() ?: $payment;
    }

    private function resolveMeteredPackage(Customer $customer, ?Subscription $subscription, string $username): ?Package
    {
        $packageId = null;

        if ($subscription && $this->hasColumn('subscriptions', 'package_id')) {
            $packageId = (int)($subscription->package_id ?? 0);
        }
        if ($packageId <= 0 && $this->hasColumn('customers', 'package_id')) {
            $packageId = (int)($customer->package_id ?? 0);
        }
        if ($packageId <= 0 && $this->hasTable('hotspot_user_billings') && $this->hasColumn('hotspot_user_billings', 'package_id')) {
            $billing = HotspotUserBilling::query()->where('username', $username)->first();
            $packageId = (int)($billing?->package_id ?? 0);
        }

        if ($packageId <= 0) {
            return null;
        }

        return Package::find($packageId);
    }

    private function upsertMeteredSubscription(
        Customer $customer,
        Package $package,
        string $username,
        string $password,
        string $mac,
        string $profile,
        string $ip
    ): void {
        if (!$this->hasTable('subscriptions')) {
            return;
        }

        $match = [];
        if ($this->hasColumn('subscriptions', 'customer_id')) {
            $match['customer_id'] = $customer->id;
        }
        if ($this->hasColumn('subscriptions', 'type')) {
            $match['type'] = 'metered';
        }
        if ($this->hasColumn('subscriptions', 'username')) {
            $match['username'] = $username;
        }
        if (empty($match)) {
            return;
        }

        $values = [];
        if ($this->hasColumn('subscriptions', 'customer_id')) {
            $values['customer_id'] = $customer->id;
        }
        if ($this->hasColumn('subscriptions', 'package_id')) {
            $values['package_id'] = $package->id;
        }
        if ($this->hasColumn('subscriptions', 'type')) {
            $values['type'] = 'metered';
        }
        if ($this->hasColumn('subscriptions', 'username')) {
            $values['username'] = $username;
        }
        if ($this->hasColumn('subscriptions', 'password')) {
            $values['password'] = encrypt($password);
        }
        if ($this->hasColumn('subscriptions', 'mac_address')) {
            $values['mac_address'] = $mac;
        }
        if ($this->hasColumn('subscriptions', 'mk_profile')) {
            $values['mk_profile'] = $profile;
        }
        if ($this->hasColumn('subscriptions', 'starts_at')) {
            $values['starts_at'] = now();
        }
        if ($this->hasColumn('subscriptions', 'expires_at')) {
            $values['expires_at'] = null;
        }
        if ($this->hasColumn('subscriptions', 'status')) {
            $values['status'] = 'active';
        }
        if ($this->hasColumn('subscriptions', 'meta')) {
            $values['meta'] = [
                'source' => 'connect.metered',
                'ip' => $ip,
            ];
        }

        if (!empty($values)) {
            Subscription::query()->updateOrCreate($match, $values);
        }
    }

    private function hasOverdueInvoices(int $customerId): bool
    {
        if ($customerId <= 0 || !$this->hasTable('invoices')) {
            return false;
        }

        $query = Invoice::query()->where('customer_id', $customerId);

        $hasInvoiceStatus = $this->hasColumn('invoices', 'invoice_status');
        $hasLegacyStatus = $this->hasColumn('invoices', 'status');
        $hasDueDate = $this->hasColumn('invoices', 'due_date');

        $query->where(function ($q) use ($hasInvoiceStatus, $hasLegacyStatus, $hasDueDate) {
            if ($hasInvoiceStatus) {
                $q->where('invoice_status', 'overdue');
                if ($hasDueDate) {
                    $q->orWhere(function ($inner) {
                        $inner->whereIn('invoice_status', ['unpaid', 'due', 'partial'])
                            ->whereDate('due_date', '<', now()->toDateString());
                    });
                }
            } elseif ($hasLegacyStatus) {
                $q->where('status', 'overdue');
                if ($hasDueDate) {
                    $q->orWhere(function ($inner) {
                        $inner->where('status', '!=', 'paid')
                            ->whereDate('due_date', '<', now()->toDateString());
                    });
                }
            } elseif ($hasDueDate) {
                $q->whereDate('due_date', '<', now()->toDateString());
            } else {
                $q->whereRaw('1 = 0');
            }
        });

        return $query->exists();
    }

    private function decryptSubscriptionPassword(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        try {
            return (string)Crypt::decryptString($value);
        } catch (\Throwable) {
            // Backward compatibility for records stored as plain text.
            return $value;
        }
    }

    private function packageDurationMinutes(Package $package): int
    {
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

        if (str_contains($raw, ':')) {
            $parts = array_map('intval', explode(':', $raw));
            if (count($parts) === 3) {
                return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
            }
            if (count($parts) === 2) {
                return ($parts[0] * 60) + $parts[1];
            }
        }

        return is_numeric($raw) ? (int)$raw : 0;
    }

    private function humanTime(int $minutes): string
    {
        if ($minutes <= 0) {
            return 'On demand';
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        if ($hours > 0 && $mins > 0) {
            return sprintf('%dh %dm', $hours, $mins);
        }
        if ($hours > 0) {
            return sprintf('%dh', $hours);
        }

        return sprintf('%dm', $mins);
    }

    private function hotspotUsernameFromMac(string $mac): string
    {
        return 'hs_' . Str::lower(str_replace(':', '', trim($mac)));
    }

    private function nextHotspotReference(int $packageId, string $mac): string
    {
        do {
            $seed = 'HS' . $packageId . strtoupper(substr(md5($mac . microtime(true) . Str::random(6)), 0, 6));
            $reference = 'HS_' . now()->format('YmdHis') . '_' . $seed;
        } while (MegaPayment::query()->where('reference', $reference)->exists());

        return $reference;
    }

    private function onlyExistingColumns(string $table, array $payload): array
    {
        $filtered = [];
        foreach ($payload as $column => $value) {
            if ($this->hasColumn($table, $column)) {
                $filtered[$column] = $value;
            }
        }

        return $filtered;
    }

    private function hasTable(string $table): bool
    {
        $key = 'table.' . $table;
        if (!array_key_exists($key, self::$schemaCache)) {
            try {
                self::$schemaCache[$key] = Schema::hasTable($table);
            } catch (\Throwable) {
                self::$schemaCache[$key] = false;
            }
        }

        return (bool)self::$schemaCache[$key];
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (!array_key_exists($key, self::$schemaCache)) {
            try {
                self::$schemaCache[$key] = Schema::hasColumn($table, $column);
            } catch (\Throwable) {
                self::$schemaCache[$key] = false;
            }
        }

        return (bool)self::$schemaCache[$key];
    }
}
