<?php

declare(strict_types=1);

use App\Modules\PettyCash\Models\Batch;
use App\Modules\PettyCash\Models\Bike;
use App\Modules\PettyCash\Models\BikeService;
use App\Modules\PettyCash\Models\Credit;
use App\Modules\PettyCash\Models\Hostel;
use App\Modules\PettyCash\Models\Payment;
use App\Modules\PettyCash\Models\PettyApiToken;
use App\Modules\PettyCash\Models\PettyUser;
use App\Modules\PettyCash\Models\Respondent;
use App\Modules\PettyCash\Models\Spending;
use App\Modules\PettyCash\Models\SpendingAllocation;
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

function isOkStatus(int $status, array $expected): bool
{
    foreach ($expected as $allowed) {
        if ($status === (int) $allowed) {
            return true;
        }
    }
    return false;
}

$admin = PettyUser::query()
    ->where('is_active', true)
    ->where('role', 'admin')
    ->orderBy('id')
    ->first();

$accountant = PettyUser::query()
    ->where('is_active', true)
    ->where('role', 'accountant')
    ->orderBy('id')
    ->first();

$writer = $admin ?: $accountant;
if (!$writer) {
    fwrite(STDERR, "No active petty admin/accountant found. Cannot run write smoke tests.\n");
    exit(2);
}

$isAdmin = $writer->role === 'admin';
$suffix = now()->format('YmdHis') . '-' . strtolower(substr(Str::random(6), 0, 6));
$today = now()->toDateString();

$created = [
    'respondent_id' => null,
    'bike_id' => null,
    'service_id' => null,
    'credit_id' => null,
    'batch_id' => null,
    'other_spending_id' => null,
    'hostel_id' => null,
    'token_payment_id' => null,
    'token_spending_id' => null,
];

$plainToken = Str::random(80);
$token = PettyApiToken::query()->create([
    'petty_user_id' => $writer->id,
    'name' => 'live-write-smoke',
    'token' => hash('sha256', $plainToken),
    'last_used_at' => now(),
    'expires_at' => now()->addHour(),
]);

$authHeaders = [
    'Accept' => 'application/json',
    'Authorization' => 'Bearer ' . $plainToken,
];

$results = [];
$stepIndex = 0;

