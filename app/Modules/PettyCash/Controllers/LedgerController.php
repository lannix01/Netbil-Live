<?php

namespace App\Modules\PettyCash\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Modules\PettyCash\Support\TabularExport;
use App\Modules\PettyCash\Support\UnifiedLedger;
use Barryvdh\DomPDF\Facade\Pdf;

class LedgerController extends Controller
{
    public function pdf(Request $request)
    {
        $request->merge(['export' => 'pdf']);
        return $this->spendings($request);
    }

    public function index(Request $request)
    {
        return $this->spendings($request);
    }

    public function spendings(Request $request)
    {
        $from    = $request->query('from');
        $to      = $request->query('to');
        $batchId = $request->query('batch_id');
        $type    = $request->query('type');
        $q       = trim((string) $request->query('q', ''));
        $calc    = (int) $request->query('calc', 0) === 1;
        $exportRaw = $request->query('export');

        $exportFormat = null;
        if ($exportRaw !== null && $exportRaw !== '') {
            $raw = strtolower((string) $exportRaw);
            $exportFormat = in_array($raw, ['1', 'true', 'yes'], true) ? 'pdf' : $raw;
        }
        $export = in_array($exportFormat, ['pdf', 'csv', 'excel', 'xls', 'xlsx'], true);

        // batches list for dropdown (adjust if your table differs)
        $batches = DB::table('petty_batches')
            ->select('id', 'batch_no')
            ->orderByDesc('id')
            ->get();

        $query = UnifiedLedger::query();

        if (!empty($from)) $query->whereDate('date', '>=', $from);
        if (!empty($to))   $query->whereDate('date', '<=', $to);

        // Batch only applies to spendings rows (services have batch_id null)
        if (!empty($batchId)) {
            $query->where(function ($x) use ($batchId) {
                $x->where('source', 'spending')
                  ->where('batch_id', $batchId);
            });
        }

        if (!empty($type)) {
            $query->where('type', $type);
        }

        if ($q !== '') {
            $query->where(function ($x) use ($q) {
                $x->where('reference', 'like', "%{$q}%")
                  ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $spendings = $query
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // AJAX response for live search (not for export)
        if ($request->ajax() && !$export) {
            $html = view('pettycash::ledger._table', ['spendings' => $spendings])->render();
            return response()->json([
                'html'  => $html,
                'count' => $spendings->count(),
            ]);
        }

        // Totals for PDF (optional)
        $sumByTypeAmount = [];
        $sumByTypeFee = [];
        $sumByTypeTotal = [];
        $grandAmount = 0.0;
        $grandFee = 0.0;
        $grandTotal = 0.0;

        if ($calc) {
            foreach ($spendings as $s) {
                $fee = (float) data_get($s, 'transaction_cost', 0);
                $amt = (float) data_get($s, 'amount', 0);
                $tot = $amt + $fee;

                $t = strtolower(trim((string) data_get($s, 'type', 'other')));
                if ($t === '') $t = 'other';

                $sumByTypeAmount[$t] = ($sumByTypeAmount[$t] ?? 0) + $amt;
                $sumByTypeFee[$t]    = ($sumByTypeFee[$t] ?? 0) + $fee;
                $sumByTypeTotal[$t]  = ($sumByTypeTotal[$t] ?? 0) + $tot;

                $grandAmount += $amt;
                $grandFee    += $fee;
                $grandTotal  += $tot;
            }
        }

        if ($export) {
            if (in_array($exportFormat, ['csv', 'excel', 'xls', 'xlsx'], true)) {
                $rows = $spendings->map(function ($s) {
                    $amount = (float) data_get($s, 'amount', 0);
                    $fee = (float) data_get($s, 'transaction_cost', 0);
                    $date = data_get($s, 'date');

                    return [
                        'date' => $date ? date('Y-m-d', strtotime((string) $date)) : '',
                        'reference' => (string) data_get($s, 'reference', ''),
                        'type' => strtoupper((string) data_get($s, 'type', '')),
                        'sub_type' => strtoupper((string) data_get($s, 'sub_type', '')),
                        'description' => (string) data_get($s, 'description', ''),
                        'batch_no' => (string) (data_get($s, 'batch_no') ?? ''),
                        'plate_no' => (string) (data_get($s, 'plate_no') ?? ''),
                        'meter_no' => (string) (data_get($s, 'meter_no') ?? ''),
                        'amount' => number_format($amount, 2, '.', ''),
                        'transaction_cost' => number_format($fee, 2, '.', ''),
                        'total' => number_format($amount + $fee, 2, '.', ''),
                        'source' => strtoupper((string) data_get($s, 'source', '')),
                    ];
                })->all();

                return TabularExport::download(
                    $exportFormat,
                    'pettycash-spendings-ledger-' . now()->format('Ymd-His'),
                    [
                        'Date' => 'date',
                        'Reference' => 'reference',
                        'Category' => 'type',
                        'Sub Type' => 'sub_type',
                        'Description' => 'description',
                        'Batch' => 'batch_no',
                        'Plate' => 'plate_no',
                        'Meter' => 'meter_no',
                        'Amount' => 'amount',
                        'Fee' => 'transaction_cost',
                        'Total' => 'total',
                        'Source' => 'source',
                    ],
                    $rows
                );
            }

            $payload = [
                'spendings' => $spendings,
                'from' => $from,
                'to' => $to,
                'batchId' => $batchId,
                'type' => $type,
                'q' => $q,
                'calc' => $calc,

                // Pass totals (your PDF blade can use these)
                'sumByTypeAmount' => $sumByTypeAmount,
                'sumByTypeFee' => $sumByTypeFee,
                'sumByTypeTotal' => $sumByTypeTotal,
                'grandAmount' => $grandAmount,
                'grandFee' => $grandFee,
                'grandTotal' => $grandTotal,
            ];

            $name = 'pettycash-spendings-' . now()->format('Ymd-His') . '.pdf';

            // ✅ LANDSCAPE + DOWNLOAD
            return Pdf::loadView('pettycash::ledger.spendings_pdf', $payload)
                ->setPaper('a4', 'landscape')
                ->download($name);
        }

        return view('pettycash::ledger.spendings', compact(
            'spendings', 'from', 'to', 'batches', 'batchId', 'type', 'q', 'calc'
        ));
    }
}
