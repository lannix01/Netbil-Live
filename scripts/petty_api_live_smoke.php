<?php

declare(strict_types=1);

use App\Modules\PettyCash\Models\Bike;
use App\Modules\PettyCash\Models\Hostel;
use App\Modules\PettyCash\Models\PettyApiToken;
use App\Modules\PettyCash\Models\PettyUser;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

/** @var Kernel $httpKernel */
$httpKernel = $app->make(Kernel::class);

function makeServerHeaders(array $headers): array
{
    $server = [];
    foreach ($headers as $key => $value) {
        $normalized = strtoupper(str_replace('-', '_', $key));
        if (!in_array($normalized, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
            $normalized = 'HTTP_' . $normalized;
        }
        $server[$normalized] = $value;
    }
    return $server;
}

function callJson(
    Kernel $httpKernel,
    string $method,
    string $path,
    array $headers = [],
    array $query = [],
    array $payload = []
): array {
    $uri = $path;
    if (!empty($query)) {
        $uri .= '?' . http_build_query($query);
    }

    $content = null;
    $params = $payload;
    if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        $content = json_encode($payload);
        $params = [];
        $headers['Content-Type'] = 'application/json';
    }

    $request = Request::create(
        $uri,
        strtoupper($method),
        $params,
        [],
        [],
        makeServerHeaders($headers),
        $content
    );

    $response = $httpKernel->handle($request);
    $raw = (string) $response->getContent();
    $json = json_decode($raw, true);
    $httpKernel->terminate($request, $response);

    return [
        'status' => $response->getStatusCode(),
        'headers' => $response->headers,
        'raw' => $raw,
        'json' => is_array($json) ? $json : null,
    ];
}

$user = PettyUser::query()
    ->where('is_active', true)
    ->orderByRaw("FIELD(role, 'admin', 'accountant', 'viewer')")
    ->orderBy('id')
    ->first();

if (!$user) {
    fwrite(STDERR, "No active petty user found. Cannot run live API smoke tests.\n");
    exit(2);
}

$plainToken = Str::random(80);
$token = PettyApiToken::query()->create([
    'petty_user_id' => $user->id,
    'name' => 'live-smoke',
    'token' => hash('sha256', $plainToken),
    'last_used_at' => now(),
    'expires_at' => now()->addHour(),
]);

$authHeaders = [
    'Accept' => 'application/json',
    'Authorization' => 'Bearer ' . $plainToken,
];

$hostelId = (int) (Hostel::query()->orderBy('id')->value('id') ?? 0);
$bikeId = (int) (Bike::query()->orderBy('id')->value('id') ?? 0);

$tests = [
    ['name' => 'auth.me', 'method' => 'GET', 'path' => '/api/petty/v1/auth/me', 'query' => []],
    ['name' => 'auth.tokens', 'method' => 'GET', 'path' => '/api/petty/v1/auth/tokens', 'query' => []],
    ['name' => 'batches.available', 'method' => 'GET', 'path' => '/api/petty/v1/batches/available', 'query' => []],
    ['name' => 'masters.bikes', 'method' => 'GET', 'path' => '/api/petty/v1/masters/bikes', 'query' => ['per_page' => 15], 'pagination' => 'single'],
    ['name' => 'masters.respondents', 'method' => 'GET', 'path' => '/api/petty/v1/masters/respondents', 'query' => ['per_page' => 15], 'pagination' => 'single'],
    ['name' => 'credits.index', 'method' => 'GET', 'path' => '/api/petty/v1/credits', 'query' => ['per_page' => 15], 'pagination' => 'single'],
    ['name' => 'spendings.index', 'method' => 'GET', 'path' => '/api/petty/v1/spendings', 'query' => ['per_page' => 15], 'pagination' => 'single'],
    ['name' => 'maint.schedule', 'method' => 'GET', 'path' => '/api/petty/v1/maintenances/schedule', 'query' => ['per_page' => 15], 'pagination' => 'single'],
    ['name' => 'maint.history', 'method' => 'GET', 'path' => '/api/petty/v1/maintenances/history', 'query' => ['per_page' => 15], 'pagination' => 'single'],
    ['name' => 'maint.unroadworthy', 'method' => 'GET', 'path' => '/api/petty/v1/maintenances/unroadworthy', 'query' => ['per_page' => 15], 'pagination' => 'single'],
    ['name' => 'insights.dashboard', 'method' => 'GET', 'path' => '/api/petty/v1/insights/dashboard', 'query' => []],
    ['name' => 'insights.ledger', 'method' => 'GET', 'path' => '/api/petty/v1/insights/ledger', 'query' => ['per_page' => 15], 'pagination' => 'single'],
    ['name' => 'reports.lookups', 'method' => 'GET', 'path' => '/api/petty/v1/reports/lookups', 'query' => ['batch_limit' => 50]],
    ['name' => 'reports.general', 'method' => 'GET', 'path' => '/api/petty/v1/reports/general', 'query' => ['include_rows' => 0]],
    ['name' => 'tokens.hostels.index', 'method' => 'GET', 'path' => '/api/petty/v1/tokens/hostels', 'query' => ['per_page' => 15], 'pagination' => 'single'],
];

