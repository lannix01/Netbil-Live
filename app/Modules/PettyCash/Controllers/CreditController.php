<?php

namespace App\Modules\PettyCash\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\PettyCash\Models\Batch;
use App\Modules\PettyCash\Models\Credit;
use App\Modules\PettyCash\Services\BatchService;
use App\Modules\PettyCash\Support\TabularExport;
use Barryvdh\DomPDF\Facade\Pdf;

class CreditController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');

        $credits = Credit::query()
            ->with('batch')
            ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('date', '<=', $to))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $total = (float) Credit::query()
            ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('date', '<=', $to))
            ->sum('amount');

        return view('pettycash::credits.index', compact('credits', 'total', 'from', 'to'));
    }

    public function create()
    {
        return view('pettycash::credits.create');
    }

    public function store(Request $request, BatchService $batchService)
    {
        $data = $request->validate([
            'reference' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_cost' => ['nullable', 'numeric', 'min:0'],

            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $userId = auth('petty')->id();

        $batch = $batchService->createBatchWithCredit($data, $userId);

        return redirect()
            ->route('petty.batches.show', $batch->id)
            ->with('success', "Credit recorded. Batch created: {$batch->batch_no}");
    }

    public function pdf(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'pdf'));
        $from = $request->query('from');
        $to = $request->query('to');

        $credits = Credit::query()
            ->with('batch')
            ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('date', '<=', $to))
            ->orderByDesc('date')
            ->get();

        $total = (float) $credits->sum('amount');

        if (in_array($format, ['csv', 'excel', 'xls', 'xlsx'], true)) {
            $rows = $credits->map(function ($c) {
                $amount = (float) $c->amount;
                $fee = (float) ($c->transaction_cost ?? 0);

                return [
                    'date' => $c->date?->format('Y-m-d'),
                    'reference' => $c->reference ?? '',
                    'amount' => number_format($amount, 2, '.', ''),
                    'transaction_cost' => number_format($fee, 2, '.', ''),
                    'net_credit' => number_format($amount - $fee, 2, '.', ''),
                    'batch_no' => $c->batch?->batch_no ?? '',
                    'description' => $c->description ?? '',
                ];
            })->all();

            return TabularExport::download(
                $format,
                'pettycash-credits-' . now()->format('Ymd-His'),
                [
                    'Date' => 'date',
                    'MPESA Ref' => 'reference',
                    'Amount' => 'amount',
                    'Transaction Cost' => 'transaction_cost',
                    'Net Credit' => 'net_credit',
                    'Batch' => 'batch_no',
                    'Description' => 'description',
                ],
                $rows
            );
        }

        $pdf = Pdf::loadView('pettycash::reports.credits_pdf', [
            'credits' => $credits,
            'total' => $total,
            'from' => $from,
            'to' => $to,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('pettycash-credits.pdf');
    }

    public function edit(Credit $credit)
{
    return view('pettycash::credits.edit', compact('credit'));
}

public function update(Request $request, Credit $credit)
{
    $data = $request->validate([
        'reference' => ['nullable', 'string', 'max:255'],
        'amount' => ['required', 'numeric', 'min:0.01'],
        'transaction_cost' => ['nullable', 'numeric', 'min:0'],
        'date' => ['required', 'date'],
        'description' => ['nullable', 'string', 'max:255'],
    ]);

    // Update credit row
    $credit->update([
        'reference' => $data['reference'] ?? null,
        'amount' => $data['amount'],
        'transaction_cost' => $data['transaction_cost'] ?? 0,
        'date' => $data['date'],
        'description' => $data['description'] ?? null,
    ]);

    // Keep batch credited_amount aligned (net credits math will be calculated in reports anyway,
    // but we keep raw credited_amount consistent for your batch summary screens if you use it)
    $batch = Batch::find($credit->batch_id);
    if ($batch) {
        $sumCredits = (float) Credit::where('batch_id', $batch->id)->sum('amount');
        $batch->credited_amount = $sumCredits;
        $batch->save();
    }

    return redirect()->route('petty.credits.index')->with('success', 'Credit updated.');
}

}
