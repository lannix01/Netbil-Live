<?php

namespace App\Modules\PettyCash\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OntDirectoryService
{
    private const CACHE_KEY = 'pettycash:ont-directory:catalog:v1';
    private const SEARCH_CACHE_PREFIX = 'pettycash:ont-directory:search:v1';

    /**
     * @return array{
     *     available:bool,
     *     message:string,
     *     fetched_at:string|null,
     *     source_count:int,
     *     hostels:array<int,array{
     *         key:string,
     *         hostel_name:string,
     *         site_id:string|null,
     *         router_count_suggestion:int
     *     }>
     * }
     */
    public function catalog(bool $refresh = false): array
    {
        $cfg = $this->config();
        if (!$cfg['enabled']) {
            return $this->unavailable('Skybrix ONT directory is disabled.');
        }

        if (!$cfg['configured']) {
            return $this->unavailable('Skybrix ONT API credentials are not configured.');
        }

        $ttl = max(30, (int) ($cfg['cache_ttl_seconds'] ?? 300));
        if ($refresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(self::CACHE_KEY, $ttl, function () use ($cfg) {
            return $this->fetchCatalog($cfg);
        });
    }

    /**
     * Search ONT directory by site/hostel name without loading all pages.
     *
     * @return array{
     *     available:bool,
     *     message:string,
     *     fetched_at:string|null,
     *     source_count:int,
     *     hostels:array<int,array{
     *         key:string,
     *         hostel_name:string,
     *         site_id:string|null,
     *         router_count_suggestion:int
     *     }>
     * }
     */
    public function searchCatalog(?string $search = null, int $limit = 40, bool $refresh = false): array
    {
        $cfg = $this->config();
        if (!$cfg['enabled']) {
            return $this->unavailable('Skybrix ONT directory is disabled.');
        }

        if (!$cfg['configured']) {
            return $this->unavailable('Skybrix ONT API credentials are not configured.');
        }

        $query = trim((string) $search);
        if ($query === '') {
            return [
                'available' => true,
                'message' => 'Type site name to search ONT directory.',
                'fetched_at' => null,
                'source_count' => 0,
                'hostels' => [],
            ];
        }

        $limit = max(1, min(100, $limit));
        $ttl = max(15, (int) ($cfg['cache_ttl_seconds'] ?? 300));
        $cacheKey = self::SEARCH_CACHE_PREFIX . ':' . md5(strtolower($query) . '|' . $limit);
        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, $ttl, function () use ($cfg, $query, $limit) {
            return $this->fetchSearchCatalog($cfg, $query, $limit);
        });
    }

    /**
     * @return array{catalog:array,candidate:array|null}
     */
    public function findCandidate(?string $ontKey = null, ?string $hostelName = null, bool $refresh = false): array
    {
        $key = trim((string) $ontKey);
        $name = trim((string) $hostelName);
        $terms = $this->buildSearchTerms($name, $key);

        if (empty($terms)) {
            $catalog = $this->searchCatalog('', 40, $refresh);
            return ['catalog' => $catalog, 'candidate' => null];
        }

        $catalog = null;
        $hostels = [];
        $seen = [];

        foreach ($terms as $term) {
            $result = $this->searchCatalog($term, 120, $refresh);
            if (!($result['available'] ?? false)) {
                return ['catalog' => $result, 'candidate' => null];
            }

            $catalog = $result;
            foreach ((array) ($result['hostels'] ?? []) as $row) {
                $id = (string) ($row['key'] ?? '');
                if ($id === '') {
                    continue;
                }
                if (isset($seen[$id])) {
                    continue;
                }
                $seen[$id] = true;
                $hostels[] = $row;
            }
        }

        if ($catalog === null) {
            $catalog = $this->searchCatalog('', 40, $refresh);
        }
        $catalog['hostels'] = $hostels;

        $candidate = $this->findCandidateInHostels($hostels, $key, $name);
        if ($candidate !== null) {
            return ['catalog' => $catalog, 'candidate' => $candidate];
        }

        // Retry with key as direct search term if key looked too synthetic for first pass.
        if ($key !== '' && !in_array($key, $terms, true)) {
            $retry = $this->searchCatalog($key, 120, $refresh);
            if (!($retry['available'] ?? false)) {
                return ['catalog' => $retry, 'candidate' => null];
            }

            foreach ((array) ($retry['hostels'] ?? []) as $row) {
                $id = (string) ($row['key'] ?? '');
                if ($id === '' || isset($seen[$id])) {
                    continue;
                }
                $hostels[] = $row;
                $seen[$id] = true;
            }

            $catalog = $retry;
            $catalog['hostels'] = $hostels;
            $candidate = $this->findCandidateInHostels($hostels, $key, $name);
            if ($candidate !== null) {
                return ['catalog' => $catalog, 'candidate' => $candidate];
            }
        }

        return ['catalog' => $catalog, 'candidate' => null];
    }

