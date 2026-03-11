<?php

namespace App\Modules\PettyCash\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\Hostel;
use App\Modules\PettyCash\Models\Payment;
use App\Modules\PettyCash\Models\Spending;
use App\Modules\PettyCash\Models\SpendingAllocation;
use App\Modules\PettyCash\Support\ApiResponder;
use App\Modules\PettyCash\Services\FundsAllocatorService;
use App\Modules\PettyCash\Services\OntDirectoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TokenHostelController extends Controller
{
    use ApiResponder;

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $sortDue = strtolower(trim((string) $request->get('sort_due', '')));
        $supportsOntSiteColumns = $this->hostelOntColumnsAvailable();
        $perPageOptions = [15, 25, 30, 50, 100];
        $perPage = (int) $request->integer('per_page', 25);
        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 25;
        }
        if (!in_array($sortDue, ['', 'asc', 'desc'], true)) {
            $sortDue = '';
        }

        $semesterMonths = (int) config('pettycash.token_notifications.semester_months', 4);
        if ($semesterMonths < 1) {
            $semesterMonths = 4;
        }

        $lastPaySub = Payment::query()
            ->selectRaw('hostel_id, MAX(date) as last_payment_date')
            ->groupBy('hostel_id');

        $dueDateExpr = "CASE
            WHEN lp.last_payment_date IS NULL THEN NULL
            WHEN petty_hostels.stake = 'semester' THEN DATE_ADD(lp.last_payment_date, INTERVAL {$semesterMonths} MONTH)
            ELSE DATE_ADD(lp.last_payment_date, INTERVAL 1 MONTH)
        END";

        $hostels = Hostel::query()
            ->leftJoinSub($lastPaySub, 'lp', function ($join) {
                $join->on('lp.hostel_id', '=', 'petty_hostels.id');
            })
            ->select('petty_hostels.*', 'lp.last_payment_date')
            ->selectRaw("$dueDateExpr as due_date_sort")
            ->when($q !== '', function ($qq) use ($q, $supportsOntSiteColumns) {
                $qq->where(function ($w) use ($q, $supportsOntSiteColumns) {
                    $w->where('hostel_name', 'like', "%{$q}%")
                        ->orWhere('contact_person', 'like', "%{$q}%")
                        ->orWhere('meter_no', 'like', "%{$q}%")
                        ->orWhere('phone_no', 'like', "%{$q}%");

                    if ($supportsOntSiteColumns) {
                        $w->orWhere('ont_site_sn', 'like', "%{$q}%")
                            ->orWhere('ont_site_id', 'like', "%{$q}%");
                    }
                });
            })
            ->when($sortDue === 'asc', function ($qq) {
                $qq->orderByRaw('due_date_sort IS NULL ASC')
                    ->orderBy('due_date_sort', 'asc')
                    ->orderBy('hostel_name');
            }, function ($qq) use ($sortDue) {
                if ($sortDue === 'desc') {
                    $qq->orderByRaw('due_date_sort IS NULL ASC')
                        ->orderBy('due_date_sort', 'desc')
                        ->orderBy('hostel_name');
                } else {
                    $qq->orderBy('hostel_name');
                }
            })
            ->paginate($perPage)
            ->withQueryString();

        $items = $hostels->getCollection();
        $today = Carbon::today();

        $lastDatesByHostel = $items
            ->filter(fn ($h) => !empty($h->last_payment_date))
            ->mapWithKeys(fn ($h) => [$h->id => Carbon::parse($h->last_payment_date)->format('Y-m-d')])
            ->all();

        $lastPayments = collect();
        if (!empty($lastDatesByHostel)) {
            $pairs = [];
            foreach ($lastDatesByHostel as $hid => $date) {
                $pairs[] = [$hid, $date];
            }

            $query = Payment::query()
                ->where(function ($w) use ($pairs) {
                    foreach ($pairs as [$hid, $date]) {
                        $w->orWhere(function ($or) use ($hid, $date) {
                            $or->where('hostel_id', $hid)->whereDate('date', $date);
                        });
                    }
                })
                ->orderByDesc('id');

            foreach ($query->get() as $p) {
                if (!$lastPayments->has($p->hostel_id)) {
                    $lastPayments->put($p->hostel_id, $p);
                }
            }
        }

        $items = $items->map(function ($h) use ($today, $lastPayments, $semesterMonths) {
            $last = $lastPayments->get($h->id);
            $lastDate = $last?->date ? Carbon::parse($last->date)->startOfDay() : null;

            $nextDue = null;
            $daysToDue = null;
            $dueStatus = 'unknown';
            $dueBadge = 'No payments yet';

            if ($lastDate) {
                if (($h->stake ?: 'monthly') === 'semester') {
                    $nextDue = $lastDate->copy()->addMonthsNoOverflow($semesterMonths)->startOfDay();
                } else {
                    $nextDue = $lastDate->copy()->addMonthNoOverflow()->startOfDay();
                }

                $daysToDue = $today->diffInDays($nextDue, false);
                $d = (int) $daysToDue;

                if ($d === 3) { $dueBadge = 'Due in 3 days'; $dueStatus = 'upcoming'; }
                elseif ($d === 2) { $dueBadge = 'Due in 2 days'; $dueStatus = 'upcoming'; }
                elseif ($d === 1) { $dueBadge = 'Due tomorrow'; $dueStatus = 'upcoming'; }
                elseif ($d === 0) { $dueBadge = 'Due today'; $dueStatus = 'due_today'; }
                elseif ($d < 0) { $dueBadge = 'Overdue by ' . abs($d) . ' day' . (abs($d) === 1 ? '' : 's'); $dueStatus = 'overdue'; }
                else { $dueBadge = 'Due in ' . $d . ' days'; $dueStatus = 'upcoming'; }
            }

            return [
                'id' => $h->id,
                'hostel_name' => $h->hostel_name,
                'contact_person' => $h->contact_person,
                'ont_site_id' => $h->ont_site_id,
                'ont_site_sn' => $h->ont_site_sn,
                'meter_no' => $h->meter_no,
                'phone_no' => $h->phone_no,
                'no_of_routers' => (int) ($h->no_of_routers ?? 0),
                'stake' => $h->stake,
                'amount_due' => (float) ($h->amount_due ?? 0),
                'ont_merged' => (bool) ($h->ont_merged ?? false),
                'last_payment_amount' => $last ? (float) $last->amount : null,
                'last_payment_date' => $last?->date?->format('Y-m-d'),
                'next_due_date' => $nextDue ? $nextDue->format('Y-m-d') : null,
                'days_to_due' => $daysToDue,
                'due_status' => $dueStatus,
                'due_badge' => $dueBadge,
            ];
        });

        $hostels->setCollection($items);

        return $this->successResponse([
            'hostels' => $hostels->items(),
            'summary_current_page' => [
                'due_today' => $items->where('due_status', 'due_today')->count(),
                'overdue' => $items->where('due_status', 'overdue')->count(),
                'upcoming' => $items->where('due_status', 'upcoming')->count(),
                'unknown' => $items->where('due_status', 'unknown')->count(),
            ],
            'filters' => [
                'q' => $q,
                'sort_due' => $sortDue,
                'per_page' => $perPage,
            ],
        ], 'Hostels fetched.', 200, [
            'pagination' => $this->paginationMeta($hostels),
        ]);
    }

    public function show(Request $request, Hostel $hostel)
    {
        $paymentsPerPageOptions = [10, 20, 50, 100];
        $paymentsPerPage = (int) $request->integer('payments_per_page', 20);
        if (!in_array($paymentsPerPage, $paymentsPerPageOptions, true)) {
            $paymentsPerPage = 20;
        }

        $payments = Payment::query()
            ->with('batch')
            ->where('hostel_id', $hostel->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($paymentsPerPage)
            ->withQueryString();

        $hostelSnapshot = $this->buildHostelSnapshot($hostel);

        return $this->successResponse([
            'hostel' => $hostelSnapshot,
            'payments' => collect($payments->items())->map(function ($p) {
                return [
                    'id' => $p->id,
                    'spending_id' => $p->spending_id,
                    'reference' => $p->reference,
                    'amount' => (float) ($p->amount ?? 0),
                    'transaction_cost' => (float) ($p->transaction_cost ?? 0),
                    'total' => (float) ($p->amount ?? 0) + (float) ($p->transaction_cost ?? 0),
                    'batch_id' => $p->batch_id,
                    'batch_no' => $p->batch?->batch_no,
                    'date' => optional($p->date)->format('Y-m-d'),
                    'receiver_name' => $p->receiver_name,
                    'receiver_phone' => $p->receiver_phone,
                    'notes' => $p->notes,
                    'recorded_by' => $p->recorded_by,
                ];
            })->values(),
        ], 'Hostel fetched.', 200, [
            'pagination' => $this->paginationMeta($payments),
        ]);
    }

    public function storeHostel(Request $request)
    {
        if ($deny = $this->denyIfRoleNotIn($request, ['admin', 'accountant', 'finance'])) return $deny;

        $data = $request->validate([
            'hostel_name' => ['nullable', 'string', 'max:255'],
            'ont_key' => ['nullable', 'string', 'max:120'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'meter_no' => ['required', 'string', 'max:255'],
            'phone_no' => ['nullable', 'string', 'max:255'],
            'no_of_routers' => ['nullable', 'integer', 'min:0'],
            'stake' => ['required', 'in:monthly,semester'],
            'amount_due' => ['required', 'numeric', 'min:0'],
        ]);

        [$candidate, $validationError] = $this->resolveOntCandidate(
            (string) ($data['hostel_name'] ?? ''),
            (string) ($data['ont_key'] ?? '')
        );
        if ($validationError !== null) {
            return $this->errorResponse('Validation failed.', 422, [
                'hostel_name' => [$validationError],
            ]);
        }

        if ($candidate !== null) {
            $data['hostel_name'] = (string) ($candidate['hostel_name'] ?? '');
        }

        if (trim((string) ($data['hostel_name'] ?? '')) === '') {
            return $this->errorResponse('Validation failed.', 422, [
                'hostel_name' => ['Hostel name is required.'],
            ]);
        }

        $payload = [
            'hostel_name' => $data['hostel_name'],
            'contact_person' => $data['contact_person'] ?? null,
            'meter_no' => $data['meter_no'],
            'phone_no' => $data['phone_no'] ?? null,
            'no_of_routers' => $this->resolveRoutersInput($data),
            'stake' => $data['stake'],
            'amount_due' => $data['amount_due'],
        ];
        if ($this->hostelOntColumnsAvailable()) {
            $payload['ont_site_id'] = (string) ($candidate['site_id'] ?? '') !== '' ? (string) $candidate['site_id'] : null;
            $payload['ont_site_sn'] = (string) ($candidate['site_sn'] ?? '') !== '' ? (string) $candidate['site_sn'] : null;
        }
        if ($this->hostelOntMergedColumnAvailable()) {
            $payload['ont_merged'] = (bool) ($candidate !== null);
        }

        $hostel = Hostel::create($payload);

        return $this->successResponse([
            'hostel' => $this->buildHostelSnapshot($hostel),
        ], 'Hostel created.', 201);
    }

    public function updateHostel(Request $request, Hostel $hostel)
    {
        if ($deny = $this->denyIfRoleNotIn($request, ['admin', 'accountant', 'finance'])) return $deny;

        $data = $request->validate([
            'hostel_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'ont_key' => ['sometimes', 'nullable', 'string', 'max:120'],
            'contact_person' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meter_no' => ['sometimes', 'required', 'string', 'max:255'],
            'phone_no' => ['sometimes', 'nullable', 'string', 'max:255'],
            'no_of_routers' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'stake' => ['sometimes', 'required', 'in:monthly,semester'],
            'amount_due' => ['sometimes', 'required', 'numeric', 'min:0'],
        ]);

        if (empty($data)) {
            return $this->errorResponse('No update fields supplied.', 422, [
                'payload' => ['Provide at least one field to update.'],
            ]);
        }

        $candidate = null;
        if (array_key_exists('hostel_name', $data) || array_key_exists('ont_key', $data)) {
            [$candidate, $validationError] = $this->resolveOntCandidate(
                (string) ($data['hostel_name'] ?? ''),
                (string) ($data['ont_key'] ?? '')
            );
            if ($validationError !== null) {
                return $this->errorResponse('Validation failed.', 422, [
                    'hostel_name' => [$validationError],
                ]);
            }

            if ($candidate !== null) {
                $data['hostel_name'] = (string) ($candidate['hostel_name'] ?? '');
                if ($this->hostelOntColumnsAvailable()) {
                    $data['ont_site_id'] = (string) ($candidate['site_id'] ?? '') !== '' ? (string) $candidate['site_id'] : ($hostel->ont_site_id ?? null);
                    $data['ont_site_sn'] = (string) ($candidate['site_sn'] ?? '') !== '' ? (string) $candidate['site_sn'] : ($hostel->ont_site_sn ?? null);
                }
            }
        }

        if (array_key_exists('hostel_name', $data) && trim((string) $data['hostel_name']) === '') {
            return $this->errorResponse('Validation failed.', 422, [
                'hostel_name' => ['Hostel name is required.'],
            ]);
        }

        $effectiveMeterNo = array_key_exists('meter_no', $data)
            ? trim((string) $data['meter_no'])
            : trim((string) $hostel->meter_no);

        if ($effectiveMeterNo === '') {
            return $this->errorResponse('Validation failed.', 422, [
                'meter_no' => ['Meter number is required.'],
            ]);
        }

        unset($data['ont_key']);

        $hostel->fill($data);
        if ($candidate !== null && $this->hostelOntMergedColumnAvailable()) {
            $hostel->ont_merged = true;
        }
        $hostel->save();

        return $this->successResponse([
            'hostel' => $this->buildHostelSnapshot($hostel->fresh()),
        ], 'Hostel updated.');
    }

    public function mergeHostelOnt(Request $request, Hostel $hostel)
    {
        if ($deny = $this->denyIfRoleNotIn($request, ['admin', 'accountant', 'finance'])) return $deny;

        if ((bool) ($hostel->ont_merged ?? false)) {
            return $this->errorResponse('Hostel already merged from ONT directory.', 409);
        }

        $data = $request->validate([
            'ont_key' => ['required', 'string', 'max:120'],
            'hostel_name' => ['nullable', 'string', 'max:255'],
            'no_of_routers' => ['nullable', 'integer', 'min:0'],
        ]);

        [$candidate, $validationError] = $this->resolveOntCandidate(
            (string) ($data['hostel_name'] ?? ''),
            (string) ($data['ont_key'] ?? '')
        );
        if ($validationError !== null || $candidate === null) {
            return $this->errorResponse('Validation failed.', 422, [
                'ont_key' => [$validationError ?? 'Selected ONT was not found in the directory.'],
            ]);
        }

        $payload = [
            'hostel_name' => (string) ($candidate['hostel_name'] ?? $hostel->hostel_name),
            'no_of_routers' => $this->resolveRoutersInput($data, (int) ($hostel->no_of_routers ?? 0)),
        ];
        if ($this->hostelOntColumnsAvailable()) {
            $payload['ont_site_id'] = (string) ($candidate['site_id'] ?? '') !== '' ? (string) $candidate['site_id'] : ($hostel->ont_site_id ?? null);
            $payload['ont_site_sn'] = (string) ($candidate['site_sn'] ?? '') !== '' ? (string) $candidate['site_sn'] : ($hostel->ont_site_sn ?? null);
        }
        if ($this->hostelOntMergedColumnAvailable()) {
            $payload['ont_merged'] = true;
        }

        $hostel->fill($payload);
        $hostel->save();

        return $this->successResponse([
            'hostel' => $this->buildHostelSnapshot($hostel->fresh()),
            'merged_ont' => [
                'key' => (string) ($candidate['key'] ?? ''),
                'hostel_name' => (string) ($candidate['hostel_name'] ?? ''),
                'site_id' => (string) ($candidate['site_id'] ?? ''),
                'site_sn' => (string) ($candidate['site_sn'] ?? ''),
                'router_count_suggestion' => (int) ($candidate['router_count_suggestion'] ?? 0),
            ],
        ], 'Hostel merged from ONT directory.');
    }

    public function ontCatalog(Request $request)
    {
        /** @var OntDirectoryService $directory */
        $directory = app(OntDirectoryService::class);
        $q = trim((string) $request->query('q', $request->query('search', '')));
        $limit = max(5, min(100, (int) $request->integer('limit', 30)));
        $catalog = $directory->searchCatalog($q, $limit, $request->boolean('refresh'));

        if (!($catalog['available'] ?? false)) {
            return $this->errorResponse((string) ($catalog['message'] ?? 'ONT directory unavailable.'), 503, [
                'onts' => [],
            ]);
        }

        return $this->successResponse([
            'query' => $q,
            'fetched_at' => $catalog['fetched_at'],
            'source_count' => (int) ($catalog['source_count'] ?? 0),
            'onts' => collect($catalog['hostels'] ?? [])->map(function ($row) {
                return [
                    'key' => (string) ($row['key'] ?? ''),
                    'hostel_name' => (string) ($row['hostel_name'] ?? ''),
                    'site_id' => $row['site_id'] ?? null,
                    'site_sn' => $row['site_sn'] ?? null,
                    'router_count_suggestion' => (int) ($row['router_count_suggestion'] ?? 0),
                ];
            })->values(),
        ], 'ONT catalog fetched.');
    }

    public function destroyHostel(Request $request, Hostel $hostel)
    {
        if ($deny = $this->denyIfRoleNotIn($request, ['admin'])) return $deny;

        $hasPayments = Payment::query()->where('hostel_id', $hostel->id)->exists();
        $hasTokenSpendings = Spending::query()
            ->where('type', 'token')
            ->where('sub_type', 'hostel')
            ->where('related_id', $hostel->id)
            ->exists();

        if ($hasPayments || $hasTokenSpendings) {
            return $this->errorResponse(
                'Cannot delete hostel with recorded transactions.',
                409
            );
        }

        $hostel->delete();

        return $this->successResponse([], 'Hostel deleted.');
    }

    public function storePayment(Request $request, Hostel $hostel)
    {
        if ($deny = $this->denyIfRoleNotIn($request, ['admin', 'accountant', 'finance'])) return $deny;
        $user = $request->attributes->get('pettyUser');
        $supportsSpendingLink = $this->paymentSupportsSpendingLink();

        $data = $request->validate([
            'funding' => ['required', 'in:auto,single'],
            'batch_id' => ['nullable', 'integer', 'exists:petty_batches,id'],

            'reference' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_cost' => ['nullable', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'receiver_name' => ['nullable', 'string', 'max:255'],
            'receiver_phone' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:255'],

            'meter_no' => ['required', 'string', 'max:64'],
        ]);

        if ($data['funding'] === 'single' && empty($data['batch_id'])) {
            return $this->errorResponse('Validation failed.', 422, [
                'batch_id' => ['Batch is required in single funding mode.'],
            ]);
        }

        $fee = (float) ($data['transaction_cost'] ?? 0);
        $amount = (float) $data['amount'];
        $meterNo = trim((string) $data['meter_no']);
        if ($meterNo === '') {
            return $this->errorResponse('Validation failed.', 422, [
                'meter_no' => ['Meter number is required.'],
            ]);
        }

        $allocator = app(FundsAllocatorService::class);

        if ($data['funding'] === 'auto') {
            $required = $amount + $fee;
            $available = (float) $allocator->totalNetBalance();

            if ($required > $available) {
                return $this->errorResponse('Insufficient total balance.', 422, [
                    'amount' => [
                        'Needed: ' . number_format($required, 2) . '. Available: ' . number_format($available, 2),
                    ],
                ]);
            }
        }

        $payment = null;
        $spending = null;
        $allocations = [];

        try {
            DB::transaction(function () use (
                $hostel,
                $data,
                $fee,
                $amount,
                $meterNo,
                $allocator,
                $user,
                $supportsSpendingLink,
                &$payment,
                &$spending,
                &$allocations
            ) {
                if (trim((string) $hostel->meter_no) !== $meterNo) {
                    $hostel->meter_no = $meterNo;
                    $hostel->save();
                }

                $spending = Spending::create([
                    'batch_id' => null,
                    'type' => 'token',
                    'sub_type' => 'hostel',
                    'reference' => $data['reference'],
                    'meter_no' => ($meterNo !== '' ? $meterNo : null),
                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'date' => $data['date'],
                    'description' => 'Token payment: ' . $hostel->hostel_name,
                    'related_id' => $hostel->id,
                ]);

                $onlyBatch = ($data['funding'] === 'single') ? (int) $data['batch_id'] : null;
                $alloc = $allocator->allocateSmallestFirst($spending, $amount, $fee, $onlyBatch);
                $allocations = $alloc['allocations'] ?? [];

                $paymentPayload = [
                    'hostel_id' => $hostel->id,
                    'batch_id' => $spending->batch_id,
                    'reference' => $data['reference'],
                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'date' => $data['date'],
                    'receiver_name' => $data['receiver_name'] ?? null,
                    'receiver_phone' => $data['receiver_phone'],
                    'notes' => $data['notes'] ?? null,
                    'recorded_by' => $user->id,
                ];
                if ($supportsSpendingLink) {
                    $paymentPayload['spending_id'] = $spending->id;
                }

                $payment = Payment::create($paymentPayload);
            });
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to record payment.', 422, [
                'payment' => [$e->getMessage()],
            ]);
        }

        $payment->load('batch');
        $spending->load('allocations');

        return $this->successResponse([
            'hostel' => $this->buildHostelSnapshot($hostel->fresh()),
            'payment' => $this->mapPayment($payment),
            'spending' => $this->mapSpending($spending),
            'allocations' => collect($allocations)->map(function ($row) {
                return [
                    'batch_id' => (int) ($row['batch_id'] ?? 0),
                    'amount' => (float) ($row['amount'] ?? 0),
                    'transaction_cost' => (float) ($row['transaction_cost'] ?? 0),
                    'total' => (float) ($row['amount'] ?? 0) + (float) ($row['transaction_cost'] ?? 0),
                ];
            })->values(),
            'supports_spending_link' => $supportsSpendingLink,
        ], 'Payment recorded and allocated.', 201);
    }

    public function updatePayment(Request $request, Payment $payment)
    {
        if ($deny = $this->denyIfRoleNotIn($request, ['admin', 'accountant', 'finance'])) return $deny;
        $user = $request->attributes->get('pettyUser');

        if (!$this->paymentSupportsSpendingLink()) {
            return $this->errorResponse(
                'Payment edit requires spending-link migration (petty_payments.spending_id).',
                409
            );
        }

        if (!$payment->spending_id) {
            return $this->errorResponse(
                'This payment is legacy (not linked to a spending record) and cannot be edited via API.',
                409
            );
        }

        $spending = Spending::query()->with('allocations')->find($payment->spending_id);
        if (!$spending) {
            return $this->errorResponse('Linked spending record not found.', 409);
        }

        $data = $request->validate([
            'funding' => ['required', 'in:auto,single'],
            'batch_id' => ['nullable', 'integer', 'exists:petty_batches,id'],

            'reference' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_cost' => ['nullable', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'receiver_name' => ['nullable', 'string', 'max:255'],
            'receiver_phone' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:255'],
            'meter_no' => ['required', 'string', 'max:64'],
        ]);

        if ($data['funding'] === 'single' && empty($data['batch_id'])) {
            return $this->errorResponse('Validation failed.', 422, [
                'batch_id' => ['Batch is required in single funding mode.'],
            ]);
        }

        $fee = (float) ($data['transaction_cost'] ?? 0);
        $amount = (float) $data['amount'];
        $oldTotal = (float) $spending->amount + (float) ($spending->transaction_cost ?? 0);

        $hostel = Hostel::query()->findOrFail($payment->hostel_id);
        $meterNo = trim((string) $data['meter_no']);
        if ($meterNo === '') {
            return $this->errorResponse('Validation failed.', 422, [
                'meter_no' => ['Meter number is required.'],
            ]);
        }
        $allocator = app(FundsAllocatorService::class);

        if ($data['funding'] === 'auto') {
            $required = $amount + $fee;
            $availableAfterRelease = (float) $allocator->totalNetBalance() + $oldTotal;
            if ($required > $availableAfterRelease) {
                return $this->errorResponse('Insufficient total balance.', 422, [
                    'amount' => [
                        'Needed: ' . number_format($required, 2) . '. Available after release: ' . number_format($availableAfterRelease, 2),
                    ],
                ]);
            }
        }

        $allocations = [];
        try {
            DB::transaction(function () use (
                &$spending,
                &$payment,
                $hostel,
                $data,
                $amount,
                $fee,
                $meterNo,
                $allocator,
                $user,
                &$allocations
            ) {
                if (trim((string) $hostel->meter_no) !== $meterNo) {
                    $hostel->meter_no = $meterNo;
                    $hostel->save();
                }

                $spending->fill([
                    'reference' => $data['reference'],
                    'meter_no' => ($meterNo !== '' ? $meterNo : null),
                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'date' => $data['date'],
                ]);
                $spending->save();

                $onlyBatch = ($data['funding'] === 'single') ? (int) $data['batch_id'] : null;
                $alloc = $allocator->allocateSmallestFirst($spending, $amount, $fee, $onlyBatch);
                $allocations = $alloc['allocations'] ?? [];

                $payment->fill([
                    'batch_id' => $spending->batch_id,
                    'reference' => $data['reference'],
                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'date' => $data['date'],
                    'receiver_name' => $data['receiver_name'] ?? null,
                    'receiver_phone' => $data['receiver_phone'],
                    'notes' => $data['notes'] ?? null,
                    'recorded_by' => $user->id,
                ]);
                $payment->save();
            });
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to update payment.', 422, [
                'payment' => [$e->getMessage()],
            ]);
        }

        $payment->load('batch');
        $spending->refresh()->load('allocations');

        return $this->successResponse([
            'hostel' => $this->buildHostelSnapshot($hostel->fresh()),
            'payment' => $this->mapPayment($payment),
            'spending' => $this->mapSpending($spending),
            'allocations' => collect($allocations)->map(function ($row) {
                return [
                    'batch_id' => (int) ($row['batch_id'] ?? 0),
                    'amount' => (float) ($row['amount'] ?? 0),
                    'transaction_cost' => (float) ($row['transaction_cost'] ?? 0),
                    'total' => (float) ($row['amount'] ?? 0) + (float) ($row['transaction_cost'] ?? 0),
                ];
            })->values(),
        ], 'Payment updated.');
    }

    public function destroyPayment(Request $request, Payment $payment)
    {
        if ($deny = $this->denyIfRoleNotIn($request, ['admin'])) return $deny;

        if (!$this->paymentSupportsSpendingLink()) {
            return $this->errorResponse(
                'Payment delete requires spending-link migration (petty_payments.spending_id).',
                409
            );
        }

        if (!$payment->spending_id) {
            return $this->errorResponse(
                'This payment is legacy (not linked to a spending record) and cannot be deleted via API.',
                409
            );
        }

        $spending = Spending::query()->find($payment->spending_id);
        if (!$spending) {
            return $this->errorResponse('Linked spending record not found.', 409);
        }

        $hostel = Hostel::query()->find($payment->hostel_id);

        DB::transaction(function () use ($payment, $spending) {
            SpendingAllocation::query()->where('spending_id', $spending->id)->delete();
            $payment->delete();
            $spending->delete();
        });

        return $this->successResponse([
            'hostel' => $hostel ? $this->buildHostelSnapshot($hostel->fresh()) : null,
        ], 'Payment deleted.');
    }

    public function availableBatches()
    {
        $allocator = app(FundsAllocatorService::class);
        $batches = $allocator->batchesWithNetAvailable();

        return $this->successResponse([
            'total_net_balance' => (float) $allocator->totalNetBalance(),
            'batches' => $batches->map(function ($b) {
                return [
                    'id' => $b->id,
                    'batch_no' => $b->batch_no,
                    'available_balance' => round((float) ($b->available_balance ?? 0), 2),
                    'created_at' => optional($b->created_at)->format('Y-m-d H:i:s'),
                ];
            })->values(),
        ]);
    }

    /**
     * @return array{0:array<string,mixed>|null,1:string|null}
     */
    private function resolveOntCandidate(string $hostelName, string $ontKey = ''): array
    {
        /** @var OntDirectoryService $directory */
        $directory = app(OntDirectoryService::class);
        $match = $directory->findCandidate($ontKey, $hostelName);
        $catalog = (array) ($match['catalog'] ?? []);
        $candidate = $match['candidate'] ?? null;
        $available = (bool) ($catalog['available'] ?? false);

        if (!$available) {
            if ($directory->strictValidationEnabled()) {
                $message = (string) ($catalog['message'] ?? 'ONT directory is unavailable.');
                return [null, $message];
            }

            return [null, null];
        }

        if ($candidate === null) {
            if (trim($ontKey) === '') {
                return [null, 'Select ONT/site from the list.'];
            }

            return [null, 'Invalid ONT/site selection.'];
        }

        return [$candidate, null];
    }

    /**
     * @param array<string,mixed> $data
     */
    private function resolveRoutersInput(array $data, int $fallback = 0): int
    {
        if (array_key_exists('no_of_routers', $data) && $data['no_of_routers'] !== null && $data['no_of_routers'] !== '') {
            return max(0, (int) $data['no_of_routers']);
        }

        return max(0, $fallback);
    }

    private function mapPayment(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'spending_id' => $payment->spending_id,
            'hostel_id' => $payment->hostel_id,
            'batch_id' => $payment->batch_id,
            'batch_no' => $payment->batch?->batch_no,
            'reference' => $payment->reference,
            'amount' => (float) $payment->amount,
            'transaction_cost' => (float) ($payment->transaction_cost ?? 0),
            'total' => (float) $payment->amount + (float) ($payment->transaction_cost ?? 0),
            'date' => optional($payment->date)->format('Y-m-d'),
            'receiver_name' => $payment->receiver_name,
            'receiver_phone' => $payment->receiver_phone,
            'notes' => $payment->notes,
            'recorded_by' => $payment->recorded_by,
        ];
    }

    private function mapSpending(Spending $spending): array
    {
        return [
            'id' => $spending->id,
            'batch_id' => $spending->batch_id,
            'amount' => (float) $spending->amount,
            'transaction_cost' => (float) ($spending->transaction_cost ?? 0),
            'total' => (float) $spending->amount + (float) ($spending->transaction_cost ?? 0),
            'date' => optional($spending->date)->format('Y-m-d'),
            'meter_no' => $spending->meter_no,
            'reference' => $spending->reference,
        ];
    }

    private function denyIfRoleNotIn(Request $request, array $roles)
    {
        $user = $request->attributes->get('pettyUser');
        $role = (string) ($user?->role ?? '');

        if (!in_array($role, $roles, true)) {
            return $this->errorResponse('Forbidden.', 403);
        }

        return null;
    }

    private function buildHostelSnapshot(Hostel $hostel): array
    {
        $latest = Payment::query()
            ->where('hostel_id', $hostel->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->first();

        $semesterMonths = (int) config('pettycash.token_notifications.semester_months', 4);
        if ($semesterMonths < 1) {
            $semesterMonths = 4;
        }

        $today = Carbon::today();
        $lastDate = $latest?->date ? Carbon::parse($latest->date)->startOfDay() : null;
        $nextDue = null;
        $daysToDue = null;
        $dueStatus = 'unknown';
        $dueBadge = 'No payments yet';

        if ($lastDate) {
            if (($hostel->stake ?: 'monthly') === 'semester') {
                $nextDue = $lastDate->copy()->addMonthsNoOverflow($semesterMonths)->startOfDay();
            } else {
                $nextDue = $lastDate->copy()->addMonthNoOverflow()->startOfDay();
            }

            $daysToDue = $today->diffInDays($nextDue, false);
            if ($daysToDue === 3) { $dueBadge = 'Due in 3 days'; $dueStatus = 'upcoming'; }
            elseif ($daysToDue === 2) { $dueBadge = 'Due in 2 days'; $dueStatus = 'upcoming'; }
            elseif ($daysToDue === 1) { $dueBadge = 'Due tomorrow'; $dueStatus = 'upcoming'; }
            elseif ($daysToDue === 0) { $dueBadge = 'Due today'; $dueStatus = 'due_today'; }
            elseif ($daysToDue < 0) { $dueBadge = 'Overdue by ' . abs($daysToDue) . ' day' . (abs($daysToDue) === 1 ? '' : 's'); $dueStatus = 'overdue'; }
            else { $dueBadge = 'Due in ' . $daysToDue . ' days'; $dueStatus = 'upcoming'; }
        }

        return [
            'id' => $hostel->id,
            'hostel_name' => $hostel->hostel_name,
            'contact_person' => $hostel->contact_person,
            'ont_site_id' => $hostel->ont_site_id,
            'ont_site_sn' => $hostel->ont_site_sn,
            'meter_no' => $hostel->meter_no,
            'phone_no' => $hostel->phone_no,
            'no_of_routers' => (int) ($hostel->no_of_routers ?? 0),
            'stake' => $hostel->stake,
            'amount_due' => (float) ($hostel->amount_due ?? 0),
            'ont_merged' => (bool) ($hostel->ont_merged ?? false),
            'last_payment_amount' => $latest ? (float) $latest->amount : null,
            'last_payment_date' => $latest?->date?->format('Y-m-d'),
            'next_due_date' => $nextDue ? $nextDue->format('Y-m-d') : null,
            'days_to_due' => $daysToDue,
            'due_status' => $dueStatus,
            'due_badge' => $dueBadge,
        ];
    }

    private function paymentSupportsSpendingLink(): bool
    {
        static $supports = null;

        if ($supports === null) {
            $supports = Schema::hasColumn('petty_payments', 'spending_id');
        }

        return $supports;
    }

    private function hostelOntColumnsAvailable(): bool
    {
        static $supports = null;

        if ($supports === null) {
            $supports = Schema::hasColumn('petty_hostels', 'ont_site_id')
                && Schema::hasColumn('petty_hostels', 'ont_site_sn');
        }

        return $supports;
    }

    private function hostelOntMergedColumnAvailable(): bool
    {
        static $supports = null;

        if ($supports === null) {
            $supports = Schema::hasColumn('petty_hostels', 'ont_merged');
        }

        return $supports;
    }
}