if ($hostelId > 0) {
    $tests[] = [
        'name' => 'tokens.hostels.show',
        'method' => 'GET',
        'path' => '/api/petty/v1/tokens/hostels/' . $hostelId,
        'query' => ['payments_per_page' => 10],
        'pagination' => 'single',
    ];
}

if ($bikeId > 0) {
    $tests[] = [
        'name' => 'maint.bikes.show',
        'method' => 'GET',
        'path' => '/api/petty/v1/maintenances/bikes/' . $bikeId,
        'query' => ['services_per_page' => 10, 'maintenances_per_page' => 10],
        'pagination' => 'multi',
    ];
}

$results = [];

try {
    foreach ($tests as $i => $test) {
        $requestId = 'live-smoke-' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
        $headers = $authHeaders;
        $headers['X-Request-Id'] = $requestId;

        $res = callJson(
            $httpKernel,
            $test['method'],
            $test['path'],
            $headers,
            $test['query'] ?? [],
            $test['payload'] ?? []
        );

        $issues = [];
        if ((int) $res['status'] !== 200) {
            $issues[] = 'Expected HTTP 200, got ' . $res['status'];
        }

        if (!is_array($res['json'])) {
            $issues[] = 'Response is not valid JSON';
        } else {
            if (($res['json']['success'] ?? null) !== true) {
                $issues[] = 'JSON success flag is not true';
            }

            if (!is_array($res['json']['data'] ?? null)) {
                $issues[] = 'JSON data is missing or not an object';
            }

            $meta = $res['json']['meta'] ?? null;
            if (!is_array($meta)) {
                $issues[] = 'JSON meta is missing or not an object';
            } else {
                if (($meta['request_id'] ?? '') !== $requestId) {
                    $issues[] = 'meta.request_id mismatch';
                }
                if (($meta['api_version'] ?? '') === '') {
                    $issues[] = 'meta.api_version missing';
                }
                if (($meta['timestamp'] ?? '') === '') {
                    $issues[] = 'meta.timestamp missing';
                }

                $paginationMode = (string) ($test['pagination'] ?? '');
                if ($paginationMode === 'single') {
                    if (!is_array($meta['pagination'] ?? null)) {
                        $issues[] = 'meta.pagination missing';
                    } else {
                        $required = ['current_page', 'last_page', 'per_page', 'total', 'from', 'to', 'has_more_pages'];
                        foreach ($required as $field) {
                            if (!array_key_exists($field, $meta['pagination'])) {
                                $issues[] = 'meta.pagination.' . $field . ' missing';
                            }
                        }
                    }
                } elseif ($paginationMode === 'multi') {
                    if (!is_array($meta['pagination'] ?? null)) {
                        $issues[] = 'meta.pagination missing';
                    } else {
                        foreach (['services', 'maintenances'] as $bucket) {
                            if (!is_array($meta['pagination'][$bucket] ?? null)) {
                                $issues[] = 'meta.pagination.' . $bucket . ' missing';
                                continue;
                            }
                            $required = ['current_page', 'last_page', 'per_page', 'total', 'from', 'to', 'has_more_pages'];
                            foreach ($required as $field) {
                                if (!array_key_exists($field, $meta['pagination'][$bucket])) {
                                    $issues[] = 'meta.pagination.' . $bucket . '.' . $field . ' missing';
                                }
                            }
                        }
                    }
                }
            }
        }

        $versionHeader = (string) $res['headers']->get('X-Petty-Api-Version', '');
        if ($versionHeader === '') {
            $issues[] = 'Missing X-Petty-Api-Version header';
        }

        $returnedRequestId = (string) $res['headers']->get('X-Request-Id', '');
        if ($returnedRequestId !== $requestId) {
            $issues[] = 'X-Request-Id mismatch';
        }

        $results[] = [
            'name' => $test['name'],
            'status' => $res['status'],
            'ok' => empty($issues),
            'issues' => $issues,
            'message' => is_array($res['json']) ? (string) ($res['json']['message'] ?? '') : '',
        ];
    }
} finally {
    if ($token->exists) {
        $token->delete();
    }
}

$passCount = 0;
foreach ($results as $row) {
    $mark = $row['ok'] ? 'PASS' : 'FAIL';
    echo '[' . $mark . '] ' . $row['name'] . ' (HTTP ' . $row['status'] . ')' . PHP_EOL;
    if (!$row['ok']) {
        foreach ($row['issues'] as $issue) {
            echo '  - ' . $issue . PHP_EOL;
        }
    }
    if ($row['message'] !== '') {
        echo '  message: ' . $row['message'] . PHP_EOL;
    }
    if ($row['ok']) {
        $passCount++;
    }
}

$total = count($results);
echo PHP_EOL . 'Summary: ' . $passCount . '/' . $total . ' passed.' . PHP_EOL;

exit($passCount === $total ? 0 : 1);
