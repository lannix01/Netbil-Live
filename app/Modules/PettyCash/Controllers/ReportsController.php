<?php

namespace App\Modules\PettyCash\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\PettyCash\Models\Batch;
use App\Modules\PettyCash\Models\Bike;
use App\Modules\PettyCash\Models\Hostel;
use App\Modules\PettyCash\Models\Respondent;
use App\Modules\PettyCash\Services\ReportService;
use App\Modules\PettyCash\Services\ReportChartService;
use App\Modules\PettyCash\Support\TabularExport;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $batchId = $request->query('batch_id');

        $batches = Batch::orderByDesc('id')->limit(50)->get();
        $hostels = Hostel::orderBy('hostel_name')->get();

        return view('pettycash::reports.hub', compact('from','to','batchId','batches','hostels'));
    }

    public function generalForm()
    {
        return view('pettycash::reports.general_form', [
            'batches' => Batch::orderByDesc('id')->get(),
            'bikes' => Bike::orderBy('plate_no')->get(),
            'respondents' => Respondent::orderBy('name')->get(),
        ]);
    }

    public function generalPdf(Request $request, ReportService $reports, ReportChartService $charts)
    {
        $data = $request->validate([
            'batch_ids' => ['nullable', 'array'],
            'batch_ids.*' => ['integer', 'exists:petty_batches,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],

            'bike_id' => ['nullable', 'integer', 'exists:petty_bikes,id'],
            'respondent_id' => ['nullable', 'integer', 'exists:petty_respondents,id'],

            'include' => ['nullable', 'array'],
            'view' => ['nullable', 'in:combined,split'],
            'format' => ['nullable', 'in:pdf,csv,excel'],
        ]);

        $batchIds = $data['batch_ids'] ?? null;
        $include = $data['include'] ?? ['bike:fuel','bike:maintenance','meal:lunch','token:hostel','other'];
        $view = $data['view'] ?? 'combined';
        $format = $data['format'] ?? 'pdf';

        // IMPORTANT: ReportService should eager-load batch/respondent/bike/hostel
        // If not, the blade will still work but details may show "-" for names.
        $credits = $reports->credits($batchIds, $data['from'] ?? null, $data['to'] ?? null);
        $spendings = $reports->spendings($batchIds, $data['from'] ?? null, $data['to'] ?? null, $include);

        // Optional focus filters
        if (!empty($data['bike_id'])) {
            $bikeId = (int)$data['bike_id'];
            $spendings = $spendings->filter(fn($s) => $s->type === 'bike' && (int)$s->related_id === $bikeId)->values();
        }

        if (!empty($data['respondent_id'])) {
            $rid = (int)$data['respondent_id'];
            $spendings = $spendings->filter(fn($s) => (int)($s->respondent_id ?? 0) === $rid)->values();
        }

        // =========================
        // NET LOGIC (fees included)
        // =========================
        // Credits: net_in = amount - fee
        $creditedAmountTotal = (float) $credits->sum('amount');
        $creditedFeeTotal = (float) $credits->sum(fn($c) => (float)($c->transaction_cost ?? 0));
        $creditedNetTotal = $creditedAmountTotal - $creditedFeeTotal;

        // Debits: net_out = amount + fee
        $debitAmountTotal = (float) $spendings->sum('amount');
        $debitFeeTotal = (float) $spendings->sum(fn($s) => (float)($s->transaction_cost ?? 0));
        $debitNetTotal = $debitAmountTotal + $debitFeeTotal;

        $balance = $creditedNetTotal - $debitNetTotal;

        // Totals by bucket (NET)
        // We compute net totals here so you don't have to refactor ReportService right now.
        $totalsNet = [];
        foreach ($spendings as $s) {
            $bucket = $s->type . ($s->sub_type ? ':' . $s->sub_type : '');
            $totalsNet[$bucket] = ($totalsNet[$bucket] ?? 0) + ((float)$s->amount + (float)($s->transaction_cost ?? 0));
        }

        arsort($totalsNet);
        $chartB64 = $charts->barChartBase64($totalsNet);

        if (in_array($format, ['csv', 'excel'], true)) {
            $creditRows = $credits->map(function ($c) {
                $amount = (float) $c->amount;
                $fee = (float) ($c->transaction_cost ?? 0);

                return [
                    'section' => 'CREDIT',
                    'date' => $c->date?->format('Y-m-d'),
                    'category' => 'credit',
                    'sub_type' => '',
                    'reference' => $c->reference ?? '',
                    'description' => $c->description ?? '',
                    'amount' => number_format($amount, 2, '.', ''),
                    'transaction_cost' => number_format($fee, 2, '.', ''),
                    'net' => number_format($amount - $fee, 2, '.', ''),
                    'context' => $c->batch?->batch_no ?? '',
                ];
            });

            $spendingRows = $spendings->map(function ($s) {
                $amount = (float) $s->amount;
                $fee = (float) ($s->transaction_cost ?? 0);

                $context = '';
                if ($s->type === 'bike') {
                    $context = $s->bike?->plate_no ?? '';
                } elseif ($s->type === 'token') {
                    $context = $s->hostel?->hostel_name ?? '';
                } else {
                    $context = $s->respondent?->name ?? '';
                }

                return [
                    'section' => 'SPENDING',
                    'date' => $s->date?->format('Y-m-d'),
                    'category' => (string) $s->type,
                    'sub_type' => (string) ($s->sub_type ?? ''),
                    'reference' => $s->reference ?? '',
                    'description' => $s->description ?? '',
                    'amount' => number_format($amount, 2, '.', ''),
                    'transaction_cost' => number_format($fee, 2, '.', ''),
                    'net' => number_format($amount + $fee, 2, '.', ''),
                    'context' => $context,
                ];
            });

            $rows = $creditRows->concat($spendingRows)->values()->all();

            return TabularExport::download(
                $format,
                'pettycash-board-report-' . now()->format('Ymd-His'),
                [
                    'Section' => 'section',
                    'Date' => 'date',
                    'Category' => 'category',
                    'Sub Type' => 'sub_type',
                    'Reference' => 'reference',
                    'Description' => 'description',
                    'Amount' => 'amount',
                    'Fee' => 'transaction_cost',
                    'Net' => 'net',
                    'Context' => 'context',
                ],
                $rows
            );
        }

        $pdf = Pdf::loadView('pettycash::reports.general_pdf', [
            'credits' => $credits,
            'spendings' => $spendings,
            'from' => $data['from'] ?? null,
            'to' => $data['to'] ?? null,
            'batchIds' => $batchIds,
            'viewMode' => $view,

            // totals (gross + fees + net)
            'creditedAmountTotal' => $creditedAmountTotal,
            'creditedFeeTotal' => $creditedFeeTotal,
            'creditedNetTotal' => $creditedNetTotal,

            'debitAmountTotal' => $debitAmountTotal,
            'debitFeeTotal' => $debitFeeTotal,
            'debitNetTotal' => $debitNetTotal,

            'balance' => $balance,

            'totalsNet' => $totalsNet,
            'chartB64' => $chartB64,

            'bike' => !empty($data['bike_id']) ? Bike::find($data['bike_id']) : null,
            'respondent' => !empty($data['respondent_id']) ? Respondent::find($data['respondent_id']) : null,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('pettycash-board-report.pdf');
    }
}
