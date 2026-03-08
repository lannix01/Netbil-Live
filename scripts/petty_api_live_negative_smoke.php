<?php

declare(strict_types=1);

use App\Modules\PettyCash\Models\Bike;
use App\Modules\PettyCash\Models\BikeService;
use App\Modules\PettyCash\Models\Hostel;
use App\Modules\PettyCash\Models\Payment;
use App\Modules\PettyCash\Models\PettyApiToken;
use App\Modules\PettyCash\Models\PettyUser;
use App\Modules\PettyCash\Models\Respondent;
use App\Modules\PettyCash\Models\Spending;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

function statusAllowed(int $status, array $allowed): bool
{
    foreach ($allowed as $x) {
        if ($status === (int) $x) {
            return true;
        }
    }
    return false;
}

$suffix = now()->format('YmdHis') . '-' . strtolower(substr(Str::random(6), 0, 6));
$today = now()->toDateString();

$created = [
    'temp_admin_user_id' => null,
    'temp_viewer_user_id' => null,
    'admin_token_id' => null,
    'admin_extra_token_id' => null,
    'admin_refresh_token_id' => null,
    'admin_refresh_rotated_token_id' => null,
    'admin_current_logout_token_id' => null,
    'viewer_token_id' => null,
    'temp_hostel_id' => null,
    'temp_legacy_payment_id' => null,
    'temp_token_spending_id' => null,
    'temp_bike_id' => null,
    'temp_bike_service_id' => null,
];

$admin = PettyUser::query()->create([
    'name' => 'API Temp Admin ' . strtoupper(substr($suffix, 0, 6)),
    'email' => 'api-temp-admin-' . $suffix . '@example.com',
    'password' => Hash::make(Str::random(24)),
    'role' => 'admin',
    'is_active' => true,
]);
$created['temp_admin_user_id'] = $admin->id;

$viewer = PettyUser::query()->create([
    'name' => 'API Temp Viewer ' . strtoupper(substr($suffix, 0, 6)),
    'email' => 'api-temp-viewer-' . $suffix . '@example.com',
    'password' => Hash::make(Str::random(24)),
    'role' => 'viewer',
    'is_active' => true,
]);
$created['temp_viewer_user_id'] = $viewer->id;

$adminPlainToken = Str::random(80);
$adminToken = PettyApiToken::query()->create([
    'petty_user_id' => $admin->id,
    'name' => 'live-negative-admin',
    'token' => hash('sha256', $adminPlainToken),
    'last_used_at' => now(),
    'expires_at' => now()->addHour(),
]);
$created['admin_token_id'] = $adminToken->id;

$adminExtraPlainToken = Str::random(80);
$adminExtraToken = PettyApiToken::query()->create([
    'petty_user_id' => $admin->id,
    'name' => 'live-negative-admin-extra',
    'token' => hash('sha256', $adminExtraPlainToken),
    'last_used_at' => now(),
    'expires_at' => now()->addHour(),
]);
$created['admin_extra_token_id'] = $adminExtraToken->id;

$adminRefreshPlainToken = Str::random(80);
$adminRefreshToken = PettyApiToken::query()->create([
    'petty_user_id' => $admin->id,
    'name' => 'live-negative-admin-refresh',
    'token' => hash('sha256', $adminRefreshPlainToken),
    'last_used_at' => now(),
    'expires_at' => now()->addHour(),
]);
$created['admin_refresh_token_id'] = $adminRefreshToken->id;

$adminCurrentLogoutPlainToken = Str::random(80);
$adminCurrentLogoutToken = PettyApiToken::query()->create([
    'petty_user_id' => $admin->id,
    'name' => 'live-negative-admin-current',
    'token' => hash('sha256', $adminCurrentLogoutPlainToken),
    'last_used_at' => now(),
    'expires_at' => now()->addHour(),
]);
$created['admin_current_logout_token_id'] = $adminCurrentLogoutToken->id;

