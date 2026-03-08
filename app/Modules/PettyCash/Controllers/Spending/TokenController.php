<?php

namespace App\Modules\PettyCash\Controllers\Spending;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Modules\PettyCash\Models\Hostel;
use App\Modules\PettyCash\Models\Payment;
use App\Modules\PettyCash\Models\Spending;
use App\Modules\PettyCash\Services\FundsAllocatorService;
use App\Modules\PettyCash\Support\PettyAccess;
use App\Modules\PettyCash\Support\TabularExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class TokenController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $sortDue = strtolower(trim((string) $request->get('sort_due', 'asc')));
        $perPageOptions = [15, 25, 30, 50, 100];
        $perPage = (int) $request->integer('per_page', 25);
        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 25;
        }
        if (!in_array($sortDue, ['asc', 'desc'], true)) {
            $sortDue = 'asc';
        }

        // ✅ Total hostels in system (unfiltered) for counter card
        $totalHostels = Hostel::count();

        // Subquery: last payment date per hostel (fast, no N+1)
        $lastPaySub = Payment::query()
            ->selectRaw('hostel_id, MAX(date) as last_payment_date')
            ->groupBy('hostel_id');

        $dueDateExpr = "CASE
            WHEN lp.last_payment_date IS NULL THEN NULL
            WHEN petty_hostels.stake = 'semester' THEN DATE_ADD(lp.last_payment_date, INTERVAL 4 MONTH)
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
            ->when($sortDue === 'desc', function ($qq) {
                $qq->orderByRaw('due_date_sort IS NULL ASC')
                    ->orderBy('due_date_sort', 'desc')
                    ->orderBy('hostel_name');
            }, function ($qq) {
                $qq->orderByRaw('due_date_sort IS NULL ASC')
                    ->orderBy('due_date_sort', 'asc')
                    ->orderBy('hostel_name');
            })
            ->paginate($perPage)
            ->withQueryString();

        $pageHostels = $hostels->getCollection();

        // Map hostel_id => last_payment_date (Y-m-d)
        $lastDatesByHostel = $pageHostels
            ->filter(fn ($h) => !empty($h->last_payment_date))
            ->mapWithKeys(fn ($h) => [$h->id => Carbon::parse($h->last_payment_date)->format('Y-m-d')])
            ->all();

        // Pull last payment row for each hostel in ONE query:
        // We'll grab payments matching (hostel_id, date==last_payment_date) and then take the newest id.
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

        $today = Carbon::today();

        $pageHostels = $pageHostels->map(function ($h) use ($today, $lastPayments) {
            $last = $lastPayments->get($h->id);

            $h->last_payment_amount = $last ? (float) $last->amount : null;
            $h->last_payment_date = $last?->date?->format('Y-m-d');

            $lastDate = $last?->date ? Carbon::parse($last->date)->startOfDay() : null;

            if ($lastDate) {
                $stake = $h->stake ?: 'monthly';

                if ($stake === 'semester') {
                    // CHANGE 4 -> 6 if semester should be 6 months
                    $due = $lastDate->copy()->addMonthsNoOverflow(4)->startOfDay();
                } else {
                    $due = $lastDate->copy()->addMonthNoOverflow()->startOfDay();
                }

                $h->next_due_date = $due->format('Y-m-d');
                $h->days_to_due = $today->diffInDays($due, false);

                $d = (int) $h->days_to_due;
                if ($d === 3) { $h->due_badge = 'Due in 3 days'; $h->due_status = 'upcoming'; }
                elseif ($d === 2) { $h->due_badge = 'Due in 2 days'; $h->due_status = 'upcoming'; }
                elseif ($d === 1) { $h->due_badge = 'Due tomorrow'; $h->due_status = 'upcoming'; }
                elseif ($d === 0) { $h->due_badge = 'Due today'; $h->due_status = 'due_today'; }
                elseif ($d < 0) { $h->due_badge = 'Overdue by ' . abs($d) . ' day' . (abs($d) === 1 ? '' : 's'); $h->due_status = 'overdue'; }
                else { $h->due_badge = 'Due in ' . $d . ' days'; $h->due_status = 'upcoming'; }
            } else {
                $h->next_due_date = null;
                $h->days_to_due = null;
                $h->due_badge = 'No payments yet';
                $h->due_status = 'unknown';
            }

            return $h;
        });
        $hostels->setCollection($pageHostels);

        // Buckets for reminders banner
        $reminders = [
            'due_today' => $pageHostels->where('due_status', 'due_today')->values(),
            'due_1'     => $pageHostels->where('days_to_due', 1)->values(),
            'due_2'     => $pageHostels->where('days_to_due', 2)->values(),
            'due_3'     => $pageHostels->where('days_to_due', 3)->values(),
            'overdue'   => $pageHostels->where('due_status', 'overdue')->values(),
        ];

        return view('pettycash::spendings.tokens.index', compact(
            'hostels',
            'q',
            'sortDue',
            'reminders',
            'today',
            'totalHostels',
            'perPage',
            'perPageOptions'
        ));
    }

    public function create()
    {
        return view('pettycash::spendings.tokens.create');
    }

    public function storeHostel(Request $request)
    {
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

        if ((string) $request->input('after_save') === 'record_payment' && PettyAccess::allows(auth('petty')->user(), 'tokens.record_payment')) {
            return redirect()
                ->route('petty.tokens.hostels.show', $hostel->id)
                ->with('success', 'Hostel saved. You can record the payment now.');
        }

        return redirect()->route('petty.tokens.index')->with('success', 'Hostel saved.');
    }

    public function showHostel(Hostel $hostel)
    {
        $payments = Payment::with('batch')
            ->where('hostel_id', $hostel->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $paymentsByBatch = $payments->groupBy('batch_id');

        $last = $payments->first();
        $lastPayment = $last ? [
            'amount' => (float) $last->amount,
            'date' => $last->date?->format('Y-m-d'),
        ] : null;

        $allocator = app(FundsAllocatorService::class);
        $batches = $allocator->batchesWithNetAvailable();
        $totalBalance = $allocator->totalNetBalance();

        $today = Carbon::today();
        $lastDate = $last?->date ? Carbon::parse($last->date)->startOfDay() : null;

        $nextDue = null;
        $daysToDue = null;
        $dueBadge = 'No payments yet';
        $dueStatus = 'unknown';

        if ($lastDate) {
            if (($hostel->stake ?: 'monthly') === 'semester') {
                $nextDue = $lastDate->copy()->addMonthsNoOverflow(4)->startOfDay();
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

        return view('pettycash::spendings.tokens.hostel_show', compact(
            'hostel',
            'paymentsByBatch',
            'lastPayment',
            'batches',
            'totalBalance',
            'nextDue',
            'daysToDue',
            'dueBadge',
            'dueStatus',
            'today'
        ));
    }

    public function storePayment(Hostel $hostel, Request $request)
    {
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

            // allow optional override; default comes from hostel below
            'meter_no' => ['nullable', 'string', 'max:64'],
        ]);

        if ($data['funding'] === 'single' && empty($data['batch_id'])) {
            return back()->withErrors(['batch_id' => 'Batch is required in Single Batch mode.'])->withInput();
        }

        $fee = (float) ($data['transaction_cost'] ?? 0);
        $amount = (float) $data['amount'];

        // meter number captured into spending record for PDF/ledger
        $meterNo = trim((string) (($data['meter_no'] ?? null) ?: ($hostel->meter_no ?? '')));

        $allocator = app(FundsAllocatorService::class);

        if ($data['funding'] === 'auto') {
            $required = $amount + $fee;
            $available = (float) $allocator->totalNetBalance();
            if ($required > $available) {
                return back()->withErrors([
                    'amount' => 'Insufficient TOTAL balance. Needed: ' . number_format($required, 2) . ' Available: ' . number_format($available, 2),
                ])->withInput();
            }
        }

        try {
            DB::transaction(function () use ($hostel, $data, $fee, $amount, $allocator, $meterNo, $supportsSpendingLink) {

                // Create spending first (affects balances)
                $spending = Spending::create([
                    'batch_id' => null,
                    'type' => 'token',
                    'sub_type' => 'hostel',
                    'reference' => $data['reference'] ?? null,

                    // store meter number snapshot on the spending row
                    'meter_no' => ($meterNo !== '' ? $meterNo : null),

                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'date' => $data['date'],
                    'description' => 'Token payment: ' . $hostel->hostel_name,
                    'related_id' => $hostel->id,
                ]);

                $onlyBatch = ($data['funding'] === 'single') ? (int) $data['batch_id'] : null;
                $allocator->allocateSmallestFirst($spending, $amount, $fee, $onlyBatch);

                // primary batch for display/payment row
                $primaryBatchId = $spending->batch_id;

                // Payment ledger (one row, primary batch)
                $paymentPayload = [
                    'hostel_id' => $hostel->id,
                    'batch_id' => $primaryBatchId,
                    'reference' => $data['reference'] ?? null,
                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'date' => $data['date'],
                    'receiver_name' => $data['receiver_name'] ?? null,
                    'receiver_phone' => $data['receiver_phone'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'recorded_by' => auth('petty')->id(),
                ];
                if ($supportsSpendingLink) {
                    $paymentPayload['spending_id'] = $spending->id;
                }

                Payment::create($paymentPayload);
            });

        } catch (\Throwable $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return redirect()->route('petty.tokens.hostels.show', $hostel->id)
            ->with('success', 'Payment recorded with auto allocation.');
    }

    private function paymentSupportsSpendingLink(): bool
    {
        static $supports = null;

        if ($supports === null) {
            $supports = Schema::hasColumn('petty_payments', 'spending_id');
        }

        return $supports;
    }

    public function pdfHostels()
    {
        $format = strtolower((string) request()->query('format', 'pdf'));
        $semesterMonths = 4;
        $q = trim((string) request()->query('q', ''));

        $lastPaySub = Payment::query()
            ->selectRaw('hostel_id, MAX(date) as last_payment_date')
            ->groupBy('hostel_id');

        $hostels = Hostel::query()
            ->leftJoinSub($lastPaySub, 'lp', function ($join) {
                $join->on('lp.hostel_id', '=', 'petty_hostels.id');
            })
            ->select('petty_hostels.*', 'lp.last_payment_date')
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('hostel_name', 'like', "%{$q}%")
                        ->orWhere('meter_no', 'like', "%{$q}%")
                        ->orWhere('phone_no', 'like', "%{$q}%");
                });
            })
            ->orderBy('hostel_name')
            ->get()
            ->map(function ($h) {
                $h->last_payment_date = $h->last_payment_date ? Carbon::parse($h->last_payment_date)->format('Y-m-d') : null;
                return $h;
            });

        if (in_array($format, ['csv', 'excel', 'xls', 'xlsx'], true)) {
            $rows = $hostels->map(function ($h) use ($semesterMonths) {
                $lastDate = $h->last_payment_date ? Carbon::parse($h->last_payment_date)->startOfDay() : null;
                $dueDate = null;

                if ($lastDate) {
                    $dueDate = ($h->stake === 'semester')
                        ? $lastDate->copy()->addMonthsNoOverflow($semesterMonths)->format('Y-m-d')
                        : $lastDate->copy()->addMonthNoOverflow()->format('Y-m-d');
                }

                return [
                    'hostel_name' => $h->hostel_name,
                    'meter_no' => $h->meter_no ?? '',
                    'phone_no' => $h->phone_no ?? '',
                    'routers' => (int) ($h->no_of_routers ?? 0),
                    'stake' => strtoupper((string) ($h->stake ?? '')),
                    'amount_due' => number_format((float) $h->amount_due, 2, '.', ''),
                    'last_payment_date' => $h->last_payment_date ?? '',
                    'next_due_date' => $dueDate ?? '',
                ];
            })->all();

            return TabularExport::download(
                $format,
                'pettycash-token-hostels-' . now()->format('Ymd-His'),
                [
                    'Hostel' => 'hostel_name',
                    'Meter No' => 'meter_no',
                    'Phone' => 'phone_no',
                    'Routers' => 'routers',
                    'Stake' => 'stake',
                    'Amount Due' => 'amount_due',
                    'Last Payment Date' => 'last_payment_date',
                    'Next Due Date' => 'next_due_date',
                ],
                $rows
            );
        }

        $pdf = Pdf::loadView('pettycash::reports.tokens_hostels_pdf', [
            'hostels' => $hostels,
            'generatedAt' => now()->format('Y-m-d H:i'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('pettycash-token-hostels.pdf');
    }

    public function pdfHostelPayments(Hostel $hostel)
    {
        $format = strtolower((string) request()->query('format', 'pdf'));

        $payments = Payment::where('hostel_id', $hostel->id)
            ->with('batch')
            ->orderByDesc('date')
            ->get();

        $total = (float) $payments->sum(fn ($p) => (float) $p->amount + (float) ($p->transaction_cost ?? 0));

        if (in_array($format, ['csv', 'excel', 'xls', 'xlsx'], true)) {
            $rows = $payments->map(function ($p) {
                $amount = (float) $p->amount;
                $fee = (float) ($p->transaction_cost ?? 0);

                return [
                    'date' => $p->date?->format('Y-m-d'),
                    'reference' => $p->reference ?? '',
                    'amount' => number_format($amount, 2, '.', ''),
                    'transaction_cost' => number_format($fee, 2, '.', ''),
                    'total' => number_format($amount + $fee, 2, '.', ''),
                    'receiver_name' => $p->receiver_name ?? '',
                    'receiver_phone' => $p->receiver_phone ?? '',
                    'batch_no' => $p->batch?->batch_no ?? '',
                    'notes' => $p->notes ?? '',
                ];
            })->all();

            return TabularExport::download(
                $format,
                'pettycash-token-hostel-' . $hostel->id . '-payments-' . now()->format('Ymd-His'),
                [
                    'Date' => 'date',
                    'MPESA Ref' => 'reference',
                    'Amount' => 'amount',
                    'Fee' => 'transaction_cost',
                    'Total' => 'total',
                    'Receiver Name' => 'receiver_name',
                    'Receiver Phone' => 'receiver_phone',
                    'Batch' => 'batch_no',
                    'Notes' => 'notes',
                ],
                $rows
            );
        }

        $pdf = Pdf::loadView('pettycash::reports.tokens_hostel_payments_pdf', [
            'hostel' => $hostel,
            'payments' => $payments,
            'total' => $total,
            'generatedAt' => now()->format('Y-m-d H:i'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('pettycash-token-' . $hostel->id . '-payments.pdf');
    }
}
