<?php

namespace App\Http\Controllers;

use App\Libraries\RouterOSAPI;
use App\Models\SystemAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LogsController extends Controller
{
    public function index()
    {
        return view('logs.index', [
            'routerHost' => (string)(config('mikrotik.host') ?? ''),
            'timezone' => (string)(config('app.timezone') ?? 'UTC'),
        ]);
    }

    public function fetch(Request $request)
    {
        $scope = strtolower(trim((string)$request->query('scope', 'all')));

        $payload = [
            'server_time' => now()->format('Y-m-d H:i:s'),
            'timezone' => (string)(config('app.timezone') ?? 'UTC'),
        ];

        if ($scope === 'audit') {
            $payload['audit'] = $this->fetchAuditLogs($request);
            return response()->json($payload);
        }

        if ($scope === 'router') {
            $payload['router'] = $this->fetchRouterLogs();
            return response()->json($payload);
        }

        $payload['audit'] = $this->fetchAuditLogs($request);
        $payload['router'] = $this->fetchRouterLogs();

        return response()->json($payload);
    }

    private function fetchRouterLogs(): array
    {
        $api = new RouterOSAPI();
        $config = config('mikrotik');

        $host = (string)($config['host'] ?? '');
        $user = (string)($config['user'] ?? '');
        $pass = (string)($config['pass'] ?? '');

        if ($host === '' || $user === '') {
            return [
                'connected' => false,
                'host' => $host,
                'error' => 'Missing MikroTik host/user configuration.',
                'logs' => [],
            ];
        }

        try {
            $api->port = (int)($config['port'] ?? 8728);
            $api->timeout = max(1, (int)($config['timeout'] ?? 2));
            $api->attempts = 1;
            $api->delay = 0;

            if (!$api->connect($host, $user, $pass)) {
                return [
                    'connected' => false,
                    'host' => $host,
                    'error' => 'Router connection failed.',
                    'logs' => [],
                ];
            }

            $rawLogs = $api->comm('/log/print');
            $api->disconnect();

            $logs = [];
            if (is_array($rawLogs)) {
                foreach ($rawLogs as $entry) {
                    if (!is_array($entry)) {
                        continue;
                    }
                    $logs[] = [
                        'time' => (string)($entry['time'] ?? ''),
                        'topics' => (string)($entry['topics'] ?? ''),
                        'message' => (string)($entry['message'] ?? ''),
                    ];
                }
            }

            return [
                'connected' => true,
                'host' => $host,
                'error' => null,
                'logs' => $logs,
            ];
        } catch (\Throwable $e) {
            try {
                $api->disconnect();
            } catch (\Throwable) {
                // ignore disconnect errors
            }

            Log::warning('MikroTik logs fetch failed', [
                'error' => $e->getMessage(),
                'host' => $host,
            ]);

            return [
                'connected' => false,
                'host' => $host,
                'error' => 'Could not load MikroTik logs.',
                'logs' => [],
            ];
        }
    }

    private function fetchAuditLogs(Request $request): array
    {
        try {
            $hasAuditTable = Schema::hasTable('system_audit_logs');
        } catch (\Throwable $e) {
            Log::warning('System audit table check failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'available' => false,
                'error' => 'Database unavailable for audit logs.',
                'logs' => [],
            ];
        }

        if (!$hasAuditTable) {
            return [
                'available' => false,
                'error' => 'Audit table not available yet.',
                'logs' => [],
            ];
        }

        try {
            $q = trim((string)$request->query('q', ''));
            $limit = (int)$request->query('limit', 200);
            $limit = max(50, min($limit, 400));

            $query = SystemAuditLog::query()->latest('id');
            if ($q !== '') {
                $query->where(function ($inner) use ($q) {
                    $like = '%' . $q . '%';
                    $inner->where('event', 'like', $like)
                        ->orWhere('action', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('actor_name', 'like', $like)
                        ->orWhere('actor_email', 'like', $like)
                        ->orWhere('route_name', 'like', $like)
                        ->orWhere('path', 'like', $like);
                });
            }

            $rows = $query->limit($limit)->get();
            $logs = $rows->map(function (SystemAuditLog $row) {
                return [
                    'id' => $row->id,
                    'time' => optional($row->created_at)->format('Y-m-d H:i:s'),
                    'event' => (string)$row->event,
                    'action' => (string)($row->action ?? ''),
                    'description' => (string)($row->description ?? ''),
                    'method' => (string)($row->method ?? ''),
                    'route' => (string)($row->route_name ?? ''),
                    'path' => (string)($row->path ?? ''),
                    'status' => (int)($row->status_code ?? 0),
                    'ip' => (string)($row->ip_address ?? ''),
                    'user' => [
                        'id' => $row->user_id,
                        'name' => (string)($row->actor_name ?? ''),
                        'email' => (string)($row->actor_email ?? ''),
                        'role' => (string)($row->actor_role ?? ''),
                    ],
                    'context' => is_array($row->context) ? $row->context : [],
                ];
            })->values()->all();

            return [
                'available' => true,
                'error' => null,
                'logs' => $logs,
            ];
        } catch (\Throwable $e) {
            Log::warning('System audit logs fetch failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'available' => true,
                'error' => 'Could not load system audit logs.',
                'logs' => [],
            ];
        }
    }
}