    public function strictValidationEnabled(): bool
    {
        return (bool) config('pettycash.ont_directory.strict_validation', true);
    }

    private function config(): array
    {
        $cfg = (array) config('pettycash.ont_directory', []);

        $username = trim((string) ($cfg['username'] ?? ''));
        $password = trim((string) ($cfg['password'] ?? ''));

        return [
            'enabled' => (bool) ($cfg['enabled'] ?? true),
            'configured' => $username !== '' && $password !== '',
            'endpoint' => (string) ($cfg['endpoint'] ?? 'https://api.skybrix.co.ke/v1/onts'),
            'username' => $username,
            'password' => $password,
            'timeout_seconds' => max(3, (int) ($cfg['timeout_seconds'] ?? 12)),
            'cache_ttl_seconds' => max(30, (int) ($cfg['cache_ttl_seconds'] ?? 300)),
        ];
    }

    /**
     * @param array{endpoint:string,username:string,password:string,timeout_seconds:int} $cfg
     * @return array{available:bool,message:string,fetched_at:string|null,source_count:int,hostels:array<int,array{key:string,hostel_name:string,site_id:string|null,router_count_suggestion:int}>}
     */
    private function fetchCatalog(array $cfg): array
    {
        try {
            $first = $this->requestPage($cfg, 1);
            if (!$first['ok']) {
                return $this->unavailable((string) ($first['message'] ?? 'Skybrix ONT API request failed.'));
            }

            $rows = (array) ($first['rows'] ?? []);
            $pagination = (array) ($first['pagination'] ?? []);
            $pageCount = max(1, (int) ($pagination['page_count'] ?? 1));

            // Safety cap for unexpected upstream pagination loops.
            $pageCount = min($pageCount, 200);

            for ($page = 2; $page <= $pageCount; $page++) {
                $next = $this->requestPage($cfg, $page);
                if (!$next['ok']) {
                    return $this->unavailable(
                        'Skybrix ONT API pagination fetch failed at page ' . $page . '.'
                    );
                }

                foreach ((array) ($next['rows'] ?? []) as $row) {
                    $rows[] = $row;
                }
            }

            $hostels = $this->buildHostelCatalog($rows);

            return [
                'available' => true,
                'message' => 'ONT directory fetched.',
                'fetched_at' => now()->toIso8601String(),
                'source_count' => count($rows),
                'hostels' => $hostels,
            ];
        } catch (\Throwable $e) {
            Log::warning('Skybrix ONT fetch failed.', [
                'error' => $e->getMessage(),
            ]);

            return $this->unavailable('Skybrix ONT API is currently unreachable.');
        }
    }

    /**
     * @param array{endpoint:string,username:string,password:string,timeout_seconds:int} $cfg
     * @return array{available:bool,message:string,fetched_at:string|null,source_count:int,hostels:array<int,array{key:string,hostel_name:string,site_id:string|null,router_count_suggestion:int}>}
     */
    private function fetchSearchCatalog(array $cfg, string $query, int $limit): array
    {
        try {
            $result = $this->requestSearch($cfg, $query, $limit);
            if (!$result['ok']) {
                return $this->unavailable((string) ($result['message'] ?? 'Skybrix ONT API search request failed.'));
            }

            $rows = (array) ($result['rows'] ?? []);
            $hostels = $this->buildHostelCatalog($rows);
            if (count($hostels) > $limit) {
                $hostels = array_slice($hostels, 0, $limit);
            }

            return [
                'available' => true,
                'message' => 'ONT directory fetched.',
                'fetched_at' => now()->toIso8601String(),
                'source_count' => count($rows),
                'hostels' => $hostels,
            ];
        } catch (\Throwable $e) {
            Log::warning('Skybrix ONT search failed.', [
                'error' => $e->getMessage(),
                'query' => $query,
            ]);

            return $this->unavailable('Skybrix ONT API is currently unreachable.');
        }
    }

    /**
     * @param array{endpoint:string,username:string,password:string,timeout_seconds:int} $cfg
     * @return array{ok:bool,message:string,rows:array<int,mixed>,pagination:array<string,mixed>}
     */
    private function requestPage(array $cfg, int $page): array
    {
        $response = Http::withBasicAuth($cfg['username'], $cfg['password'])
            ->acceptJson()
            ->timeout($cfg['timeout_seconds'])
            ->get($cfg['endpoint'], ['page' => $page]);

        if (!$response->successful()) {
            return [
                'ok' => false,
                'message' => 'Skybrix ONT API returned status ' . $response->status() . '.',
                'rows' => [],
                'pagination' => [],
            ];
        }

        $payload = $response->json();
        $rows = is_array($payload) && is_array($payload['data'] ?? null)
            ? $payload['data']
            : [];
        $pagination = is_array($payload) && is_array($payload['pagination'] ?? null)
            ? $payload['pagination']
            : [];

        return [
            'ok' => true,
            'message' => '',
            'rows' => $rows,
            'pagination' => $pagination,
        ];
    }

