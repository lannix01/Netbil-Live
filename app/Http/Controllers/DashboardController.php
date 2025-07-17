<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Libraries\RouterOSAPI;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index()
    {
        $api = new RouterOSAPI();
        $config = config('mikrotik');

        // Connect to MikroTik
        if (!$api->connect($config['host'], $config['user'], $config['pass'], $config['port'])) {
            return view('dashboard.index', [
                'error' => 'Unable to connect to MikroTik RouterOS API',
            ]);
        }

        // =============================
        // SYSTEM METRICS (RAM, CPU, HDD)
        // =============================
        $resource = $api->comm('/system/resource/print')[0] ?? [];
        $disk = $api->comm('/disk/print')[0] ?? [];

        $totalMem = $resource['total-memory'] ?? 1;
        $freeMem = $resource['free-memory'] ?? 0;
        $ramUsedMB = round(($totalMem - $freeMem) / 1024 / 1024, 1);
        $ramPercent = round((($totalMem - $freeMem) / $totalMem) * 100);

        $cpuLoad = (int)($resource['cpu-load'] ?? 0);
        $uptime = $resource['uptime'] ?? '0s';

        $hddTotal = $disk['total-space'] ?? 1;
        $hddFree = $disk['free-space'] ?? 0;
        $hddUsedGB = round(($hddTotal - $hddFree) / 1024 / 1024 / 1024, 1);
        $hddPercent = round((($hddTotal - $hddFree) / $hddTotal) * 100);

        // =============================
        // INTERFACE TRAFFIC (Real-Time)
        // =============================
     $interfaces = $api->comm('/interface/print') ?? [];
        $interfaceTraffic = [];

        foreach ($interfaces as $iface) {
            if (!isset($iface['name'])) continue;

            $ifaceName = $iface['name'];
            $monitor = $api->comm('/interface/monitor-traffic', [
                'interface' => $ifaceName,
                'once' => ''
            ]);

            if (!isset($monitor[0])) continue;

            $interfaceTraffic[] = [
                'interface_name' => $ifaceName,
                'rx_bps' => (int)($monitor[0]['rx-bits-per-second'] ?? 0),
                'tx_bps' => (int)($monitor[0]['tx-bits-per-second'] ?? 0),
                'status' => ($iface['running'] ?? 'false') === 'true' ? 'up' : 'down',
            ];
        }
        // =============================
        // HOTSPOT USERS & ACTIVITY
        // =============================
        $users = $api->comm('/ip/hotspot/user/print') ?? [];
        $active = $api->comm('/ip/hotspot/active/print') ?? [];

        $activeUsernames = collect($active)->pluck('user')->toArray();
        $totalUsers = count($users);
        $activeUsers = 0;

        $userData = [];

        foreach ($users as $user) {
            $username = $user['name'] ?? 'guest';
            $isActive = in_array($username, $activeUsernames);

            if ($isActive) $activeUsers++;

            $userData[] = [
                'username' => $username,
                'profile' => $user['profile'] ?? 'default',
                'server' => $user['server'] ?? '-',
                'uptime' => $user['uptime'] ?? '0s',
                'last_seen' => $user['last-seen'] ?? 'never',
                'status' => $isActive ? 'active' : 'inactive',
            ];
        }

        // =============================
        // HOSTS = UNIQUE DEVICE COUNT
        // =============================
        $hosts = $api->comm('/ip/hotspot/host/print') ?? [];
        $uniqueHosts = collect($hosts)->unique('mac-address')->count();

        // =============================
        // SUBSCRIPTION RATE (%)
        // =============================
        $subscriptionRate = $totalUsers > 0
            ? round(($activeUsers / $totalUsers) * 100)
            : 0;

        // Return to dashboard view
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
            'hotspotUsers' => $userData,
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'uniqueHosts' => $uniqueHosts,
            'subscriptionRate' => $subscriptionRate,
        ]);
    }

    public function data()
    {
        return response()->json([
            'message' => 'Use index() for dashboard data.',
        ]);
    }
}