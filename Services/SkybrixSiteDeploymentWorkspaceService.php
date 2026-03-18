<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\ItemUnit;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class SkybrixSiteDeploymentWorkspaceService
{
    public function build(Request $request, SkybrixRouterApiService $routerApi, array $filters): array
    {
        $deployed = $this->fetchSection($routerApi, 'deployed', $filters, true);
        $sites = $this->fetchSection($routerApi, 'sites', $filters, true);

        $deployedRows = collect($deployed['rows'] ?? []);
        $siteRows = collect($sites['rows'] ?? []);

        if (($filters['site_id'] ?? 0) > 0) {
            $siteRows = $siteRows->filter(
                fn ($row) => (string) ($row['site_id'] ?? '') === (string) $filters['site_id']
            )->values();
        }

        $inventoryIndex = $this->buildInventoryIndex(['deployed' => $deployedRows]);
        $groups = $this->buildSiteGroups($deployedRows, $siteRows, $inventoryIndex);
        $focusKey = ($filters['focus_site'] ?? '') !== ''
            ? (string) $filters['focus_site']
            : (($filters['site_id'] ?? 0) > 0 ? (string) $filters['site_id'] : '');
        $focus = $focusKey !== '' ? $groups->first(fn (array $group) => $group['site_key'] === $focusKey) : null;
        $paginator = $this->makeCollectionPaginator($groups, max(1, (int) ($filters['page_size'] ?? 20)), $request);

        return [
            'routerData' => $this->combineGroupedResults([$deployed, $sites], $paginator),
            'workspace' => [
                'paginator' => $paginator,
                'focus' => $focus,
                'focus_key' => $focusKey !== '' ? $focusKey : null,
                'group_count' => $groups->count(),
                'router_count' => $deployedRows->count(),
                'site_count' => $groups->count(),
                'technician_count' => 0,
            ],
        ];
    }

    private function buildSiteGroups(Collection $deployedRows, Collection $siteRows, array $inventoryIndex): Collection
    {
        $siteMap = [];

        foreach ($siteRows as $row) {
            $row = (array) $row;
            $siteKey = $this->siteKeyFromRow($row);
            if ($siteKey === null) {
                continue;
            }

            $siteMap[$siteKey]['site'] = $row;
            $siteMap[$siteKey]['routers'] = $siteMap[$siteKey]['routers'] ?? [];
        }

        foreach ($deployedRows as $row) {
            $row = $this->decorateRow((array) $row, 'deployed', $inventoryIndex);
            $siteKey = $this->siteKeyFromRow($row);
            if ($siteKey === null) {
                continue;
            }

            $siteMap[$siteKey]['site'] = $siteMap[$siteKey]['site'] ?? null;
            $siteMap[$siteKey]['routers'] = $siteMap[$siteKey]['routers'] ?? [];
            $siteMap[$siteKey]['routers'][] = $row;
        }

        return collect($siteMap)
            ->map(function (array $payload, string $siteKey) {
                $site = (array) ($payload['site'] ?? []);
                $routers = collect($payload['routers'] ?? [])->values();

                $siteSerial = trim((string) ($site['serial'] ?? ''));
                $explicitPrimary = $routers->first(fn (array $row) => (string) ($row['is_primary'] ?? '0') === '1');
                $matchedPrimary = $siteSerial !== ''
                    ? $routers->first(fn (array $row) => (string) ($row['serial_number'] ?? '') === $siteSerial)
                    : null;
                $mainRouter = $explicitPrimary ?? $matchedPrimary;

                if ($mainRouter === null && $siteSerial !== '') {
                    $mainRouter = [
                        'serial_number' => $siteSerial,
                        'brand' => 'Primary Router',
                        'model_number' => '',
                        'router_status' => trim((string) ($site['status'] ?? '')),
                        'status' => trim((string) ($site['status'] ?? '')),
                        'installed_date' => $site['reg_date'] ?? null,
                        'is_primary' => '1',
                        'site_id' => $site['site_id'] ?? null,
                        'site_name' => $site['site_name'] ?? ($site['name'] ?? null),
                        '_synthetic_main' => true,
                    ];
                }

                $chainRouters = $routers->reject(function (array $row) use ($mainRouter) {
                    if ($mainRouter === null) {
                        return false;
                    }

                    return (string) ($row['serial_number'] ?? '') === (string) ($mainRouter['serial_number'] ?? '');
                })->values();

                $siteName = trim((string) ($site['site_name'] ?? $site['name'] ?? ($routers->first()['site_name'] ?? 'Site')));
                $siteId = trim((string) ($site['site_id'] ?? ($routers->first()['site_id'] ?? '')));

                return [
                    'site_key' => $siteId !== '' ? $siteId : $siteKey,
                    'site_id' => $siteId,
                    'site_name' => $siteName !== '' ? $siteName : 'Site',
                    'router_count' => $routers->count() + ($mainRouter && ($mainRouter['_synthetic_main'] ?? false) ? 1 : 0),
                    'main_count' => $mainRouter ? 1 : 0,
                    'chain_count' => $chainRouters->count(),
                    'status' => trim((string) ($site['status'] ?? '')),
                    'serial' => $siteSerial,
                    'rx_power' => $site['rx_power'] ?? null,
                    'tx_power' => $site['tx_power'] ?? null,
                    'acs_last_inform' => $site['acs_last_inform'] ?? null,
                    'olt' => $site['olt'] ?? null,
                    'slot' => $site['slot'] ?? null,
                    'pon' => $site['pon'] ?? null,
                    'latest_activity_at' => collect([
                        $site['acs_last_inform'] ?? null,
                        $site['reg_date'] ?? null,
                        ...$routers->map(fn (array $row) => $this->rowTimestamp($row))->all(),
                    ])->filter()->sortDesc()->first(),
                    'site_row' => $site,
                    'main_router' => $mainRouter,
                    'chain_routers' => $chainRouters->all(),
                    'routers' => $routers->sort(function (array $left, array $right) use ($mainRouter) {
                        $leftMain = $mainRouter && (string) ($left['serial_number'] ?? '') === (string) ($mainRouter['serial_number'] ?? '');
                        $rightMain = $mainRouter && (string) ($right['serial_number'] ?? '') === (string) ($mainRouter['serial_number'] ?? '');
                        if ($leftMain !== $rightMain) {
                            return $leftMain ? -1 : 1;
                        }

                        return strcmp((string) ($right['installed_date'] ?? ''), (string) ($left['installed_date'] ?? ''));
                    })->values()->all(),
                ];
            })
            ->sort(function (array $left, array $right) {
                $time = strcmp((string) ($right['latest_activity_at'] ?? ''), (string) ($left['latest_activity_at'] ?? ''));
                if ($time !== 0) {
                    return $time;
                }

                return strcmp(
                    mb_strtolower((string) ($left['site_name'] ?? '')),
                    mb_strtolower((string) ($right['site_name'] ?? ''))
                );
            })
            ->values();
    }

    private function buildInventoryIndex(array $collections): array
    {
        $serials = collect($collections)
            ->flatMap(function ($rows) {
                return collect($rows)->map(fn ($row) => $this->serialFromRow((array) $row));
            })
            ->filter()
            ->unique()
            ->values();

        if ($serials->isEmpty()) {
            return [];
        }

        try {
            return ItemUnit::query()
                ->with(['item:id,name'])
                ->whereIn('serial_no', $serials->all())
                ->get()
                ->mapWithKeys(function (ItemUnit $unit) {
                    return [
                        $unit->serial_no => [
                            'unit_id' => $unit->id,
                            'item_id' => $unit->item_id,
                            'item_name' => $unit->item?->name,
                            'status' => $unit->status,
                            'assigned_to' => $unit->assigned_to,
                            'deployed_site_code' => $unit->deployed_site_code,
                            'deployed_site_name' => $unit->deployed_site_name,
                        ],
                    ];
                })
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function decorateRow(array $row, string $state, array $inventoryIndex): array
    {
        $serial = $this->serialFromRow($row);

        return $row + [
            '_state_key' => $state,
            '_state_label' => 'Deployed',
            '_serial' => $serial,
            '_inventory' => $serial !== null ? ($inventoryIndex[$serial] ?? null) : null,
        ];
    }

    private function fetchSection(SkybrixRouterApiService $routerApi, string $section, array $filters, bool $allPages): array
    {
        $params = $filters;
        $params['page'] = 1;

        if ($allPages) {
            $params['page_size'] = max(1, (int) config('inventory.router_api.max_page_size', 100));
        }

        $first = $this->callSection($routerApi, $section, $params);
        if (!$allPages || !($first['available'] ?? false)) {
            return $first;
        }

        $rows = collect($first['rows'] ?? []);
        $pageCount = max(1, (int) ($first['pagination']['page_count'] ?? 1));
        $pageLimit = max(0, (int) config('inventory.router_api.collection_page_limit', 0));
        $finalPage = $pageLimit > 0 ? min($pageCount, $pageLimit) : $pageCount;
        $messages = [];

        if ($pageLimit > 0 && $pageCount > $pageLimit) {
            $messages[] = 'Only the first ' . $pageLimit . ' pages were loaded for this grouped view.';
        }

        for ($page = 2; $page <= $finalPage; $page++) {
            $next = $this->callSection($routerApi, $section, array_merge($params, ['page' => $page]));
            if (!($next['available'] ?? false)) {
                $messages[] = (string) ($next['message'] ?? 'A Skybrix page could not be loaded.');
                break;
            }

            $rows = $rows->concat($next['rows'] ?? []);
        }

        $first['rows'] = $rows->values()->all();
        $first['pagination'] = [
            'current_page' => 1,
            'per_page' => max(1, (int) ($params['page_size'] ?? 100)),
            'total' => $rows->count(),
            'page_count' => 1,
        ];

        if ($messages !== []) {
            $first['message'] = trim(((string) ($first['message'] ?? '')) . ' ' . implode(' ', $messages));
        }

        return $first;
    }

    private function callSection(SkybrixRouterApiService $routerApi, string $section, array $filters): array
    {
        return match ($section) {
            'deployed' => $routerApi->deployedRouters($filters),
            'sites' => $routerApi->sites($filters),
            default => $routerApi->deployedRouters($filters),
        };
    }

    private function combineGroupedResults(array $results, LengthAwarePaginator $paginator): array
    {
        $messages = collect($results)
            ->pluck('message')
            ->filter(fn ($message) => is_string($message) && trim($message) !== '')
            ->unique()
            ->values();

        return [
            'available' => collect($results)->every(fn ($result) => (bool) ($result['available'] ?? false)),
            'message' => $messages->implode(' '),
            'fetched_at' => now()->toIso8601String(),
            'rows' => [],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'page_count' => $paginator->lastPage(),
            ],
            'request' => [
                'section' => null,
                'url' => null,
                'params' => [],
            ],
        ];
    }

    private function makeCollectionPaginator(Collection $items, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->integer('page', 1));
        $perPage = max(1, $perPage);
        $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => Arr::except($request->query(), 'page'),
                'pageName' => 'page',
            ]
        );
    }

    private function serialFromRow(array $row): ?string
    {
        $serial = trim((string) ($row['serial_number'] ?? $row['serial'] ?? ''));

        return $serial !== '' ? $serial : null;
    }

    private function siteKeyFromRow(array $row): ?string
    {
        $siteId = trim((string) ($row['site_id'] ?? ''));
        if ($siteId !== '') {
            return $siteId;
        }

        $siteName = trim((string) ($row['site_name'] ?? $row['name'] ?? ''));
        if ($siteName !== '') {
            return 'name:' . mb_strtolower($siteName);
        }

        return null;
    }

    private function rowTimestamp(array $row): ?string
    {
        foreach ([
            'installed_date',
            'assigned_date',
            'updated_at',
            'created_at',
            'acs_last_inform',
            'reg_date',
            'expected_deployment_date',
        ] as $field) {
            $value = trim((string) ($row[$field] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
