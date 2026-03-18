<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\InventoryUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SkybrixTechnicianRouterAssignmentService
{
    public function __construct(
        private readonly SkybrixRouterApiService $routerApi
    ) {
    }

    public function assignedRoutersForUser(InventoryUser $user): array
    {
        $direct = $this->collectRouters([
            'technician_id' => (int) ($user->id ?? 0),
        ]);

        $directRows = collect($direct['rows'] ?? [])->map(fn ($row) => (array) $row)->values();
        if ($directRows->isNotEmpty()) {
            return $this->makeResult(
                $directRows,
                true,
                (string) ($direct['message'] ?? 'Request processed successfully.'),
                (string) ($direct['fetched_at'] ?? now()->toIso8601String()),
                'technician_id'
            );
        }

        $tokens = $this->identityTokens($user);
        $matched = collect();
        $fetchedAt = (string) ($direct['fetched_at'] ?? now()->toIso8601String());
        $available = (bool) ($direct['available'] ?? false);

        foreach ($tokens as $token) {
            $result = $this->collectRouters([
                'search' => $token,
            ]);

            $available = $available || (bool) ($result['available'] ?? false);
            $fetchedAt = (string) ($result['fetched_at'] ?? $fetchedAt);

            if (!($result['available'] ?? false)) {
                continue;
            }

            $matches = collect($result['rows'] ?? [])
                ->map(fn ($row) => (array) $row)
                ->filter(fn (array $row) => $this->rowMatchesUser($row, $user, $tokens));

            $matched = $matched->concat($matches);
        }

        $matched = $matched
            ->unique(fn (array $row) => $this->rowFingerprint($row))
            ->sort(function (array $left, array $right) {
                $time = strcmp((string) $this->rowTimestamp($right), (string) $this->rowTimestamp($left));
                if ($time !== 0) {
                    return $time;
                }

                return strcmp(
                    trim((string) ($left['serial_number'] ?? '')),
                    trim((string) ($right['serial_number'] ?? ''))
                );
            })
            ->values();

        return $this->makeResult(
            $matched,
            $available,
            $available
                ? 'Routers matched to your Skybrix technician assignment.'
                : 'Skybrix router API is currently unreachable.',
            $available ? $fetchedAt : null,
            $matched->isNotEmpty() ? 'identity' : 'none'
        );
    }

    public function filterRows(Collection $rows, string $search): Collection
    {
        $needles = collect(preg_split('/\s+/', $this->normalizeText($search)) ?: [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values();

        if ($needles->isEmpty()) {
            return $rows->values();
        }

        return $rows
            ->filter(function ($row) use ($needles) {
                $row = (array) $row;
                $haystack = $this->normalizeText(implode(' ', [
                    $row['serial_number'] ?? '',
                    $row['batch_number'] ?? '',
                    $row['brand'] ?? '',
                    $row['model_number'] ?? '',
                    $row['technician_name'] ?? '',
                    $row['technician_email'] ?? '',
                    $row['technician_phone'] ?? '',
                    $row['status'] ?? '',
                    $row['mac_address'] ?? '',
                    $row['assigned_date'] ?? '',
                    $row['expected_deployment_date'] ?? '',
                ]));

                return $needles->every(fn (string $needle) => Str::contains($haystack, $needle));
            })
            ->values();
    }

    private function collectRouters(array $filters): array
    {
        $params = [
            'page' => 1,
            'page_size' => max(1, (int) config('inventory.router_api.max_page_size', 100)),
        ] + $filters;

        $first = $this->routerApi->routersWithTechs($params);
        if (!($first['available'] ?? false)) {
            return $first;
        }

        $rows = collect($first['rows'] ?? []);
        $pageCount = max(1, (int) ($first['pagination']['page_count'] ?? 1));
        $pageLimit = max(0, (int) config('inventory.router_api.collection_page_limit', 0));
        $finalPage = $pageLimit > 0 ? min($pageCount, $pageLimit) : $pageCount;

        for ($page = 2; $page <= $finalPage; $page++) {
            $next = $this->routerApi->routersWithTechs(array_merge($params, ['page' => $page]));
            if (!($next['available'] ?? false)) {
                break;
            }

            $rows = $rows->concat($next['rows'] ?? []);
            $first['fetched_at'] = $next['fetched_at'] ?? $first['fetched_at'];
        }

        $first['rows'] = $rows->values()->all();
        $first['pagination'] = [
            'current_page' => 1,
            'per_page' => max(1, (int) ($params['page_size'] ?? 100)),
            'total' => $rows->count(),
            'page_count' => 1,
        ];

        return $first;
    }

    private function makeResult(
        Collection $rows,
        bool $available,
        string $message,
        ?string $fetchedAt,
        string $strategy
    ): array {
        return [
            'available' => $available,
            'message' => $message,
            'fetched_at' => $fetchedAt,
            'rows' => $rows->values()->all(),
            'pagination' => [
                'current_page' => 1,
                'per_page' => max(1, $rows->count() ?: 1),
                'total' => $rows->count(),
                'page_count' => 1,
            ],
            'request' => [
                'section' => 'with-techs',
                'url' => null,
                'params' => [],
            ],
            'match_strategy' => $strategy,
        ];
    }

    private function rowMatchesUser(array $row, InventoryUser $user, Collection $tokens): bool
    {
        $rowTechnicianId = (int) ($row['technician_id'] ?? 0);
        if ($rowTechnicianId > 0 && $rowTechnicianId === (int) ($user->id ?? 0)) {
            return true;
        }

        $userEmail = mb_strtolower(trim((string) ($user->email ?? '')));
        $rowEmail = mb_strtolower(trim((string) ($row['technician_email'] ?? '')));
        if ($userEmail !== '' && $rowEmail !== '' && $userEmail === $rowEmail) {
            return true;
        }

        $rowTokens = collect(preg_split('/\s+/', $this->normalizeText(implode(' ', [
            $row['technician_name'] ?? '',
            $row['technician_email'] ?? '',
            $row['technician_phone'] ?? '',
        ]))) ?: [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values();

        if ($rowTokens->isEmpty() || $tokens->isEmpty()) {
            return false;
        }

        $overlap = $tokens->intersect($rowTokens)->values();
        if ($overlap->isEmpty()) {
            return false;
        }

        $primary = (string) ($tokens->first() ?? '');
        if ($primary !== '' && $overlap->contains($primary)) {
            return true;
        }

        return $overlap->count() >= min(2, $tokens->count());
    }

    private function identityTokens(InventoryUser $user): Collection
    {
        $stopWords = [
            'admin',
            'field',
            'inventory',
            'router',
            'routers',
            'store',
            'technician',
            'tech',
            'user',
            'staff',
            'team',
        ];

        $sources = [
            trim((string) ($user->name ?? '')),
            trim((string) Str::before((string) ($user->email ?? ''), '@')),
            trim((string) ($user->email ?? '')),
        ];

        return collect($sources)
            ->flatMap(function (string $value) {
                return preg_split('/[^a-z0-9]+/i', $this->normalizeText($value)) ?: [];
            })
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn (string $value) => $value !== '' && !in_array($value, $stopWords, true) && mb_strlen($value) >= 3)
            ->unique()
            ->values();
    }

    private function normalizeText(string $value): string
    {
        return (string) Str::of(Str::ascii($value))
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish();
    }

    private function rowFingerprint(array $row): string
    {
        return implode('|', [
            trim((string) ($row['serial_number'] ?? $row['serial'] ?? '')),
            trim((string) ($row['assigned_date'] ?? '')),
            trim((string) ($row['technician_id'] ?? '')),
        ]);
    }

    private function rowTimestamp(array $row): ?string
    {
        foreach ([
            'assigned_date',
            'expected_deployment_date',
            'updated_at',
            'created_at',
        ] as $field) {
            $value = trim((string) ($row[$field] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
