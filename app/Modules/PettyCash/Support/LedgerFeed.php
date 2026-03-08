<?php

namespace App\Modules\PettyCash\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class LedgerFeed
{
    /**
     * Unified cash-out feed: Spendings + Bike Services.
     *
     * Output columns:
     * - id
     * - date
     * - reference
     * - description
     * - type
     * - sub_type
     * - related_id
     * - amount
     * - transaction_cost
     * - net_total
     * - source (spending|bike_service)
     */
    public static function outflowFeedQuery(): Builder
    {
        // Adjust table name if yours differs
        $spendings = DB::table('petty_spendings')
            ->selectRaw("
                id,
                date,
                reference,
                description,
                type,
                sub_type,
                related_id,
                COALESCE(amount,0) as amount,
                COALESCE(transaction_cost,0) as transaction_cost,
                (COALESCE(amount,0) + COALESCE(transaction_cost,0)) as net_total,
                'spending' as source
            ");

        $services = DB::table('petty_bike_services as s')
            ->leftJoin('petty_bikes as b', 'b.id', '=', 's.bike_id')
            ->selectRaw("
                s.id as id,
                s.service_date as date,
                s.reference as reference,
                CONCAT(
                    'Bike Service ',
                    COALESCE(b.plate_no,''),
                    CASE
                        WHEN s.work_done IS NULL OR s.work_done = '' THEN ''
                        ELSE CONCAT(' | ', s.work_done)
                    END
                ) as description,
                'bike' as type,
                'service' as sub_type,
                s.bike_id as related_id,
                COALESCE(s.amount,0) as amount,
                COALESCE(s.transaction_cost,0) as transaction_cost,
                (COALESCE(s.amount,0) + COALESCE(s.transaction_cost,0)) as net_total,
                'bike_service' as source
            ");

        // Union into a single dataset
        return DB::query()->fromSub(
            $spendings->unionAll($services),
            'cashout'
        );
    }

    /**
     * Total net outflow = sum(amount + transaction_cost) across both sources.
     */
    public static function totalOutflowNet(): float
    {
        return (float) self::outflowFeedQuery()->sum('net_total');
    }
}