    /**
     * @param array{endpoint:string,username:string,password:string,timeout_seconds:int} $cfg
     * @return array{ok:bool,message:string,rows:array<int,mixed>}
     */
    private function requestSearch(array $cfg, string $query, int $limit): array
    {
        $limit = max(20, min(200, $limit));

        $response = Http::withBasicAuth($cfg['username'], $cfg['password'])
            ->acceptJson()
            ->timeout($cfg['timeout_seconds'])
            ->get($cfg['endpoint'], [
                'search' => $query,
                'page' => 1,
            ]);

        if (!$response->successful()) {
            return [
                'ok' => false,
                'message' => 'Skybrix ONT API returned status ' . $response->status() . '.',
                'rows' => [],
            ];
        }

        $payload = $response->json();
        $rows = is_array($payload) && is_array($payload['data'] ?? null)
            ? $payload['data']
            : [];
        $pagination = is_array($payload) && is_array($payload['pagination'] ?? null)
            ? $payload['pagination']
            : [];
        $perPage = max(1, (int) ($pagination['per_page'] ?? count($rows) ?: 20));
        $pageCount = max(1, (int) ($pagination['page_count'] ?? 1));
        $targetPages = min($pageCount, max(1, (int) ceil($limit / $perPage)));

        for ($page = 2; $page <= $targetPages; $page++) {
            $next = Http::withBasicAuth($cfg['username'], $cfg['password'])
                ->acceptJson()
                ->timeout($cfg['timeout_seconds'])
                ->get($cfg['endpoint'], [
                    'search' => $query,
                    'page' => $page,
                ]);

            if (!$next->successful()) {
                return [
                    'ok' => false,
                    'message' => 'Skybrix ONT API returned status ' . $next->status() . '.',
                    'rows' => [],
                ];
            }

            $nextPayload = $next->json();
            $nextRows = is_array($nextPayload) && is_array($nextPayload['data'] ?? null)
                ? $nextPayload['data']
                : [];

            foreach ($nextRows as $row) {
                $rows[] = $row;
            }
        }

        return [
            'ok' => true,
            'message' => '',
            'rows' => $rows,
        ];
    }

    /**
     * Build resilient upstream search terms.
     * Skybrix often matches single tokens better than full multi-word phrases.
     *
     * @return array<int,string>
     */
    private function buildSearchTerms(string $hostelName, string $ontKey): array
    {
        $terms = [];

        $push = static function (string $term) use (&$terms): void {
            $term = trim($term);
            if ($term === '') {
                return;
            }

            $index = strtolower($term);
            if (isset($terms[$index])) {
                return;
            }

            $terms[$index] = $term;
        };

        $push($hostelName);
        $push($this->queryFromOntKey($ontKey));

        $parts = preg_split('/[^A-Za-z0-9]+/', $hostelName) ?: [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            if (!ctype_digit($part) && strlen($part) < 3) {
                continue;
            }

            $push($part);
        }

        return array_values($terms);
    }

    /**
     * @param array<int,mixed> $rows
     * @return array<int,array{key:string,hostel_name:string,site_id:string|null,router_count_suggestion:int}>
     */
    private function buildHostelCatalog(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $siteId = trim((string) ($row['site_id'] ?? ''));
            $siteName = trim((string) ($row['site_name'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            $serial = trim((string) ($row['serial'] ?? ''));
            $ontRowId = trim((string) ($row['id'] ?? ''));

            $resolvedName = $this->resolveDisplayName($siteName, $name, $serial);
            if ($resolvedName === '') {
                continue;
            }

            $groupKey = $siteId !== ''
                ? 'site:' . $siteId
                : 'name:' . md5($this->normalizeName($resolvedName));

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'key' => $groupKey,
                    'hostel_name' => $resolvedName,
                    'site_id' => ($siteId !== '' ? $siteId : null),
                    'ont_refs' => [],
                ];
            } else {
                $current = (string) ($groups[$groupKey]['hostel_name'] ?? '');
                if ($this->displayNameScore($resolvedName, $serial) > $this->displayNameScore($current, $serial)) {
                    $groups[$groupKey]['hostel_name'] = $resolvedName;
                }
            }

            $identity = $ontRowId !== ''
                ? 'id:' . $ontRowId
                : ($serial !== '' ? 'serial:' . strtoupper($serial) : 'name:' . md5($resolvedName));

            $groups[$groupKey]['ont_refs'][$identity] = true;
        }

        $catalog = [];
        foreach ($groups as $group) {
            $catalog[] = [
                'key' => (string) $group['key'],
                'hostel_name' => (string) $group['hostel_name'],
                'site_id' => $group['site_id'],
                'router_count_suggestion' => max(1, count((array) ($group['ont_refs'] ?? []))),
            ];
        }

        usort($catalog, function (array $a, array $b): int {
            return strcasecmp((string) ($a['hostel_name'] ?? ''), (string) ($b['hostel_name'] ?? ''));
        });

        return $catalog;
    }

