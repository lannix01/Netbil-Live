<?php

namespace App\Modules\PettyCash\Support;

use Illuminate\Support\Facades\DB;

class UnifiedLedger
{
    /**
     * Unified ledger query:
     * - petty_spendings (has batch_id)
     * - petty_bike_services (no batch_id, so batch_no will be NULL)
     *
     * Columns returned:
     * id, date, reference, type, sub_type, description,
     * plate_no, meter_no, amount, transaction_cost, net_total,
     * source, batch_id, batch_no
     */
    public static function query()
    {
        $sp = DB::table('petty_spendings as p')
            ->leftJoin('petty_bikes as b', function ($join) {
                $join->on('b.id', '=', 'p.related_id')
                     ->where('p.type', '=', 'bike');
            })
            ->leftJoin('petty_batches as bt', 'bt.id', '=', 'p.batch_id')
            ->selectRaw("
                p.id as id,
                p.date as date,
                p.reference as reference,
                p.type as type,
                p.sub_type as sub_type,
                p.description as description,
                b.plate_no as plate_no,
                p.meter_no as meter_no,
                COALESCE(p.amount,0) as amount,
                COALESCE(p.transaction_cost,0) as transaction_cost,
                (COALESCE(p.amount,0) + COALESCE(p.transaction_cost,0)) as net_total,
                'spending' as source,
                p.batch_id as batch_id,
                bt.batch_no as batch_no
            ");

        $sv = DB::table('petty_bike_services as s')
            ->leftJoin('petty_bikes as b', 'b.id', '=', 's.bike_id')
            ->selectRaw("
                s.id as id,
                s.service_date as date,
                s.reference as reference,
                'bike' as type,
                'service' as sub_type,
                s.work_done as description,
                b.plate_no as plate_no,
                NULL as meter_no,
                COALESCE(s.amount,0) as amount,
                COALESCE(s.transaction_cost,0) as transaction_cost,
                (COALESCE(s.amount,0) + COALESCE(s.transaction_cost,0)) as net_total,
                'bike_service' as source,
                NULL as batch_id,
                NULL as batch_no
            ");

        return DB::query()->fromSub($sp->unionAll($sv), 'u');
    }
}