$viewerPlainToken = Str::random(80);
$viewerToken = PettyApiToken::query()->create([
    'petty_user_id' => $viewer->id,
    'name' => 'live-negative-viewer',
    'token' => hash('sha256', $viewerPlainToken),
    'last_used_at' => now(),
    'expires_at' => now()->addHour(),
]);
$created['viewer_token_id'] = $viewerToken->id;

$headersAdmin = [
    'Accept' => 'application/json',
    'Authorization' => 'Bearer ' . $adminPlainToken,
];
$headersViewer = [
    'Accept' => 'application/json',
    'Authorization' => 'Bearer ' . $viewerPlainToken,
];
$headersRefresh = [
    'Accept' => 'application/json',
    'Authorization' => 'Bearer ' . $adminRefreshPlainToken,
];
$headersCurrentLogout = [
    'Accept' => 'application/json',
    'Authorization' => 'Bearer ' . $adminCurrentLogoutPlainToken,
];
$headersNoAuth = [
    'Accept' => 'application/json',
];

$tempHostel = Hostel::query()->create([
    'hostel_name' => 'NEG HOSTEL ' . strtoupper(substr($suffix, 0, 8)),
    'meter_no' => 'NEG-MTR-' . strtoupper(substr($suffix, 0, 8)),
    'phone_no' => '0700' . random_int(100000, 999999),
    'no_of_routers' => 1,
    'stake' => 'monthly',
    'amount_due' => 100,
]);
$created['temp_hostel_id'] = $tempHostel->id;

$legacyPayment = Payment::query()->create([
    'spending_id' => null,
    'hostel_id' => $tempHostel->id,
    'batch_id' => null,
    'reference' => 'NEG-PAY-' . $suffix,
    'amount' => 2.00,
    'transaction_cost' => 0.10,
    'date' => $today,
    'receiver_name' => 'Negative Test',
    'receiver_phone' => '0700' . random_int(100000, 999999),
    'notes' => 'Legacy payment for 409 validation',
    'recorded_by' => $admin->id,
]);
$created['temp_legacy_payment_id'] = $legacyPayment->id;

$tokenSpending = Spending::query()->create([
    'batch_id' => null,
    'type' => 'token',
    'sub_type' => 'hostel',
    'reference' => 'NEG-SP-' . $suffix,
    'amount' => 1.00,
    'transaction_cost' => 0.00,
    'date' => $today,
    'description' => 'Negative token spending',
    'related_id' => $tempHostel->id,
]);
$created['temp_token_spending_id'] = $tokenSpending->id;

$tempBike = Bike::query()->create([
    'plate_no' => 'NEG-' . strtoupper(substr($suffix, 0, 10)),
    'model' => 'Negative Bike',
    'status' => 'active',
]);
$created['temp_bike_id'] = $tempBike->id;

$tempBikeService = BikeService::query()->create([
    'bike_id' => $tempBike->id,
    'service_date' => $today,
    'reference' => 'NEG-SVC-' . $suffix,
    'amount' => 1.00,
    'transaction_cost' => 0.05,
    'work_done' => 'Negative service',
    'next_due_date' => now()->addDays(30)->toDateString(),
    'recorded_by' => $admin->id,
]);
$created['temp_bike_service_id'] = $tempBikeService->id;

$results = [];
$step = 0;

