<?php

namespace App\Http\Controllers;

require_once app_path('Libraries/routeros_api.class.php');

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MikrotikTestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return $this->system($request);
    }

    protected function connect(): ?\RouterosAPI
    {
        $API = new \RouterosAPI();
        $API->port = config('mikrotik.port');
        $API->timeout = config('mikrotik.timeout');

        if (!$API->connect(
            config('mikrotik.host'),
            config('mikrotik.user'),
            config('mikrotik.pass')
        )) {
            return null;
        }

        return $API;
    }

    /**
     * Handles system info, terminal, and files
     */
    public function system(Request $request = null): JsonResponse
    {
        $API = $this->connect();
        if (!$API) {
            return response()->json(['connected' => false, 'system' => []]);
        }

        $isPost = $request?->isMethod('post') ?? false;

        if ($isPost) {
            $action = $request->input('action');

            // Terminal command
            if ($action === 'terminal') {
                $cmd = trim($request->input('command'));
                $res = $API->comm($cmd);
                $API->disconnect();
                return response()->json(['output' => json_encode($res, JSON_PRETTY_PRINT)]);
            }

            // List files
            if ($action === 'files') {
                $filesRaw = $API->comm('/file/print');
                $list = [];
                if (is_array($filesRaw)) {
                    foreach ($filesRaw as $f) {
                        if (isset($f['name'])) {
                            $list[] = $f['name'];
                        }
                    }
                }
                $API->disconnect();
                return response()->json(['files' => $list]);
            }

            // Load file content
            if ($action === 'getFile') {
                $name = $request->input('name');
                $res = $API->comm('/file/print', ['?name' => $name]);
                $content = $res[0]['contents'] ?? '';
                $API->disconnect();
                return response()->json(['content' => $content]);
            }

            // Save file
            if ($action === 'saveFile') {
                $API->comm('/file/set', [
                    '.id' => $request->input('name'),
                    'contents' => $request->input('content')
                ]);
                $API->disconnect();
                return response()->json(['status' => 'ok']);
            }

            // Add new file
            if ($action === 'addFile') {
                $API->comm('/file/add', [
                    'name' => $request->input('name'),
                    'contents' => $request->input('content') ?? ''
                ]);
                $API->disconnect();
                return response()->json(['status' => 'ok']);
            }

            // Remove file
            if ($action === 'removeFile') {
                $API->comm('/file/remove', ['numbers' => $request->input('name')]);
                $API->disconnect();
                return response()->json(['status' => 'ok']);
            }
        }

        // GET SYSTEM INFO
        $sysRes = $API->comm('/system/resource/print')[0] ?? [];
        $identity = $API->comm('/system/identity/print')[0] ?? [];
        $API->disconnect();

        return response()->json([
            'connected' => true,
            'host' => config('mikrotik.host'),
            'system' => [
                'uptime'    => $sysRes['uptime'] ?? 'Unknown',
                'version'   => $sysRes['version'] ?? '',
                'boardname' => $sysRes['board-name'] ?? '',
                'cpu'       => $sysRes['cpu'] ?? '',
                'cpuLoad'   => (int)($sysRes['cpu-load'] ?? 0),
                'totalMem'  => round(($sysRes['total-memory'] ?? 0) / 1024 / 1024),
                'freeMem'   => round(($sysRes['free-memory'] ?? 0) / 1024 / 1024),
                'identity'  => $identity['name'] ?? '',
            ]
        ]);
    }
}
