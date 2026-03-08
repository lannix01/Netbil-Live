<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\MegaPayment;
use App\Services\InvoiceBillingService;
use App\Services\InvoiceNotificationService;
use App\Services\MegaPayClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RevenueController extends Controller
{
    private static array $columnCache = [];
    private const INVOICE_DELETE_PASSWORD = 'deleteAdmin';

    public function __construct(
        private readonly InvoiceBillingService $invoiceBilling,
        private readonly InvoiceNotificationService $invoiceNotifier
    ) {
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $status = $request->get('status');
        $purpose = $request->get('purpose');
        $channel = $request->get('channel');
        $from = $request->get('from');
        $to = $request->get('to');

        $rows = MegaPayment::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('reference', 'like', "%{$q}%")
                        ->orWhere('msisdn', 'like', "%{$q}%")
                        ->orWhere('mpesa_receipt', 'like', "%{$q}%")
                        ->orWhere('transaction_request_id', 'like', "%{$q}%")
                        ->orWhere('transaction_id', 'like', "%{$q}%");
                });
            })
            ->when($status, fn($query) => $query->where('status', $status))
            ->when($purpose, fn($query) => $query->where('purpose', $purpose))
            ->when($channel, fn($query) => $query->where('channel', $channel))
            ->when($from, fn($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn($query) => $query->whereDate('created_at', '<=', $to))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $totals = MegaPayment::query()
            ->where('status', 'completed')
            ->when($from, fn($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn($query) => $query->whereDate('created_at', '<=', $to))
            ->selectRaw('COUNT(*) as count_all, COALESCE(SUM(amount),0) as sum_amount')
            ->first();

        $byPurpose = MegaPayment::query()
            ->where('status', 'completed')
            ->when($from, fn($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn($query) => $query->whereDate('created_at', '<=', $to))
            ->selectRaw('purpose, COUNT(*) as cnt, COALESCE(SUM(amount),0) as total')
            ->groupBy('purpose')
            ->orderByDesc('total')
            ->get();

        $hotspotRows = $this->recentHotspotPayments($from, $to);

        $this->syncInvoiceStatuses();

        $invoiceQ = trim((string)$request->get('invoice_q', ''));
        $invoiceStatus = trim((string)$request->get('invoice_status', ''));

        $invoicesQuery = Invoice::query()->with('customer')
            ->when($invoiceQ !== '', function ($query) use ($invoiceQ) {
                $query->where(function ($qq) use ($invoiceQ) {
                    $qq->where('invoice_number', 'like', "%{$invoiceQ}%")
                        ->orWhereHas('customer', function ($cq) use ($invoiceQ) {
                            $cq->where('name', 'like', "%{$invoiceQ}%")
                                ->orWhere('phone', 'like', "%{$invoiceQ}%")
                                ->orWhere('email', 'like', "%{$invoiceQ}%")
                                ->orWhere('username', 'like', "%{$invoiceQ}%");
                        });
                });
            })
            ->when($invoiceStatus !== '', function ($query) use ($invoiceStatus) {
                $query->where(function ($qq) use ($invoiceStatus) {
                    if ($this->hasColumn('invoices', 'invoice_status')) {
                        $qq->where('invoice_status', $invoiceStatus);
                    }
                    if ($this->hasColumn('invoices', 'status')) {
                        if ($this->hasColumn('invoices', 'invoice_status')) {
                            $qq->orWhere('status', $invoiceStatus);
                        } else {
                            $qq->where('status', $invoiceStatus);
                        }
                    }
                });
            })
            ->orderByDesc('id');

        if ($this->hasColumn('payments', 'invoice_id')) {
            $invoicesQuery->with('payments');
        }

        $invoices = $invoicesQuery
            ->paginate(20, ['*'], 'invoice_page')
            ->through(function (Invoice $invoice) {
                try {
                    return $this->invoiceBilling->recalculate($invoice);
                } catch (\Throwable $e) {
                    Log::warning('Invoice recalc skipped in revenue index', [
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage(),
                    ]);
                    return $invoice;
                }
            })
            ->withQueryString();

        $sumAmountCol = $this->hasColumn('invoices', 'total_amount') ? 'total_amount' : 'amount';
        $sumPaidCol = $this->hasColumn('invoices', 'paid_amount') ? 'paid_amount' : null;
        $sumBalanceCol = $this->hasColumn('invoices', 'balance_amount') ? 'balance_amount' : null;

        $invoiceTotals = [
            'count_all' => (int)Invoice::query()->count(),
            'total_billed' => (float)Invoice::query()->sum($sumAmountCol),
            'total_paid' => $sumPaidCol
                ? (float)Invoice::query()->sum($sumPaidCol)
                : 0,
            'total_balance' => $sumBalanceCol
                ? (float)Invoice::query()->sum($sumBalanceCol)
                : 0,
            'overdue_count' => $this->hasColumn('invoices', 'invoice_status')
                ? (int)Invoice::query()->where('invoice_status', 'overdue')->count()
                : 0,
        ];

        return view('revenue.index', [
            'rows' => $rows,
            'totals' => $totals,
            'byPurpose' => $byPurpose,
            'hotspotRows' => $hotspotRows,
            'filters' => compact('q', 'status', 'purpose', 'channel', 'from', 'to', 'invoiceQ', 'invoiceStatus'),
            'invoices' => $invoices,
            'invoiceTotals' => $invoiceTotals,
        ]);
    }

    public function pollInvoices(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1|max:200',
            'ids.*' => 'integer|exists:invoices,id',
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['ids'])));

        $query = Invoice::query()
            ->with('customer')
            ->whereIn('id', $ids);

        if ($this->hasColumn('payments', 'invoice_id')) {
            $query->with('payments');
        }

        $rows = $query->get()
            ->map(function (Invoice $invoice) {
                $invoice = $this->invoiceBilling->recalculate($invoice);
                $status = $invoice->invoice_status ?: $invoice->status ?: 'unpaid';
                $balance = $this->invoiceOutstanding($invoice);
                $total = $this->hasColumn('invoices', 'total_amount')
                    ? (float)($invoice->total_amount ?? 0)
                    : (float)($invoice->amount ?? 0);
                $paid = $this->hasColumn('invoices', 'paid_amount')
                    ? (float)($invoice->paid_amount ?? 0)
                    : round(max(0, $total - $balance), 2);

                return [
                    'id' => (int)$invoice->id,
                    'invoice_number' => (string)($invoice->invoice_number ?? ''),
                    'status' => (string)$status,
                    'total_amount' => round($total, 2),
                    'paid_amount' => round($paid, 2),
                    'balance_amount' => round($balance, 2),
                    'currency' => (string)($invoice->currency ?: 'KES'),
                    'issued_at' => optional($invoice->issued_at ?: $invoice->created_at)?->format('Y-m-d'),
                    'due_date' => optional($invoice->due_date)?->format('Y-m-d'),
                    'customer' => [
                        'id' => (int)($invoice->customer?->id ?? 0),
                        'name' => (string)($invoice->customer?->name ?? ''),
                        'username' => (string)($invoice->customer?->username ?? ''),
                        'phone' => (string)($invoice->customer?->phone ?? ''),
                    ],
                ];
            })
            ->keyBy('id');

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($rows[$id])) {
                $ordered[] = $rows[$id];
            }
        }

        $sumAmountCol = $this->hasColumn('invoices', 'total_amount') ? 'total_amount' : 'amount';
        $sumPaidCol = $this->hasColumn('invoices', 'paid_amount') ? 'paid_amount' : null;
        $sumBalanceCol = $this->hasColumn('invoices', 'balance_amount') ? 'balance_amount' : null;

        return response()->json([
            'ok' => true,
            'rows' => $ordered,
            'totals' => [
                'count_all' => (int)Invoice::query()->count(),
                'total_billed' => round((float)Invoice::query()->sum($sumAmountCol), 2),
                'total_paid' => $sumPaidCol ? round((float)Invoice::query()->sum($sumPaidCol), 2) : 0,
                'total_balance' => $sumBalanceCol ? round((float)Invoice::query()->sum($sumBalanceCol), 2) : 0,
                'overdue_count' => $this->hasColumn('invoices', 'invoice_status')
                    ? (int)Invoice::query()->where('invoice_status', 'overdue')->count()
                    : 0,
            ],
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    public function showInvoice(Invoice $invoice): JsonResponse
    {
        $invoice->load('customer');
        if ($this->hasColumn('payments', 'invoice_id')) {
            $invoice->load('payments');
        }
        $invoice = $this->invoiceBilling->recalculate($invoice);

        return response()->json([
            'invoice' => $invoice,
            'customer' => $invoice->customer,
            'payments' => $this->hasColumn('payments', 'invoice_id') ? ($invoice->payments ?? []) : [],
            'public_url' => $this->invoiceBilling->publicUrl($invoice),
        ]);
    }

    public function printInvoice(Invoice $invoice)
    {
        $invoice->load('customer');
        if ($this->hasColumn('payments', 'invoice_id')) {
            $invoice->load('payments');
        }
        $invoice = $this->invoiceBilling->recalculate($invoice);

        return view('revenue.invoice-print', [
            'invoice' => $invoice,
            'customer' => $invoice->customer,
            'payments' => $this->hasColumn('payments', 'invoice_id') ? ($invoice->payments ?? collect()) : collect(),
            'publicUrl' => $this->invoiceBilling->publicUrl($invoice),
        ]);
    }

    public function updateInvoiceStatus(Request $request, Invoice $invoice): JsonResponse
    {
        $data = $request->validate([
            'invoice_status' => 'required|string|in:unpaid,due,overdue,partial,paid,cancelled',
        ]);

        $status = $data['invoice_status'];

        if ($status === 'paid' && $this->hasColumn('invoices', 'paid_amount')) {
            $invoice->paid_amount = (float)($invoice->total_amount ?: $invoice->amount ?: 0);
        }

        if (in_array($status, ['unpaid', 'due', 'overdue'], true) && $this->hasColumn('invoices', 'paid_amount')) {
            $invoice->paid_amount = 0;
        }

        if ($status === 'due' && $this->hasColumn('invoices', 'due_date') && !$invoice->due_date) {
            $invoice->due_date = now()->addDays(1)->toDateString();
        }

        if ($status === 'overdue' && $this->hasColumn('invoices', 'due_date') && !$invoice->due_date) {
            $invoice->due_date = now()->subDay()->toDateString();
        }

        if ($this->hasColumn('invoices', 'invoice_status')) {
            $invoice->invoice_status = $status;
        }
        if ($this->hasColumn('invoices', 'status')) {
            $invoice->status = $status === 'paid' ? 'paid' : 'unpaid';
        }

        $invoice = $this->invoiceBilling->recalculate($invoice);

        if ($status === 'cancelled' && $this->hasColumn('invoices', 'invoice_status')) {
            $invoice->invoice_status = 'cancelled';
            if ($this->hasColumn('invoices', 'status')) {
                $invoice->status = 'unpaid';
            }
            $invoice->save();
        }

        return response()->json([
            'ok' => true,
            'message' => 'Invoice status updated.',
            'invoice' => $invoice,
        ]);
    }

    public function sendInvoiceReminder(Invoice $invoice): JsonResponse
    {
        $invoice->load('customer');
        $invoice = $this->invoiceBilling->recalculate($invoice);

        $res = $this->invoiceNotifier->sendReminder($invoice);

        if (!($res['success'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => $res['message'] ?? 'Reminder failed.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Reminder sent.',
            'result' => $res,
        ]);
    }

    public function deleteInvoice(Request $request, Invoice $invoice): JsonResponse
    {
        $data = $request->validate([
            'delete_password' => 'required|string|max:64',
        ]);

        if (!hash_equals(self::INVOICE_DELETE_PASSWORD, (string)$data['delete_password'])) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid authorization password. Invoice was not deleted.',
            ], 422);
        }

        $invoiceNumber = (string)($invoice->invoice_number ?: ('#' . $invoice->id));

        DB::beginTransaction();
        try {
            if ($this->hasColumn('payments', 'invoice_id')) {
                DB::table('payments')
                    ->where('invoice_id', $invoice->id)
                    ->update(['invoice_id' => null]);
            }

            $invoice->delete();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Invoice delete failed', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Unable to delete invoice at the moment.',
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'message' => "Invoice {$invoiceNumber} deleted.",
            'invoice_id' => $invoice->id,
        ]);
    }

    public function requestInvoicePayment(Request $request, MegaPayClient $client): JsonResponse
    {
        $data = $request->validate([
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'integer|exists:invoices,id',
            'msisdn' => 'nullable|string|max:20',
            'amount' => 'nullable|numeric|min:1',
        ]);

        $invoices = Invoice::query()
            ->with('customer')
            ->whereIn('id', $data['invoice_ids'])
            ->orderBy('id')
            ->get();

        if ($invoices->isEmpty()) {
            return response()->json(['ok' => false, 'message' => 'No invoices selected.'], 422);
        }

        $customerIds = $invoices->pluck('customer_id')->unique()->filter();
        if ($customerIds->count() > 1) {
            return response()->json(['ok' => false, 'message' => 'Selected invoices must belong to one customer.'], 422);
        }

        $customer = $invoices->first()->customer;

        $msisdn = trim((string)($data['msisdn'] ?? ($customer->phone ?? '')));
        if ($msisdn === '') {
            return response()->json(['ok' => false, 'message' => 'Customer phone is required for STK.'], 422);
        }

        $totalBalance = round((float)$invoices->sum(function (Invoice $invoice) {
            return $this->invoiceOutstanding($invoice);
        }), 2);

        $amount = round((float)($data['amount'] ?? $totalBalance), 2);
        if ($amount <= 0) {
            return response()->json(['ok' => false, 'message' => 'Invoice amount must be above zero.'], 422);
        }

        $reference = $this->invoiceReference($invoices->pluck('id')->all());

        $mp = MegaPayment::create([
            'reference' => $reference,
            'purpose' => 'invoice',
            'channel' => 'invoice_request',
            'meta' => [
                'invoice_ids' => $invoices->pluck('id')->values()->all(),
                'customer_id' => $customer?->id,
                'source' => 'billing_dashboard',
            ],
            'payable_type' => Invoice::class,
            'payable_id' => $invoices->first()->id,
            'customer_id' => $customer?->id,
            'initiated_by' => auth()->id(),
            'msisdn' => MegaPayClient::normalizeMsisdn($msisdn),
            'amount' => (int)round($amount),
            'status' => 'pending',
            'initiated_at' => now(),
        ]);

        try {
            $resp = $client->initiateStk((int)round($amount), $mp->msisdn, $reference);

            $mp->transaction_request_id = $resp['transaction_request_id'] ?? null;
            $mp->merchant_request_id = $resp['MerchantRequestID'] ?? ($resp['merchant_request_id'] ?? null);
            $mp->checkout_request_id = $resp['CheckoutRequestID'] ?? ($resp['checkout_request_id'] ?? null);
            $mp->response_description = $resp['message'] ?? ($resp['massage'] ?? null);
            $mp->response_code = isset($resp['ResponseCode']) ? (int)$resp['ResponseCode'] : null;

            if (isset($resp['ResponseCode']) && (int)$resp['ResponseCode'] !== 0) {
                $mp->status = 'failed';
                $mp->failed_at = now();
            }

            $mp->save();

            return response()->json([
                'ok' => true,
                'message' => $mp->response_description ?: 'STK request sent.',
                'reference' => $reference,
                'transaction_request_id' => $mp->transaction_request_id,
                'amount' => $amount,
                'msisdn' => $mp->msisdn,
            ]);
        } catch (\Throwable $e) {
            Log::error('Invoice STK initiate failed', ['reference' => $reference, 'error' => $e->getMessage()]);

            $mp->status = 'failed';
            $mp->response_description = 'Initiate failed: ' . $e->getMessage();
            $mp->failed_at = now();
            $mp->save();

            return response()->json([
                'ok' => false,
                'message' => 'Failed to request payment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function publicInvoice(string $token)
    {
        [$invoice, $customer, $dueInvoices, $summary] = $this->resolvePublicInvoiceContext($token);

        return view('revenue.public-invoice', [
            'invoice' => $invoice,
            'customer' => $customer,
            'dueInvoices' => $dueInvoices,
            'publicToken' => $token,
            'summary' => $summary,
        ]);
    }

    public function publicInvoiceSnapshot(string $token): JsonResponse
    {
        [$invoice, $customer, $dueInvoices, $summary] = $this->resolvePublicInvoiceContext($token);

        $status = $invoice->invoice_status ?: $invoice->status ?: 'unpaid';

        return response()->json([
            'ok' => true,
            'invoice' => [
                'id' => (int)$invoice->id,
                'invoice_number' => (string)($invoice->invoice_number ?? ''),
                'status' => (string)$status,
                'currency' => (string)($invoice->currency ?: 'KES'),
                'total_amount' => round((float)($invoice->total_amount ?: $invoice->amount ?: 0), 2),
                'paid_amount' => round((float)($invoice->paid_amount ?? 0), 2),
                'balance_amount' => round((float)($invoice->balance_amount ?? 0), 2),
                'issued_at' => optional($invoice->issued_at ?: $invoice->created_at)?->format('Y-m-d'),
                'due_date' => optional($invoice->due_date)?->format('Y-m-d'),
            ],
            'customer' => [
                'id' => (int)($customer?->id ?? 0),
                'name' => (string)($customer?->name ?? ''),
                'username' => (string)($customer?->username ?? ''),
                'phone' => (string)($customer?->phone ?? ''),
            ],
            'due_invoices' => $dueInvoices->map(function (Invoice $row) {
                $status = $row->invoice_status ?: $row->status ?: 'unpaid';
                $balance = $this->invoiceOutstanding($row);
                return [
                    'id' => (int)$row->id,
                    'invoice_number' => (string)($row->invoice_number ?? ''),
                    'status' => (string)$status,
                    'currency' => (string)($row->currency ?: 'KES'),
                    'total_amount' => round((float)($row->total_amount ?: $row->amount ?: 0), 2),
                    'paid_amount' => round((float)($row->paid_amount ?? 0), 2),
                    'balance_amount' => round($balance, 2),
                    'due_date' => optional($row->due_date)?->format('Y-m-d'),
                    'public_url' => $this->invoiceBilling->publicUrl($row),
                ];
            })->values(),
            'summary' => $summary,
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    public function publicInvoiceRequestPayment(Request $request, string $token, MegaPayClient $client): JsonResponse
    {
        $baseInvoice = Invoice::query()
            ->when($this->hasColumn('invoices', 'public_token'), function ($query) use ($token) {
                $query->where('public_token', $token);
            }, function ($query) use ($token) {
                $query->where('invoice_number', urldecode($token));
            })
            ->firstOrFail();

        $data = $request->validate([
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'integer|exists:invoices,id',
            'msisdn' => 'required|string|max:20',
            'amount' => 'nullable|numeric|min:1',
        ]);

        $invoices = Invoice::query()
            ->where('customer_id', $baseInvoice->customer_id)
            ->whereIn('id', $data['invoice_ids'])
            ->when($this->hasColumn('invoices', 'invoice_status'), function ($query) {
                $query->whereNotIn('invoice_status', ['paid', 'cancelled']);
            }, function ($query) {
                $query->where('status', '!=', 'paid');
            })
            ->get();

        if ($invoices->isEmpty()) {
            return response()->json(['ok' => false, 'message' => 'No valid invoices selected.'], 422);
        }

        $totalBalance = round((float)$invoices->sum(function (Invoice $invoice) {
            return $this->invoiceOutstanding($invoice);
        }), 2);

        $amount = round((float)($data['amount'] ?? $totalBalance), 2);
        if ($amount <= 0) {
            return response()->json(['ok' => false, 'message' => 'Amount must be above zero.'], 422);
        }

        $reference = $this->invoiceReference($invoices->pluck('id')->all());

        $mp = MegaPayment::create([
            'reference' => $reference,
            'purpose' => 'invoice',
            'channel' => 'invoice_link_public',
            'meta' => [
                'invoice_ids' => $invoices->pluck('id')->values()->all(),
                'customer_id' => $baseInvoice->customer_id,
                'source' => 'public_payment_link',
            ],
            'payable_type' => Invoice::class,
            'payable_id' => $invoices->first()->id,
            'customer_id' => $baseInvoice->customer_id,
            'initiated_by' => null,
            'msisdn' => MegaPayClient::normalizeMsisdn($data['msisdn']),
            'amount' => (int)round($amount),
            'status' => 'pending',
            'initiated_at' => now(),
        ]);

        try {
            $resp = $client->initiateStk((int)round($amount), $mp->msisdn, $reference);

            $mp->transaction_request_id = $resp['transaction_request_id'] ?? null;
            $mp->merchant_request_id = $resp['MerchantRequestID'] ?? ($resp['merchant_request_id'] ?? null);
            $mp->checkout_request_id = $resp['CheckoutRequestID'] ?? ($resp['checkout_request_id'] ?? null);
            $mp->response_description = $resp['message'] ?? ($resp['massage'] ?? null);
            $mp->response_code = isset($resp['ResponseCode']) ? (int)$resp['ResponseCode'] : null;

            if (isset($resp['ResponseCode']) && (int)$resp['ResponseCode'] !== 0) {
                $mp->status = 'failed';
                $mp->failed_at = now();
            }

            $mp->save();

            return response()->json([
                'ok' => true,
                'message' => $mp->response_description ?: 'STK request sent.',
                'reference' => $reference,
                'amount' => $amount,
                'msisdn' => $mp->msisdn,
            ]);
        } catch (\Throwable $e) {
            Log::error('Public invoice STK initiate failed', ['reference' => $reference, 'error' => $e->getMessage()]);

            $mp->status = 'failed';
            $mp->response_description = 'Initiate failed: ' . $e->getMessage();
            $mp->failed_at = now();
            $mp->save();

            return response()->json([
                'ok' => false,
                'message' => 'Failed to request payment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function recentHotspotPayments(?string $from = null, ?string $to = null): Collection
    {
        $hasPurpose = $this->hasColumn('megapayments', 'purpose');
        $hasChannel = $this->hasColumn('megapayments', 'channel');
        if (!$hasPurpose && !$hasChannel) {
            return collect();
        }

        $query = MegaPayment::query()
            ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('created_at', '<=', $to))
            ->where(function ($q) use ($hasPurpose, $hasChannel) {
                if ($hasPurpose) {
                    $q->whereIn('purpose', ['hotspot_access', 'hotspot_package']);
                }
                if ($hasChannel) {
                    if ($hasPurpose) {
                        $q->orWhereIn('channel', ['portal_connect', 'hotspot']);
                    } else {
                        $q->whereIn('channel', ['portal_connect', 'hotspot']);
                    }
                }
            })
            ->orderByDesc('id')
            ->limit(80);

        if ($this->hasColumn('megapayments', 'status')) {
            $query->where('status', 'completed');
        }

        $payments = $query->get();
        if ($payments->isEmpty()) {
            return collect();
        }

        $connectionIds = $payments->map(function (MegaPayment $payment) {
            $meta = is_array($payment->meta) ? $payment->meta : [];
            $payableType = ltrim((string)($payment->payable_type ?? ''), '\\');
            if ($payableType === ltrim(Connection::class, '\\') && (int)($payment->payable_id ?? 0) > 0) {
                return (int)$payment->payable_id;
            }
            return (int)($meta['connection_id'] ?? 0);
        })->filter(fn($id) => (int)$id > 0)->unique()->values();

        $connectionMap = collect();
        if ($connectionIds->isNotEmpty() && $this->hasColumn('connections', 'username')) {
            $connectionMap = Connection::query()
                ->whereIn('id', $connectionIds->all())
                ->get(['id', 'username'])
                ->keyBy('id');
        }

        $customerMap = collect();
        if ($this->hasColumn('customers', 'username')) {
            $customerIds = $payments->pluck('customer_id')
                ->map(fn($id) => (int)$id)
                ->filter(fn($id) => $id > 0)
                ->unique()
                ->values();

            if ($customerIds->isNotEmpty()) {
                $customerSelect = ['id', 'username'];
                if ($this->hasColumn('customers', 'phone')) {
                    $customerSelect[] = 'phone';
                }

                $customerMap = Customer::query()
                    ->whereIn('id', $customerIds->all())
                    ->get($customerSelect)
                    ->keyBy('id');
            }
        }

        $canResolveByPhone = $this->hasColumn('customers', 'phone') && $this->hasColumn('customers', 'username');
        $usernameByPhoneKey = [];

        return $payments->map(function (MegaPayment $payment) use ($connectionMap, $customerMap, $canResolveByPhone, &$usernameByPhoneKey) {
            $meta = is_array($payment->meta) ? $payment->meta : [];
            $customerId = (int)($payment->customer_id ?? 0);

            $username = '';
            if ($customerId > 0 && $customerMap->has($customerId)) {
                $username = trim((string)($customerMap->get($customerId)->username ?? ''));
            }

            if ($username === '') {
                $connectionId = 0;
                $payableType = ltrim((string)($payment->payable_type ?? ''), '\\');
                if ($payableType === ltrim(Connection::class, '\\') && (int)($payment->payable_id ?? 0) > 0) {
                    $connectionId = (int)$payment->payable_id;
                } else {
                    $connectionId = (int)($meta['connection_id'] ?? 0);
                }

                if ($connectionId > 0 && $connectionMap->has($connectionId)) {
                    $username = trim((string)($connectionMap->get($connectionId)->username ?? ''));
                }
            }

            if ($username === '') {
                $username = trim((string)($meta['username'] ?? $meta['user'] ?? ''));
            }

            if ($username === '' && $canResolveByPhone) {
                $phoneKey = $this->phoneLookupKey((string)($payment->msisdn ?? ''));
                if ($phoneKey !== '') {
                    if (!array_key_exists($phoneKey, $usernameByPhoneKey)) {
                        $usernameByPhoneKey[$phoneKey] = (string)(Customer::query()
                            ->whereNotNull('phone')
                            ->where('phone', 'like', '%' . $phoneKey)
                            ->orderByDesc('id')
                            ->value('username') ?? '');
                    }
                    $username = trim((string)$usernameByPhoneKey[$phoneKey]);
                }
            }

            $packageName = trim((string)($meta['package_name'] ?? $meta['plan'] ?? $meta['package'] ?? 'Hotspot Package'));
            if ($packageName === '') {
                $packageName = 'Hotspot Package';
            }

            $phone = trim((string)($payment->msisdn ?? ''));

            return (object)[
                'id' => (int)$payment->id,
                'reference' => (string)($payment->reference ?? ''),
                'msisdn' => $phone !== '' ? $phone : '—',
                'amount' => (float)($payment->amount ?? 0),
                'currency' => strtoupper(trim((string)($meta['currency'] ?? 'KES'))) ?: 'KES',
                'status' => strtolower((string)($payment->status ?? 'pending')),
                'package_name' => $packageName,
                'receipt' => (string)($payment->mpesa_receipt ?? ''),
                'attempted_at' => $payment->created_at,
                'completed_at' => $payment->completed_at,
                'customer_username' => $username,
                'customer_url' => $username !== ''
                    ? route('customers.index', ['open_user' => $username, 'focus' => 'package'])
                    : null,
            ];
        })->values();
    }

    private function syncInvoiceStatuses(): void
    {
        $query = Invoice::query()->orderBy('id');
        if ($this->hasColumn('invoices', 'invoice_status')) {
            $query->whereNotIn('invoice_status', ['paid', 'cancelled']);
        } elseif ($this->hasColumn('invoices', 'status')) {
            $query->where('status', '!=', 'paid');
        }

        $query->chunkById(100, function ($invoices) {
            foreach ($invoices as $invoice) {
                try {
                    $this->invoiceBilling->recalculate($invoice);
                } catch (\Throwable $e) {
                    Log::warning('Invoice status sync skipped', [
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });
    }

    private function invoiceReference(array $invoiceIds): string
    {
        $seed = implode('-', $invoiceIds);
        return 'INVREQ_' . now()->format('YmdHis') . '_' . strtoupper(Str::substr(md5($seed . microtime()), 0, 6));
    }

    private function resolvePublicInvoiceContext(string $token): array
    {
        $query = Invoice::query()->with('customer');
        if ($this->hasColumn('payments', 'invoice_id')) {
            $query->with('payments');
        }

        if ($this->hasColumn('invoices', 'public_token')) {
            $query->where('public_token', $token);
        } else {
            $query->where('invoice_number', urldecode($token));
        }

        $invoice = $query->firstOrFail();
        $invoice = $this->invoiceBilling->recalculate($invoice);

        $customer = $invoice->customer;
        $dueInvoicesQuery = Invoice::query()
            ->where('customer_id', $customer?->id);

        if ($this->hasColumn('invoices', 'invoice_status')) {
            $dueInvoicesQuery->whereNotIn('invoice_status', ['paid', 'cancelled']);
        } else {
            $dueInvoicesQuery->where('status', '!=', 'paid');
        }

        if ($this->hasColumn('invoices', 'due_date')) {
            $dueInvoicesQuery->orderBy('due_date');
        } else {
            $dueInvoicesQuery->orderBy('id');
        }

        if ($this->hasColumn('payments', 'invoice_id')) {
            $dueInvoicesQuery->with('payments');
        }

        $dueInvoices = $dueInvoicesQuery->get()->map(function (Invoice $row) {
            return $this->invoiceBilling->recalculate($row);
        });

        $openTotal = round((float)$dueInvoices->sum(function (Invoice $row) {
            return $this->invoiceOutstanding($row);
        }), 2);

        $summary = [
            'open_count' => (int)$dueInvoices->count(),
            'open_total' => $openTotal,
            'updated_at' => now()->toDateTimeString(),
        ];

        return [$invoice, $customer, $dueInvoices, $summary];
    }

    private function invoiceOutstanding(Invoice $invoice): float
    {
        if ($this->hasColumn('invoices', 'balance_amount')) {
            return round((float)($invoice->balance_amount ?? 0), 2);
        }

        $base = $this->hasColumn('invoices', 'total_amount')
            ? (float)($invoice->total_amount ?? 0)
            : (float)($invoice->amount ?? 0);

        if ($this->hasColumn('invoices', 'paid_amount')) {
            return round(max(0, $base - (float)($invoice->paid_amount ?? 0)), 2);
        }

        return round(max(0, $base), 2);
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

    private function phoneLookupKey(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value);
        if ($digits === null || $digits === '') {
            return '';
        }

        if (strlen($digits) <= 9) {
            return $digits;
        }

        return substr($digits, -9);
    }
}
