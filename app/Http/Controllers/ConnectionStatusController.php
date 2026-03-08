<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MikrotikService;
use App\Models\Package;

class ConnectionStatusController extends Controller
{
    public function status(Request $request, MikrotikService $mikrotik)
    {
        $mac = $request->get('mac');
        $ip  = $request->get('ip');

        $session = $mikrotik->getActiveSession($mac, $ip);

        if (! $session) {
            return redirect()->route('connect.index');
        }

        $username = $session['user'];

        // HOTSPOT PACKAGE USER
        if (str_starts_with($username, 'hs_')) {
            $package = Package::where('mikrotik_profile', $session['profile'])->first();

            return view('connect.status-hotspot', [
                'session' => $session,
                'package' => $package,
            ]);
        }

        // METERED USER
        $usage = $mikrotik->getMeteredUsage($username);

        return view('connect.status-metered', [
            'session' => $session,
            'usage'   => $usage,
        ]);
    }

    public function reconnect(Request $request, MikrotikService $mikrotik)
    {
        $mikrotik->relogin($request->get('username'), $request->get('ip'));

        return redirect()->route('connect.status', $request->all());
    }

    public function renew()
    {
        return redirect()->route('connect.index');
    }
}