$run = function (
    string $name,
    string $method,
    string $path,
    array $expectedStatuses,
    array $headers,
    array $query = [],
    array $payload = []
) use (&$results, &$step, $httpKernel): array {
    $step++;
    $requestId = 'neg-smoke-' . str_pad((string) $step, 2, '0', STR_PAD_LEFT);
    $h = $headers;
    $h['X-Request-Id'] = $requestId;

    $res = callJson($httpKernel, $method, $path, $h, $query, $payload);

    $issues = [];
    if (!statusAllowed((int) $res['status'], $expectedStatuses)) {
        $issues[] = 'Expected HTTP ' . implode('|', $expectedStatuses) . ', got ' . $res['status'];
    }

    if (!is_array($res['json'])) {
        $issues[] = 'Response is not valid JSON';
    } else {
        $isExpectedError = true;
        foreach ($expectedStatuses as $x) {
            if ((int) $x >= 200 && (int) $x < 300) {
                $isExpectedError = false;
                break;
            }
        }

        $successFlag = $res['json']['success'] ?? null;
        if ($isExpectedError && $successFlag !== false) {
            $issues[] = 'JSON success flag is not false for error response';
        }
        if (!$isExpectedError && $successFlag !== true) {
            $issues[] = 'JSON success flag is not true for success response';
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
        }
    }

    $apiVersion = (string) $res['headers']->get('X-Petty-Api-Version', '');
    if ($apiVersion === '') {
        $issues[] = 'Missing X-Petty-Api-Version header';
    }
    $echoRequestId = (string) $res['headers']->get('X-Request-Id', '');
    if ($echoRequestId !== $requestId) {
        $issues[] = 'X-Request-Id mismatch';
    }

    $results[] = [
        'name' => $name,
        'status' => $res['status'],
        'ok' => empty($issues),
        'issues' => $issues,
        'message' => is_array($res['json']) ? (string) ($res['json']['message'] ?? '') : '',
    ];

    return [
        'ok' => empty($issues),
        'response' => $res,
    ];
};

