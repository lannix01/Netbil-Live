<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Config;
use App\Libraries\RouterosAPI;

class MikrotikTestController extends Controller
{
    public function index()
    {
        require_once app_path('Libraries/routeros_api.class.php');

        $API = new \RouterosAPI();

        $host = config('mikrotik.host');
        $user = config('mikrotik.user');
        $pass = config('mikrotik.pass');
        $port = config('mikrotik.port');
        $timeout = config('mikrotik.timeout');

        $API->port = $port;
        $API->timeout = $timeout;

        $connected = $API->connect($host, $user, $pass);

        $system = null;
        if ($connected) {
            $sysRes = $API->comm('/system/resource/print');
            $identity = $API->comm('/system/identity/print');
            $system = [
                'uptime'    => $sysRes[0]['uptime'] ?? 'Unknown',
                'version'   => $sysRes[0]['version'] ?? '',
                'boardname'=> $sysRes[0]['board-name'] ?? '',
                'cpu'       => $sysRes[0]['cpu'] ?? '',
                'cpuLoad'   => $sysRes[0]['cpu-load'] ?? 0,
                'totalMem'  => round($sysRes[0]['total-memory'] / 1024 / 1024, 1), // MB
                'freeMem'   => round($sysRes[0]['free-memory'] / 1024 / 1024, 1),  // MB
                'identity'  => $identity[0]['name'] ?? '',
            ];
        }

        return view('mikrotiks.index', compact('connected', 'host', 'system'));
    }
}