$run = function (
    string $name,
    string $method,
    string $path,
    array $expectedStatuses,
    array $payload = [],
    array $query = []
) use (&$stepIndex, $httpKernel, $authHeaders, &$results): array {
    $stepIndex++;
    $requestId = 'live-write-' . str_pad((string) $stepIndex, 2, '0', STR_PAD_LEFT);
    $headers = $authHeaders;
    $headers['X-Request-Id'] = $requestId;

    $res = callJson($httpKernel, $method, $path, $headers, $query, $payload);

    $issues = [];
    if (!isOkStatus((int) $res['status'], $expectedStatuses)) {
        $issues[] = 'Expected HTTP ' . implode('|', $expectedStatuses) . ', got ' . $res['status'];
    }
    if (!is_array($res['json'])) {
        $issues[] = 'Response is not valid JSON';
    } else {
        if ((int) $res['status'] >= 200 && (int) $res['status'] < 300) {
            if (($res['json']['success'] ?? null) !== true) {
                $issues[] = 'JSON success flag is not true for success response';
            }
            if (!is_array($res['json']['data'] ?? null)) {
                $issues[] = 'JSON data is missing or not an object';
            }
        } else {
            if (($res['json']['success'] ?? null) !== false) {
                $issues[] = 'JSON success flag is not false for non-success response';
            }
            if (!is_array($res['json']['errors'] ?? null)) {
                $issues[] = 'JSON errors is missing or not an object';
            }
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

    $versionHeader = (string) $res['headers']->get('X-Petty-Api-Version', '');
    if ($versionHeader === '') {
        $issues[] = 'Missing X-Petty-Api-Version header';
    }
    $returnedRequestId = (string) $res['headers']->get('X-Request-Id', '');
    if ($returnedRequestId !== $requestId) {
        $issues[] = 'X-Request-Id mismatch';
    }

    $ok = empty($issues);
    $results[] = [
        'name' => $name,
        'status' => $res['status'],
        'ok' => $ok,
        'issues' => $issues,
        'message' => is_array($res['json']) ? (string) ($res['json']['message'] ?? '') : '',
    ];

    return [
        'ok' => $ok,
        'response' => $res,
    ];
};

$hardFailure = false;

try {
    $me = $run('auth.me', 'GET', '/api/petty/v1/auth/me', [200]);
    if (!$me['ok']) {
        $hardFailure = true;
    }

    if (!$hardFailure) {
        $createRespondent = $run('respondent.create', 'POST', '/api/petty/v1/masters/respondents', [201], [
            'name' => 'API Live ' . $suffix,
            'phone' => '0700' . random_int(100000, 999999),
            'category' => 'api-smoke',
        ]);
        if (!$createRespondent['ok']) {
            $hardFailure = true;
        } else {
            $created['respondent_id'] = $createRespondent['response']['json']['data']['respondent']['id'] ?? null;
        }
    }

    if (!$hardFailure && $created['respondent_id']) {
        $run('respondent.update', 'PATCH', '/api/petty/v1/masters/respondents/' . $created['respondent_id'], [200], [
            'phone' => '0799' . random_int(100000, 999999),
            'category' => 'api-smoke-updated',
        ]);
    }

    if (!$hardFailure) {
        $createBike = $run('bike.create', 'POST', '/api/petty/v1/masters/bikes', [201], [
            'plate_no' => 'API-' . strtoupper(substr($suffix, 0, 14)),
            'model' => 'Smoke Runner',
            'status' => 'active',
        ]);
        if (!$createBike['ok']) {
            $hardFailure = true;
        } else {
            $created['bike_id'] = $createBike['response']['json']['data']['bike']['id'] ?? null;
        }
    }

    if (!$hardFailure && $created['bike_id']) {
        $run('bike.update', 'PATCH', '/api/petty/v1/masters/bikes/' . $created['bike_id'], [200], [
            'status' => 'service',
            'is_unroadworthy' => true,
            'unroadworthy_notes' => 'API smoke test',
        ]);

        $createService = $run('service.create', 'POST', '/api/petty/v1/maintenances/bikes/' . $created['bike_id'] . '/services', [201], [
            'service_date' => $today,
            'next_due_date' => now()->addDays(30)->toDateString(),
            'reference' => 'SRV-' . $suffix,
            'work_done' => 'API smoke service',
            'amount' => 2.50,
            'transaction_cost' => 0.20,
        ]);
        if ($createService['ok']) {
            $created['service_id'] = $createService['response']['json']['data']['service']['id'] ?? null;
        }

        if ($created['service_id']) {
            $run('service.update', 'PATCH', '/api/petty/v1/maintenances/services/' . $created['service_id'], [200], [
                'work_done' => 'API smoke service updated',
                'amount' => 2.80,
                'transaction_cost' => 0.25,
            ]);
        }

        $run('bike.unroadworthy.update', 'POST', '/api/petty/v1/maintenances/bikes/' . $created['bike_id'] . '/unroadworthy', [200], [
            'is_unroadworthy' => false,
            'unroadworthy_notes' => null,
        ]);
    }

    if (!$hardFailure) {
        $createCredit = $run('credit.create', 'POST', '/api/petty/v1/credits', [201], [
            'reference' => 'CR-' . $suffix,
            'amount' => 150.00,
            'transaction_cost' => 1.00,
            'date' => $today,
            'description' => 'API smoke credit',
        ]);
        if (!$createCredit['ok']) {
            $hardFailure = true;
        } else {
            $created['credit_id'] = $createCredit['response']['json']['data']['credit']['id'] ?? null;
            $created['batch_id'] = $createCredit['response']['json']['data']['credit']['batch_id'] ?? null;
        }
    }

    if (!$hardFailure && $created['credit_id']) {
        $run('credit.update', 'PATCH', '/api/petty/v1/credits/' . $created['credit_id'], [200], [
            'description' => 'API smoke credit updated',
            'amount' => 151.00,
            'transaction_cost' => 1.10,
            'date' => $today,
        ]);
    }

    if (!$hardFailure && $created['batch_id']) {
        $createSpending = $run('spending.other.create', 'POST', '/api/petty/v1/spendings', [201], [
            'funding' => 'single',
            'batch_id' => $created['batch_id'],
            'type' => 'other',
            'reference' => 'SP-' . $suffix,
            'amount' => 5.00,
            'transaction_cost' => 0.10,
            'date' => $today,
            'respondent_id' => $created['respondent_id'],
            'description' => 'API smoke other spending',
        ]);
        if ($createSpending['ok']) {
            $created['other_spending_id'] = $createSpending['response']['json']['data']['spending']['id'] ?? null;
        }

        if ($created['other_spending_id']) {
            $run('spending.other.update', 'PATCH', '/api/petty/v1/spendings/' . $created['other_spending_id'], [200], [
                'funding' => 'single',
                'batch_id' => $created['batch_id'],
                'amount' => 5.50,
                'transaction_cost' => 0.20,
                'description' => 'API smoke other spending updated',
                'date' => $today,
            ]);
        }
    }

    if (!$hardFailure) {
        $createHostel = $run('hostel.create', 'POST', '/api/petty/v1/tokens/hostels', [201], [
            'hostel_name' => 'API Hostel ' . strtoupper(substr($suffix, 0, 8)),
            'meter_no' => 'MTR-' . strtoupper(substr($suffix, 0, 8)),
            'phone_no' => '0712' . random_int(100000, 999999),
            'no_of_routers' => 1,
            'stake' => 'monthly',
            'amount_due' => 100.00,
        ]);
        if ($createHostel['ok']) {
            $created['hostel_id'] = $createHostel['response']['json']['data']['hostel']['id'] ?? null;
        }

        if ($created['hostel_id']) {
            $run('hostel.update', 'PATCH', '/api/petty/v1/tokens/hostels/' . $created['hostel_id'], [200], [
                'amount_due' => 110.00,
                'phone_no' => '0722' . random_int(100000, 999999),
            ]);
        }
    }

    if (!$hardFailure && $created['hostel_id'] && $created['batch_id']) {
        $createPayment = $run('token.payment.create', 'POST', '/api/petty/v1/tokens/hostels/' . $created['hostel_id'] . '/payments', [201], [
            'funding' => 'single',
            'batch_id' => $created['batch_id'],
            'reference' => 'TP-' . $suffix,
            'amount' => 3.00,
            'transaction_cost' => 0.10,
            'date' => $today,
            'receiver_name' => 'API Receiver',
            'receiver_phone' => '0733' . random_int(100000, 999999),
            'notes' => 'API smoke payment',
        ]);
        if ($createPayment['ok']) {
            $created['token_payment_id'] = $createPayment['response']['json']['data']['payment']['id'] ?? null;
            $created['token_spending_id'] = $createPayment['response']['json']['data']['spending']['id'] ?? null;
        }

        if ($created['token_payment_id']) {
            $run('token.payment.update', 'PATCH', '/api/petty/v1/tokens/payments/' . $created['token_payment_id'], [200], [
                'funding' => 'single',
                'batch_id' => $created['batch_id'],
                'reference' => 'TPU-' . $suffix,
                'amount' => 2.50,
                'transaction_cost' => 0.10,
                'date' => $today,
                'receiver_name' => 'API Receiver Updated',
                'receiver_phone' => '0733' . random_int(100000, 999999),
                'notes' => 'API smoke payment updated',
            ]);
        }
    }

    $adminExpected = $isAdmin ? [200] : [403];

    if ($created['token_payment_id']) {
        $run('token.payment.delete', 'DELETE', '/api/petty/v1/tokens/payments/' . $created['token_payment_id'], $adminExpected);
    }
    if ($created['hostel_id']) {
        $run('hostel.delete', 'DELETE', '/api/petty/v1/tokens/hostels/' . $created['hostel_id'], $adminExpected);
    }
    if ($created['other_spending_id']) {
        $run('spending.other.delete', 'DELETE', '/api/petty/v1/spendings/' . $created['other_spending_id'], $adminExpected);
    }
    if ($created['service_id']) {
        $run('service.delete', 'DELETE', '/api/petty/v1/maintenances/services/' . $created['service_id'], $adminExpected);
    }
    if ($created['bike_id']) {
        $run('bike.delete', 'DELETE', '/api/petty/v1/masters/bikes/' . $created['bike_id'], $adminExpected);
    }
    if ($created['respondent_id']) {
        $run('respondent.delete', 'DELETE', '/api/petty/v1/masters/respondents/' . $created['respondent_id'], $adminExpected);
    }
} finally {
    try {
        if (!empty($created['token_payment_id'])) {
            Payment::query()->where('id', $created['token_payment_id'])->delete();
        }
    } catch (\Throwable $e) {
    }

    try {
        if (!empty($created['token_spending_id'])) {
            SpendingAllocation::query()->where('spending_id', $created['token_spending_id'])->delete();
            Spending::query()->where('id', $created['token_spending_id'])->delete();
        }
    } catch (\Throwable $e) {
    }

    try {
        if (!empty($created['hostel_id'])) {
            Hostel::query()->where('id', $created['hostel_id'])->delete();
        }
    } catch (\Throwable $e) {
    }

    try {
        if (!empty($created['service_id'])) {
            BikeService::query()->where('id', $created['service_id'])->delete();
        }
    } catch (\Throwable $e) {
    }

    try {
        if (!empty($created['other_spending_id'])) {
            SpendingAllocation::query()->where('spending_id', $created['other_spending_id'])->delete();
            Spending::query()->where('id', $created['other_spending_id'])->delete();
        }
    } catch (\Throwable $e) {
    }

    try {
        if (!empty($created['bike_id'])) {
            Bike::query()->where('id', $created['bike_id'])->delete();
        }
    } catch (\Throwable $e) {
    }

    try {
        if (!empty($created['respondent_id'])) {
            Respondent::query()->where('id', $created['respondent_id'])->delete();
        }
    } catch (\Throwable $e) {
    }

    try {
        if (!empty($created['credit_id'])) {
            Credit::query()->where('id', $created['credit_id'])->delete();
        }
    } catch (\Throwable $e) {
    }

    try {
        if (!empty($created['batch_id'])) {
            Batch::query()->where('id', $created['batch_id'])->delete();
        }
    } catch (\Throwable $e) {
    }

    try {
        if ($token->exists) {
            $token->delete();
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
echo PHP_EOL;
echo 'Role used: ' . $writer->role . ' (user_id=' . $writer->id . ')' . PHP_EOL;
echo 'Summary: ' . $passCount . '/' . $total . ' passed.' . PHP_EOL;

exit($passCount === $total ? 0 : 1);
