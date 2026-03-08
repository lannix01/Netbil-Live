<?php

namespace App\Modules\PettyCash\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\Hostel;
use App\Modules\PettyCash\Models\Payment;
use App\Modules\PettyCash\Models\Spending;
use App\Modules\PettyCash\Models\SpendingAllocation;
use App\Modules\PettyCash\Support\ApiResponder;
use App\Modules\PettyCash\Services\FundsAllocatorService;
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
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('hostel_name', 'like', "%{$q}%")
                        ->orWhere('meter_no', 'like', "%{$q}%")
                        ->orWhere('phone_no', 'like', "%{$q}%");
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
                'meter_no' => $h->meter_no,
                'phone_no' => $h->phone_no,
                'no_of_routers' => (int) ($h->no_of_routers ?? 0),
                'stake' => $h->stake,
                'amount_due' => (float) ($h->amount_due ?? 0),
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
            'hostel_name' => ['required', 'string', 'max:255'],
            'meter_no' => ['nullable', 'string', 'max:255'],
            'phone_no' => ['nullable', 'string', 'max:255'],
            'no_of_routers' => ['nullable', 'integer', 'min:0'],
            'stake' => ['required', 'in:monthly,semester'],
            'amount_due' => ['required', 'numeric', 'min:0'],
        ]);

        $hostel = Hostel::create([
            'hostel_name' => $data['hostel_name'],
            'meter_no' => $data['meter_no'] ?? null,
            'phone_no' => $data['phone_no'] ?? null,
            'no_of_routers' => $data['no_of_routers'] ?? 0,
            'stake' => $data['stake'],
            'amount_due' => $data['amount_due'],
        ]);

        return $this->successResponse([
            'hostel' => $this->buildHostelSnapshot($hostel),
        ], 'Hostel created.', 201);
    }

    public function updateHostel(Request $request, Hostel $hostel)
    {
        if ($deny = $this->denyIfRoleNotIn($request, ['admin', 'accountant', 'finance'])) return $deny;

        $data = $request->validate([
            'hostel_name' => ['sometimes', 'required', 'string', 'max:255'],
            'meter_no' => ['sometimes', 'nullable', 'string', 'max:255'],
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

        $hostel->fill($data);
        $hostel->save();

        return $this->successResponse([
            'hostel' => $this->buildHostelSnapshot($hostel->fresh()),
        ], 'Hostel updated.');
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

            'reference' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_cost' => ['nullable', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'receiver_name' => ['nullable', 'string', 'max:255'],
            'receiver_phone' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:255'],

            'meter_no' => ['nullable', 'string', 'max:64'],
        ]);

        if ($data['funding'] === 'single' && empty($data['batch_id'])) {
            return $this->errorResponse('Validation failed.', 422, [
                'batch_id' => ['Batch is required in single funding mode.'],
            ]);
        }

        $fee = (float) ($data['transaction_cost'] ?? 0);
        $amount = (float) $data['amount'];
        $meterNo = trim((string) (($data['meter_no'] ?? null) ?: ($hostel->meter_no ?? '')));

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
                $spending = Spending::create([
                    'batch_id' => null,
                    'type' => 'token',
                    'sub_type' => 'hostel',
                    'reference' => $data['reference'] ?? null,
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
                    'reference' => $data['reference'] ?? null,
                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'date' => $data['date'],
                    'receiver_name' => $data['receiver_name'] ?? null,
                    'receiver_phone' => $data['receiver_phone'] ?? null,
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

            'reference' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_cost' => ['nullable', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'receiver_name' => ['nullable', 'string', 'max:255'],
            'receiver_phone' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:255'],
            'meter_no' => ['nullable', 'string', 'max:64'],
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
        $meterNo = trim((string) (($data['meter_no'] ?? null) ?: ($hostel->meter_no ?? '')));
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
                $data,
                $amount,
                $fee,
                $meterNo,
                $allocator,
                $user,
                &$allocations
            ) {
                $spending->fill([
                    'reference' => $data['reference'] ?? null,
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
                    'reference' => $data['reference'] ?? null,
                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'date' => $data['date'],
                    'receiver_name' => $data['receiver_name'] ?? null,
                    'receiver_phone' => $data['receiver_phone'] ?? null,
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
            'meter_no' => $hostel->meter_no,
            'phone_no' => $hostel->phone_no,
            'no_of_routers' => (int) ($hostel->no_of_routers ?? 0),
            'stake' => $hostel->stake,
            'amount_due' => (float) ($hostel->amount_due ?? 0),
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
}