    private function resolveDisplayName(string $siteName, string $name, string $serial): string
    {
        $siteName = $this->cleanName($siteName);
        $name = $this->cleanName($name);
        $serial = strtoupper(trim($serial));

        if ($siteName !== '' && !$this->isGenericOntName($siteName, $serial)) {
            return $siteName;
        }

        if ($name !== '' && !$this->isGenericOntName($name, $serial)) {
            return $name;
        }

        if ($siteName !== '') {
            return $siteName;
        }

        return $name;
    }

    private function cleanName(string $value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        return $value;
    }

    private function isGenericOntName(string $value, string $serial): bool
    {
        $compact = strtoupper(str_replace(' ', '', trim($value)));
        if ($compact === '') {
            return true;
        }

        if ($serial !== '' && $compact === strtoupper(str_replace(' ', '', $serial))) {
            return true;
        }

        return str_contains($compact, 'GPON');
    }

    private function displayNameScore(string $value, string $serial): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        return $this->isGenericOntName($value, $serial) ? 1 : 10;
    }

    private function normalizeName(string $value): string
    {
        $collapsed = preg_replace('/\s+/', ' ', trim($value)) ?? '';
        return strtoupper($collapsed);
    }

    /**
     * Convert generated key styles (site:123 / name:abc) to a usable search term.
     */
    private function queryFromOntKey(string $key): string
    {
        $key = trim($key);
        if ($key === '') {
            return '';
        }

        if (str_starts_with($key, 'site:')) {
            return trim(substr($key, strlen('site:')));
        }

        if (str_starts_with($key, 'name:')) {
            $value = trim(substr($key, strlen('name:')));
            // Legacy keys may contain an md5 hash, which is not searchable upstream.
            if ($value !== '' && preg_match('/^[a-f0-9]{32}$/i', $value) === 1) {
                return '';
            }

            return $value;
        }

        return $key;
    }

    /**
     * @param array<int,array<string,mixed>> $hostels
     * @return array<string,mixed>|null
     */
    private function findCandidateInHostels(array $hostels, string $key, string $name): ?array
    {
        $targetName = $this->normalizeName($name);
        $targetLoose = $this->normalizeLooseName($name);

        foreach ($hostels as $candidate) {
            $candidateKey = (string) ($candidate['key'] ?? '');
            $siteId = trim((string) ($candidate['site_id'] ?? ''));

            if ($key !== '') {
                if ($candidateKey === $key) {
                    return $candidate;
                }
                if (str_starts_with($key, 'site:') && $siteId !== '' && ('site:' . $siteId) === $key) {
                    return $candidate;
                }
            }

            if ($targetName !== '' && $this->normalizeName((string) ($candidate['hostel_name'] ?? '')) === $targetName) {
                return $candidate;
            }
        }

        if ($targetLoose === '') {
            return null;
        }

        foreach ($hostels as $candidate) {
            $candidateLoose = $this->normalizeLooseName((string) ($candidate['hostel_name'] ?? ''));
            if ($candidateLoose === '') {
                continue;
            }

            // Accept close matches to handle punctuation/spacing differences from upstream data.
            if ($candidateLoose === $targetLoose
                || str_contains($candidateLoose, $targetLoose)
                || str_contains($targetLoose, $candidateLoose)
            ) {
                return $candidate;
            }
        }

        return null;
    }

    private function normalizeLooseName(string $value): string
    {
        $value = strtoupper($value);
        $value = preg_replace('/[^A-Z0-9]+/', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';

        return $value;
    }

    /**
     * @return array{available:bool,message:string,fetched_at:null,source_count:int,hostels:array<int,array{key:string,hostel_name:string,site_id:string|null,router_count_suggestion:int}>}
     */
    private function unavailable(string $message): array
    {
        return [
            'available' => false,
            'message' => $message,
            'fetched_at' => null,
            'source_count' => 0,
            'hostels' => [],
        ];
    }
}
