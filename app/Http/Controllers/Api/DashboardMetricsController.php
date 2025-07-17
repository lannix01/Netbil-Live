<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardMetricsController extends Controller
{
    public function index()
    {
        // 1. System stats (placeholder values — you can replace with real logic or shell_exec if allowed)
        $ramUsage = '512MB / 1024MB';
        $ramPercent = 50;
        $cpuUsage = 34;
        $storageUsage = '30GB / 100GB';
        $hddPercent = 30;
        $uptime = '2 days 4 hrs';

        // 2. User metrics (you may need to change table/column names based on your DB)
        $activeUsers = DB::table('users')->where('status', 'active')->count();
        $totalUsers = DB::table('users')->count();
        $uniqueVisitors = DB::table('users')->distinct('ip_address')->count('ip_address'); // placeholder logic

        // 3. Interface traffic (placeholder)
        $interfaceTraffic = [
            [
                'name' => 'ether1',
                'rx' => rand(1000000, 10000000),
                'tx' => rand(1000000, 10000000),
                'status' => 'up'
            ],
            [
                'name' => 'ether2',
                'rx' => rand(1000000, 10000000),
                'tx' => rand(1000000, 10000000),
                'status' => 'down'
            ]
        ];

        // 4. Uplink info (mock data for now)
        $uplink = [
            'interface' => 'ether1',
            'rx' => rand(1000000, 10000000),
            'tx' => rand(1000000, 10000000)
        ];

        return response()->json([
            'system' => [
                'ram_usage' => $ramUsage,
                'ram_percentage' => $ramPercent,
                'cpu_usage' => $cpuUsage,
                'storage_usage' => $storageUsage,
                'hdd_percentage' => $hddPercent,
                'uptime' => $uptime
            ],
            'active_users_count' => $activeUsers,
            'total_users_count' => $totalUsers,
            'unique_visitors' => $uniqueVisitors,
            'interface_traffic' => $interfaceTraffic,
            'uplink' => $uplink
        ]);
    }
}
