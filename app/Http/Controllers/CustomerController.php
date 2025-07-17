<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Libraries\RouterOSAPI;
use Illuminate\Support\Facades\View;

class CustomerController extends Controller
{
    public function index()
    {
        $api = new RouterOSAPI();
        $config = config('mikrotik');

        if (!$api->connect($config['host'], $config['user'], $config['pass'], $config['port'])) {
            return view('customers.index', ['error' => 'Could not connect to MikroTik']);
        }

        $users = $api->comm('/ip/hotspot/user/print') ?? [];
        $activeSessions = $api->comm('/ip/hotspot/active/print') ?? [];
        $hosts = $api->comm('/ip/hotspot/host/print') ?? [];
        $cookies = $api->comm('/ip/hotspot/cookie/print') ?? [];

        $activeMacs = collect($activeSessions)->pluck('mac-address')->toArray();
        $finalUsers = [];

        foreach ($users as $user) {
            $mac = $user['mac-address'] ?? null;
            $username = $user['name'] ?? 'guest';
            $profile = $user['profile'] ?? 'default';
            $server = $user['server'] ?? '-';
            $uptime = $user['uptime'] ?? '0s';

            if (!$mac) {
                $status = 'initial';
            } elseif (in_array($mac, $activeMacs)) {
                $status = 'active';
            } else {
                $status = 'inactive';
            }

            $finalUsers[] = [
                'username' => $username,
                'mac' => $mac,
                'profile' => $profile,
                'server' => $server,
                'uptime' => $uptime,
                'status' => $status
            ];
        }

        return view('customers.index', [
            'users' => $finalUsers,
            'activeSessions' => $activeSessions,
            'hosts' => $hosts,
            'cookies' => $cookies
        ]);
    }


public function section(Request $request, $section)
{
    switch ($section) {
        case 'users':
            $users = $this->fetchHotspotUsers();
            return view('customers.partials.users', compact('users'));

        case 'hosts':
            $hosts = $this->fetchHotspotHosts();
            return view('customers.partials.hosts', compact('hosts'));

        case 'cookies':
            $cookies = $this->fetchHotspotCookies();
            return view('customers.partials.cookies', compact('cookies'));

        case 'sessions':
            $activeSessions = $this->fetchHotspotSessions();
            return view('customers.partials.sessions', compact('activeSessions'));

        default:
            return response()->json(['error' => 'Invalid section.'], 404);
    }
}

    public function disconnect(Request $request)
    {
        $mac = $request->mac;

        $api = new RouterOSAPI();
        $config = config('mikrotik');

        if (!$api->connect($config['host'], $config['user'], $config['pass'], $config['port'])) {
            return response()->json(['status' => 'fail', 'message' => 'Connection failed']);
        }

        $actives = $api->comm('/ip/hotspot/active/print') ?? [];

        foreach ($actives as $session) {
            if (($session['mac-address'] ?? '') === $mac) {
                $api->comm('/ip/hotspot/active/remove', [
                    '.id' => $session['.id']
                ]);
                return response()->json(['status' => 'success', 'message' => 'User disconnected']);
            }
        }

        return response()->json(['status' => 'not_found', 'message' => 'MAC not found in active sessions']);
    }

    public function monitorTraffic(Request $request)
{
    $username = $request->username;

    $api = new RouterOSAPI();
    $config = config('mikrotik');

    if (!$api->connect($config['host'], $config['user'], $config['pass'], $config['port'])) {
        return response()->json(['error' => 'Connection failed']);
    }

    // Get all active sessions
    $actives = $api->comm('/ip/hotspot/active/print') ?? [];

    // Find the session with the given username
    foreach ($actives as $session) {
        if (($session['user'] ?? '') === $username) {
            $interface = $session['interface'] ?? null;
            if (!$interface) {
                return response()->json(['error' => 'Interface not found']);
            }

            $monitor = $api->comm('/interface/monitor-traffic', [
                'interface' => $interface,
                'once' => ''
            ])[0] ?? [];

            return response()->json([
                'rx' => (int)($monitor['rx-bits-per-second'] ?? 0),
                'tx' => (int)($monitor['tx-bits-per-second'] ?? 0)
            ]);
        }
    }

    return response()->json(['error' => 'Active session not found for this user']);
}

    private function fetchHotspotUsers()
{
    $api = new RouterOSAPI();
    $config = config('mikrotik');

    if (!$api->connect($config['host'], $config['user'], $config['pass'], $config['port'])) {
        return [];
    }

    $users = $api->comm('/ip/hotspot/user/print') ?? [];
    $activeSessions = $api->comm('/ip/hotspot/active/print') ?? [];
    $activeMacs = collect($activeSessions)->pluck('mac-address')->toArray();
    $finalUsers = [];

    foreach ($users as $user) {
        $mac = $user['mac-address'] ?? null;
        $username = $user['name'] ?? 'guest';
        $profile = $user['profile'] ?? 'default';
        $server = $user['server'] ?? '-';
        $uptime = $user['uptime'] ?? '0s';

        $status = !$mac ? 'initial' : (in_array($mac, $activeMacs) ? 'active' : 'inactive');

        $finalUsers[] = [
            'username' => $username,
            'mac' => $mac,
            'profile' => $profile,
            'server' => $server,
            'uptime' => $uptime,
            'status' => $status
        ];
    }

    return $finalUsers;
}

private function fetchHotspotHosts()
{
    $api = new RouterOSAPI();
    $config = config('mikrotik');

    return $api->connect($config['host'], $config['user'], $config['pass'], $config['port'])
        ? $api->comm('/ip/hotspot/host/print') ?? []
        : [];
}

private function fetchHotspotCookies()
{
    $api = new RouterOSAPI();
    $config = config('mikrotik');

    return $api->connect($config['host'], $config['user'], $config['pass'], $config['port'])
        ? $api->comm('/ip/hotspot/cookie/print') ?? []
        : [];
}

private function fetchHotspotSessions()
{
    $api = new RouterOSAPI();
    $config = config('mikrotik');

    return $api->connect($config['host'], $config['user'], $config['pass'], $config['port'])
        ? $api->comm('/ip/hotspot/active/print') ?? []
        : [];
}

}