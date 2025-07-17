<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Libraries\RouterOSAPI;

class DeviceController extends Controller {

    public function index()
    {
        $api = new RouterOSAPI();
        $config = config('mikrotik');

        if ($api->connect($config['host'], $config['user'], $config['pass'], $config['port'])) {
            $api->write('/ip/dhcp-server/lease/print');
            $leases = $api->read();
            $api->disconnect();
        } else {
            $leases = [];
        }

        return view('devices.index', compact('leases'));
    }
}
