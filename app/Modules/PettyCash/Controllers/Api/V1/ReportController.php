<?php

namespace App\Modules\PettyCash\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\Batch;
use App\Modules\PettyCash\Models\Bike;
use App\Modules\PettyCash\Models\Hostel;
use App\Modules\PettyCash\Models\Respondent;
use App\Modules\PettyCash\Services\ReportChartService;
use App\Modules\PettyCash\Services\ReportService;
use App\Modules\PettyCash\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReportController extends Controller
{
    use ApiResponder;

    public function lookups(Request $request)
    {
        $batchLimit = (int) $request->integer('batch_limit', 100);
        if ($batchLimit < 1) {
            $batchLimit = 100;
        }
        if ($batchLimit > 500) {
            $batchLimit = 500;
        }

        return $this->successResponse([
            'batches' => Batch::query()
                ->orderByDesc('id')
                ->limit($batchLimit)
                ->get()
                ->map(fn (Batch $batch) => [
                    'id' => $batch->id,
                    'batch_no' => $batch->batch_no,
                    'opening_balance' => (float) ($batch->opening_balance ?? 0),
                    'credited_amount' => (float) ($batch->credited_amount ?? 0),
                    'created_at' => optional($batch->created_at)->format('Y-m-d H:i:s'),
                ])
                ->values(),
            'bikes' => Bike::query()
                ->orderBy('plate_no')
                ->get()
                ->map(fn (Bike $bike) => [
                    'id' => $bike->id,
                    'plate_no' => $bike->plate_no,
                    'model' => $bike->model,
                    'status' => $bike->status,
                    'is_unroadworthy' => (bool) $bike->is_unroadworthy,
                ])
                ->values(),
            'respondents' => Respondent::query()
                ->orderBy('name')
                ->get()
                ->map(fn (Respondent $respondent) => [
                    'id' => $respondent->id,
                    'name' => $respondent->name,
                    'phone' => $respondent->phone,
                    'category' => $respondent->category,
                ])
                ->values(),
            'hostels' => Hostel::query()
                ->orderBy('hostel_name')
                ->get()
                ->map(fn (Hostel $hostel) => [
                    'id' => $hostel->id,
                    'hostel_name' => $hostel->hostel_name,
                    'meter_no' => $hostel->meter_no,
                    'phone_no' => $hostel->phone_no,
                    'stake' => $hostel->stake,
                    'amount_due' => (float) ($hostel->amount_due ?? 0),
                ])
                ->values(),
            'bucket_defaults' => ['bike:fuel', 'bike:maintenance', 'meal:lunch', 'token:hostel', 'other'],
        ], 'Report lookups fetched.');
    }

    public function general(Request $request, ReportService $reports, ReportChartService $charts)
    {
        $data = $request->validate([
            'batch_ids' => ['nullable', 'array'],
            'batch_ids.*' => ['integer', 'exists:petty_batches,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'bike_id' => ['nullable', 'integer', 'exists:petty_bikes,id'],
            'respondent_id' => ['nullable', 'integer', 'exists:petty_respondents,id'],
            'include' => ['nullable', 'array'],
            'include.*' => ['string'],
            'view' => ['nullable', 'in:combined,split'],
            'q' => ['nullable', 'string', 'max:255'],
            'include_chart' => ['nullable', 'boolean'],
            'include_rows' => ['nullable', 'boolean'],
            'max_rows' => ['nullable', 'integer', 'min:1', 'max:2000'],
        ]);

        $batchIds = $data['batch_ids'] ?? null;
        $include = $data['include'] ?? ['bike:fuel', 'bike:maintenance', 'meal:lunch', 'token:hostel', 'other'];
        $view = $data['view'] ?? 'combined';
        $q = trim((string) ($data['q'] ?? ''));
        $includeRows = (bool) ($data['include_rows'] ?? true);
        $includeChart = (bool) ($data['include_chart'] ?? false);
        $maxRows = (int) ($data['max_rows'] ?? 500);

        $credits = $reports->credits($batchIds, $data['from'] ?? null, $data['to'] ?? null);
        $spendings = $reports->spendings($batchIds, $data['from'] ?? null, $data['to'] ?? null, $include);

        if (!empty($data['bike_id'])) {
            $bikeId = (int) $data['bike_id'];
            $spendings = $spendings
                ->filter(fn ($spending) => $spending->type === 'bike' && (int) $spending->related_id === $bikeId)
                ->values();
        }

        if (!empty($data['respondent_id'])) {
            $respondentId = (int) $data['respondent_id'];
            $spendings = $spendings
                ->filter(fn ($spending) => (int) ($spending->respondent_id ?? 0) === $respondentId)
                ->values();
        }

        if ($q !== '') {
            $credits = $credits
                ->filter(function ($credit) use ($q) {
                    return str_contains(strtolower((string) $credit->reference), strtolower($q))
                        || str_contains(strtolower((string) $credit->description), strtolower($q))
                        || str_contains(strtolower((string) ($credit->batch?->batch_no ?? '')), strtolower($q));
                })
                ->values();

            $spendings = $spendings
                ->filter(function ($spending) use ($q) {
                    return str_contains(strtolower((string) $spending->reference), strtolower($q))
                        || str_contains(strtolower((string) $spending->description), strtolower($q))
                        || str_contains(strtolower((string) $spending->particulars), strtolower($q))
                        || str_contains(strtolower((string) ($spending->respondent?->name ?? '')), strtolower($q))
                        || str_contains(strtolower((string) ($spending->bike?->plate_no ?? '')), strtolower($q))
                        || str_contains(strtolower((string) ($spending->hostel?->hostel_name ?? '')), strtolower($q))
                        || str_contains(strtolower((string) ($spending->batch?->batch_no ?? '')), strtolower($q));
                })
                ->values();
        }

        $creditedAmountTotal = (float) $credits->sum('amount');
        $creditedFeeTotal = (float) $credits->sum(fn ($credit) => (float) ($credit->transaction_cost ?? 0));
        $creditedNetTotal = $creditedAmountTotal - $creditedFeeTotal;

        $debitAmountTotal = (float) $spendings->sum('amount');
        $debitFeeTotal = (float) $spendings->sum(fn ($spending) => (float) ($spending->transaction_cost ?? 0));
        $debitNetTotal = $debitAmountTotal + $debitFeeTotal;

        $balance = $creditedNetTotal - $debitNetTotal;

        $totalsNet = $reports->totalsByBucketNet($spendings);
        $chartB64 = $includeChart ? $charts->barChartBase64($totalsNet) : null;

        $creditsCount = $credits->count();
        $spendingsCount = $spendings->count();

        return $this->successResponse([
            'filters' => [
                'batch_ids' => $batchIds,
                'from' => $data['from'] ?? null,
                'to' => $data['to'] ?? null,
                'bike_id' => $data['bike_id'] ?? null,
                'respondent_id' => $data['respondent_id'] ?? null,
                'include' => $include,
                'view' => $view,
                'q' => $q,
                'include_chart' => $includeChart,
                'include_rows' => $includeRows,
                'max_rows' => $maxRows,
            ],
            'summary' => [
                'credited_amount_total' => round($creditedAmountTotal, 2),
                'credited_fee_total' => round($creditedFeeTotal, 2),
                'credited_net_total' => round($creditedNetTotal, 2),
                'debit_amount_total' => round($debitAmountTotal, 2),
                'debit_fee_total' => round($debitFeeTotal, 2),
                'debit_net_total' => round($debitNetTotal, 2),
                'balance' => round($balance, 2),
                'credits_count' => $creditsCount,
                'spendings_count' => $spendingsCount,
            ],
            'totals_net_by_bucket' => $totalsNet,
            'chart_b64' => $chartB64,
            'credits' => $includeRows
                ? $this->mapCredits($credits->take($maxRows))
                : [],
            'spendings' => $includeRows
                ? $this->mapSpendings($spendings->take($maxRows))
                : [],
            'meta_counts' => [
                'credits_total' => $creditsCount,
                'credits_returned' => $includeRows ? min($creditsCount, $maxRows) : 0,
                'spendings_total' => $spendingsCount,
                'spendings_returned' => $includeRows ? min($spendingsCount, $maxRows) : 0,
            ],
            'context' => [
                'bike' => !empty($data['bike_id']) ? Bike::query()->find($data['bike_id'])?->only(['id', 'plate_no', 'model', 'status']) : null,
                'respondent' => !empty($data['respondent_id']) ? Respondent::query()->find($data['respondent_id'])?->only(['id', 'name', 'phone', 'category']) : null,
            ],
        ], 'General report fetched.');
    }

    private function mapCredits(Collection $credits): array
    {
        return $credits
            ->map(function ($credit) {
                $amount = (float) ($credit->amount ?? 0);
                $fee = (float) ($credit->transaction_cost ?? 0);

                return [
                    'id' => $credit->id,
                    'batch_id' => $credit->batch_id,
                    'batch_no' => $credit->batch?->batch_no,
                    'reference' => $credit->reference,
                    'description' => $credit->description,
                    'date' => optional($credit->date)->format('Y-m-d'),
                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'net_amount' => $amount - $fee,
                    'created_by' => $credit->created_by,
                ];
            })
            ->values()
            ->all();
    }

    private function mapSpendings(Collection $spendings): array
    {
        return $spendings
            ->map(function ($spending) {
                $amount = (float) ($spending->amount ?? 0);
                $fee = (float) ($spending->transaction_cost ?? 0);

                return [
                    'id' => $spending->id,
                    'type' => $spending->type,
                    'sub_type' => $spending->sub_type,
                    'batch_id' => $spending->batch_id,
                    'batch_no' => $spending->batch?->batch_no,
                    'reference' => $spending->reference,
                    'description' => $spending->description,
                    'particulars' => $spending->particulars,
                    'date' => optional($spending->date)->format('Y-m-d'),
                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'net_total' => $amount + $fee,
                    'respondent' => $spending->respondent
                        ? [
                            'id' => $spending->respondent->id,
                            'name' => $spending->respondent->name,
                          ]
                        : null,
                    'bike' => $spending->bike
                        ? [
                            'id' => $spending->bike->id,
                            'plate_no' => $spending->bike->plate_no,
                          ]
                        : null,
                    'hostel' => $spending->hostel
                        ? [
                            'id' => $spending->hostel->id,
                            'hostel_name' => $spending->hostel->hostel_name,
                            'meter_no' => $spending->hostel->meter_no,
                          ]
                        : null,
                ];
            })
            ->values()
            ->all();
    }
}
