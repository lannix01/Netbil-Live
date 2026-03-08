<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Libraries\RouterOSAPI;

class TerminalController extends Controller
{
    public function run(Request $request)
    {
        $request->validate(['command' => 'required|string']);
        $api = new RouterOSAPI();
        $config = config('mikrotik');

        $output = [];

        try {
            if (!$api->connect($config['host'], $config['user'], $config['pass'])) {
                return response()->json(['output' => ["❌ Failed to connect to MikroTik."]]);
            }

            $command = $request->command;
            $cmdParts = explode(' ', $command);
            $mainCmd = array_shift($cmdParts);
            $args = $cmdParts;

            // Convert common commands
            switch (strtolower($mainCmd)) {
                case 'ping':
                    if (count($args) < 1) {
                        $output[] = "Usage: ping <host> [count]";
                    } else {
                        $addr = $args[0];
                        $count = isset($args[1]) ? intval($args[1]) : 5;
                        $res = $api->comm('/ping', ['address' => $addr, 'count' => $count]);
                        foreach ($res as $line) {
                            $output[] = implode(' ', $line);
                        }
                    }
                    break;

                case 'interface/print':
                case 'ip/address/print':
                case 'log/print':
                    $res = $api->comm('/' . $command);
                    foreach ($res as $line) {
                        $output[] = implode(' ', $line);
                    }
                    break;

                default:
                    $res = $api->comm($command); // fallback
                    foreach ($res as $line) {
                        $output[] = implode(' ', $line);
                    }
                    break;
            }

            $api->disconnect();
        } catch (\Throwable $e) {
            $output[] = "❌ Error: " . $e->getMessage();
        }

        return response()->json(['output' => $output]);
    }
}
