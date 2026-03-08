<?php

namespace App\Http\Controllers;

use App\Services\Sms\AdvantaSmsService;
use Illuminate\Http\Request;
use App\Libraries\RouterOSAPI;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index()
{
    $api = new RouterOSAPI();
    $config = config('mikrotik');

    if (!$api->connect($config['host'], $config['user'], $config['pass'], $config['port'])) {
        return view('dashboard.index', [
            'error' => 'Unable to connect to MikroTik RouterOS API',
        ]);
    }

    /* =============================
       SYSTEM METRICS
    ============================= */
    $resource = $api->comm('/system/resource/print')[0] ?? [];
    $disk = $api->comm('/disk/print')[0] ?? [];

    $totalMem = $resource['total-memory'] ?? 1;
    $freeMem  = $resource['free-memory'] ?? 0;

    $ramUsedMB = round(($totalMem - $freeMem) / 1024 / 1024, 1);
    $ramPercent = round((($totalMem - $freeMem) / $totalMem) * 100);

    $cpuLoad = (int)($resource['cpu-load'] ?? 0);
    $uptime  = $resource['uptime'] ?? '0s';

    $hddTotal = $disk['total-space'] ?? 1;
    $hddFree  = $disk['free-space'] ?? 0;

    $hddUsedGB = round(($hddTotal - $hddFree) / 1024 / 1024 / 1024, 1);
    $hddPercent = round((($hddTotal - $hddFree) / $hddTotal) * 100);

    /* =============================
       INTERFACE TRAFFIC
    ============================= */
    $interfaces = $api->comm('/interface/print') ?? [];
    $interfaceTraffic = [];

    foreach ($interfaces as $iface) {
        if (!isset($iface['name'])) continue;

        $monitor = $api->comm('/interface/monitor-traffic', [
            'interface' => $iface['name'],
            'once' => ''
        ]);

        if (!isset($monitor[0])) continue;

        $interfaceTraffic[] = [
            'interface_name' => $iface['name'],
            'rx_bps' => (int)($monitor[0]['rx-bits-per-second'] ?? 0),
            'tx_bps' => (int)($monitor[0]['tx-bits-per-second'] ?? 0),
            'status' => ($iface['running'] ?? 'false') === 'true' ? 'up' : 'down',
        ];
    }

    /* =============================
       HOTSPOT USERS
    ============================= */
    $users  = $api->comm('/ip/hotspot/user/print') ?? [];
    $active = $api->comm('/ip/hotspot/active/print') ?? [];

    $activeUsernames = collect($active)->pluck('user')->toArray();
    $totalUsers = count($users);
    $activeUsers = 0;

    foreach ($users as $user) {
        if (in_array($user['name'] ?? '', $activeUsernames)) {
            $activeUsers++;
        }
    }

    $hosts = $api->comm('/ip/hotspot/host/print') ?? [];
    $uniqueHosts = collect($hosts)->unique('mac-address')->count();

    $subscriptionRate = $totalUsers > 0
        ? round(($activeUsers / $totalUsers) * 100)
        : 0;

    /* =============================
   ADVANTA SMS BALANCE  (fixed)
============================= */
$advantaBalance = null;

try {
    $advanta = new AdvantaSmsService();
    $raw = $advanta->balance();

    logger()->info('Advanta balance response', ['raw' => $raw]);

    // Case 1: JSON response like docs
    if (is_array($raw) && (($raw['response-code'] ?? null) == 200)) {
        $credit = $raw['credit'] ?? null;

        if ($credit !== null) {
            // convert "545.00" => 545
            $advantaBalance = (float) $credit;
        }
    }

    // Case 2: Some gateways respond with plain text (backup)
    if ($advantaBalance === null && isset($raw['raw'])) {
        preg_match('/(\d+(\.\d+)?)/', $raw['raw'], $m);
        $advantaBalance = isset($m[1]) ? (float) $m[1] : null;
    }

} catch (\Throwable $e) {
    logger()->error('Advanta balance error', [
        'msg' => $e->getMessage(),
    ]);
}


    /* =============================
       ONE RETURN. ALL DATA.
    ============================= */
    return view('dashboard.index', [
        'latestMetrics' => (object)[
            'cpu_percent' => $cpuLoad,
            'ram_used' => $ramUsedMB,
            'ram_percent' => $ramPercent,
            'hdd_used' => $hddUsedGB,
            'hdd_percent' => $hddPercent,
            'uptime' => $uptime,
        ],
        'traffic' => $interfaceTraffic,
        'totalUsers' => $totalUsers,
        'activeUsers' => $activeUsers,
        'uniqueHosts' => $uniqueHosts,
        'subscriptionRate' => $subscriptionRate,
        'advantaBalance' => $advantaBalance, //  NOW EXISTS
    ]);
}


    public function data()
    {
        return response()->json([
            'message' => 'Use index() for dashboard data.',
        ]);
    }

    public function refresh()
    {
        return $this->data();
    }
}
