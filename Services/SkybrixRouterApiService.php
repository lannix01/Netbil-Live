<?php

namespace App\Modules\Inventory\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SkybrixRouterApiService
{
    private const ENDPOINTS = [
        'sites' => '/onts',
        'in-stock' => '/inventory/in-stock-routers',
        'with-techs' => '/inventory/undeployed-routers',
        'deployed' => '/inventory/deployed-routers',
        'faulty' => '/inventory/faulty-routers',
    ];

    public function sites(array $params = []): array
    {
        return $this->request('sites', $params);
    }

    public function inStockRouters(array $params = []): array
    {
        return $this->request('in-stock', $params);
    }

    public function routersWithTechs(array $params = []): array
    {
        return $this->request('with-techs', $params);
    }

    public function deployedRouters(array $params = []): array
    {
        return $this->request('deployed', $params);
    }

    public function faultyRouters(array $params = []): array
    {
        return $this->request('faulty', $params);
    }

    private function request(string $section, array $params): array
    {
        $cfg = $this->config();
        if (!$cfg['enabled']) {
            return $this->unavailable('Skybrix router API is disabled.');
        }

        if (!$cfg['configured']) {
            return $this->unavailable('Skybrix router API credentials are not configured.');
        }

        $path = self::ENDPOINTS[$section] ?? null;
        if ($path === null) {
            return $this->unavailable('Unknown Skybrix router API section requested.');
        }

        $query = $this->sanitizeParams($section, $params, $cfg);
        $url = rtrim($cfg['base_url'], '/') . $path;

        try {
            $response = Http::withBasicAuth($cfg['username'], $cfg['password'])
                ->acceptJson()
                ->timeout($cfg['timeout_seconds'])
                ->get($url, $query);

            if (!$response->successful()) {
                return $this->unavailable('Skybrix router API returned status ' . $response->status() . '.');
            }

            $payload = $response->json();
            $rows = is_array($payload) && is_array($payload['data'] ?? null)
                ? array_values($payload['data'])
                : [];
            $pagination = is_array($payload) && is_array($payload['pagination'] ?? null)
                ? $payload['pagination']
                : [];

            return [
                'available' => true,
                'message' => (string) ($payload['message'] ?? 'Request processed successfully.'),
                'fetched_at' => now()->toIso8601String(),
                'rows' => $rows,
                'pagination' => [
                    'current_page' => max(1, (int) ($pagination['current_page'] ?? ($query['page'] ?? 1))),
                    'per_page' => max(1, (int) ($pagination['per_page'] ?? ($query['page_size'] ?? $cfg['default_page_size']))),
                    'total' => max(0, (int) ($pagination['total'] ?? count($rows))),
                    'page_count' => max(1, (int) ($pagination['page_count'] ?? 1)),
                ],
                'request' => [
                    'section' => $section,
                    'url' => $url,
                    'params' => $query,
                ],
            ];
        } catch (\Throwable $e) {
            Log::warning('Skybrix router API request failed.', [
                'section' => $section,
                'params' => $query,
                'error' => $e->getMessage(),
            ]);

            return $this->unavailable('Skybrix router API is currently unreachable.');
        }
    }

    private function sanitizeParams(string $section, array $params, array $cfg): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $defaultPageSize = max(1, (int) ($cfg['default_page_size'] ?? 20));
        $maxPageSize = max($defaultPageSize, (int) ($cfg['max_page_size'] ?? 100));
        $pageSize = max(1, min($maxPageSize, (int) ($params['page_size'] ?? $defaultPageSize)));

        $query = [
            'page' => $page,
            'page_size' => $pageSize,
        ];

        $search = trim((string) ($params['search'] ?? ''));
        if ($search !== '') {
            $query['search'] = $search;
        }

        if ($section === 'in-stock') {
            $batchNumber = trim((string) ($params['batch_number'] ?? ''));
            if ($batchNumber !== '') {
                $query['batch_number'] = $batchNumber;
            }
        }

        if ($section === 'with-techs') {
            $technicianId = (int) ($params['technician_id'] ?? 0);
            if ($technicianId > 0) {
                $query['technician_id'] = $technicianId;
            }
        }

        if ($section === 'deployed') {
            $siteId = (int) ($params['site_id'] ?? 0);
            if ($siteId > 0) {
                $query['site_id'] = $siteId;
            }

            if (array_key_exists('is_primary', $params) && $params['is_primary'] !== null && $params['is_primary'] !== '') {
                $query['is_primary'] = filter_var($params['is_primary'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            }
        }

        return $query;
    }

    private function config(): array
    {
        $cfg = (array) config('inventory.router_api', []);

        $username = trim((string) ($cfg['username'] ?? ''));
        $password = trim((string) ($cfg['password'] ?? ''));

        return [
            'enabled' => (bool) ($cfg['enabled'] ?? true),
            'configured' => $username !== '' && $password !== '',
            'base_url' => (string) ($cfg['base_url'] ?? 'https://api.skybrix.co.ke/v1'),
            'username' => $username,
            'password' => $password,
            'timeout_seconds' => max(3, (int) ($cfg['timeout_seconds'] ?? 12)),
            'default_page_size' => max(1, (int) ($cfg['default_page_size'] ?? 20)),
            'max_page_size' => max(1, (int) ($cfg['max_page_size'] ?? 100)),
        ];
    }

    private function unavailable(string $message): array
    {
        return [
            'available' => false,
            'message' => $message,
            'fetched_at' => null,
            'rows' => [],
            'pagination' => [
                'current_page' => 1,
                'per_page' => 0,
                'total' => 0,
                'page_count' => 1,
            ],
            'request' => [
                'section' => null,
                'url' => null,
                'params' => [],
            ],
        ];
    }
}