try {
    $run('auth.me.no_token', 'GET', '/api/petty/v1/auth/me', [401], $headersNoAuth);
    $run('auth.me.invalid_token', 'GET', '/api/petty/v1/auth/me', [401], [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer invalid-token',
    ]);
    $run('auth.login.validation', 'POST', '/api/petty/v1/auth/login', [422], $headersNoAuth, [], [
        'email' => 'invalid-email-format',
    ]);
    $run('auth.tokens.index', 'GET', '/api/petty/v1/auth/tokens', [200], $headersAdmin);
    $run('auth.tokens.revoke_single', 'DELETE', '/api/petty/v1/auth/tokens/' . $adminExtraToken->id, [200], $headersAdmin);

    $refreshRes = $run('auth.refresh.rotate', 'POST', '/api/petty/v1/auth/refresh', [200], $headersRefresh, [], [
        'device_name' => 'negative-refresh-device',
        'device_platform' => 'android',
    ]);

    $newAccessToken = is_array($refreshRes['response']['json'] ?? null)
        ? (string) ($refreshRes['response']['json']['data']['access_token'] ?? '')
        : '';
    if ($newAccessToken !== '') {
        $headersRefreshed = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $newAccessToken,
        ];

        $run('auth.refresh.old_token_invalid', 'GET', '/api/petty/v1/auth/me', [401], $headersRefresh);
        $run('auth.refresh.new_token_valid', 'GET', '/api/petty/v1/auth/me', [200], $headersRefreshed);

        $rotated = PettyApiToken::query()
            ->where('petty_user_id', $admin->id)
            ->where('token', hash('sha256', $newAccessToken))
            ->first();
        if ($rotated) {
            $created['admin_refresh_rotated_token_id'] = $rotated->id;
        }
    } else {
        $results[] = [
            'name' => 'auth.refresh.new_token_valid',
            'status' => 0,
            'ok' => false,
            'issues' => ['Refresh response missing data.access_token'],
            'message' => '',
        ];
    }

    $run('auth.tokens.current_logout', 'DELETE', '/api/petty/v1/auth/tokens/current', [200], $headersCurrentLogout);
    $run('auth.tokens.current_logout.invalidated', 'GET', '/api/petty/v1/auth/me', [401], $headersCurrentLogout);

    $run('auth.logout_all.others', 'POST', '/api/petty/v1/auth/logout-all', [200], $headersAdmin, [], [
        'include_current' => false,
    ]);
    $run('auth.me.after_logout_all_others', 'GET', '/api/petty/v1/auth/me', [200], $headersAdmin);

    $run('viewer.forbidden.respondent_create', 'POST', '/api/petty/v1/masters/respondents', [403], $headersViewer, [], [
        'name' => 'Should Fail',
        'phone' => '0700111222',
        'category' => 'forbidden',
    ]);

    $run('viewer.forbidden.hostel_delete', 'DELETE', '/api/petty/v1/tokens/hostels/' . $tempHostel->id, [403], $headersViewer);

    $run('spending.validation.single_without_batch', 'POST', '/api/petty/v1/spendings', [422], $headersAdmin, [], [
        'funding' => 'single',
        'type' => 'other',
        'amount' => 5.00,
        'transaction_cost' => 0.10,
        'date' => $today,
        'description' => 'Negative validation test',
    ]);

    $run('token.payment.update.legacy_conflict', 'PATCH', '/api/petty/v1/tokens/payments/' . $legacyPayment->id, [409], $headersAdmin, [], []);
    $run('hostel.delete.with_transactions_conflict', 'DELETE', '/api/petty/v1/tokens/hostels/' . $tempHostel->id, [409], $headersAdmin);
    $run('spending.show.token_conflict', 'GET', '/api/petty/v1/spendings/' . $tokenSpending->id, [409], $headersAdmin);
    $run('bike.delete.with_service_conflict', 'DELETE', '/api/petty/v1/masters/bikes/' . $tempBike->id, [409], $headersAdmin);
} finally {
    try {
        if (!empty($created['temp_legacy_payment_id'])) {
            Payment::query()->where('id', $created['temp_legacy_payment_id'])->delete();
        }
    } catch (\Throwable $e) {
    }

    try {
        if (!empty($created['temp_token_spending_id'])) {
            Spending::query()->where('id', $created['temp_token_spending_id'])->delete();
        }
    } catch (\Throwable $e) {
    }

    try {
        if (!empty($created['temp_bike_service_id'])) {
            BikeService::query()->where('id', $created['temp_bike_service_id'])->delete();
        }
    } catch (\Throwable $e) {
    }

    try {
        if (!empty($created['temp_bike_id'])) {
            Bike::query()->where('id', $created['temp_bike_id'])->delete();
        }
    } catch (\Throwable $e) {
    }

    try {
        if (!empty($created['temp_hostel_id'])) {
            Hostel::query()->where('id', $created['temp_hostel_id'])->delete();
        }
    } catch (\Throwable $e) {
    }

    try {
        if (!empty($created['admin_token_id'])) {
            PettyApiToken::query()->where('id', $created['admin_token_id'])->delete();
        }
        if (!empty($created['admin_extra_token_id'])) {
            PettyApiToken::query()->where('id', $created['admin_extra_token_id'])->delete();
        }
        if (!empty($created['admin_refresh_token_id'])) {
            PettyApiToken::query()->where('id', $created['admin_refresh_token_id'])->delete();
        }
        if (!empty($created['admin_refresh_rotated_token_id'])) {
            PettyApiToken::query()->where('id', $created['admin_refresh_rotated_token_id'])->delete();
        }
        if (!empty($created['admin_current_logout_token_id'])) {
            PettyApiToken::query()->where('id', $created['admin_current_logout_token_id'])->delete();
        }
        if (!empty($created['viewer_token_id'])) {
            PettyApiToken::query()->where('id', $created['viewer_token_id'])->delete();
        }
    } catch (\Throwable $e) {
    }

    try {
        if (!empty($created['temp_admin_user_id'])) {
            PettyUser::query()->where('id', $created['temp_admin_user_id'])->delete();
        }
        if (!empty($created['temp_viewer_user_id'])) {
            PettyUser::query()->where('id', $created['temp_viewer_user_id'])->delete();
        }
    } catch (\Throwable $e) {
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
