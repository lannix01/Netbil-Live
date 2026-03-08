<?php

namespace App\Http\Controllers;

use App\Libraries\RouterOSAPI;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ServicesController extends Controller
{
    private static array $columnCache = [];

    public function index()
    {
        $schema = $this->schemaFlags();

        $columns = ['id', 'name'];
        foreach ([
            'speed',
            'rate_limit',
            'duration',
            'duration_minutes',
            'data_limit',
            'price',
            'mk_profile',
            'mikrotik_profile',
            'status',
            'is_active',
            'description',
            'category',
        ] as $column) {
            if ($this->hasColumn('packages', $column)) {
                $columns[] = $column;
            }
        }

        $plans = Package::query()
            ->when($schema['has_status'], fn($query) => $query->where('status', '!=', 'archived'))
            ->orderBy('name')
            ->paginate(20, array_values(array_unique($columns)));

        $customerCounts = [];
        if ($this->hasTable('customers') && $this->hasColumn('customers', 'package_id')) {
            try {
                $customerCounts = DB::table('customers')
                    ->selectRaw('package_id, COUNT(*) AS total')
                    ->whereNotNull('package_id')
                    ->groupBy('package_id')
                    ->pluck('total', 'package_id')
                    ->map(fn($v) => (int)$v)
                    ->all();
            } catch (\Throwable) {
                $customerCounts = [];
            }
        }

        $subscriptionCounts = [];
        if ($this->hasTable('subscriptions') && $this->hasColumn('subscriptions', 'package_id')) {
            try {
                $query = DB::table('subscriptions')
                    ->selectRaw('package_id, COUNT(*) AS total')
                    ->whereNotNull('package_id');

                if ($this->hasColumn('subscriptions', 'status')) {
                    $query->where('status', 'active');
                }

                $subscriptionCounts = $query
                    ->groupBy('package_id')
                    ->pluck('total', 'package_id')
                    ->map(fn($v) => (int)$v)
                    ->all();
            } catch (\Throwable) {
                $subscriptionCounts = [];
            }
        }

        $profileCounts = $this->routerProfileCounts();

        $stats = [
            'total_plans' => (int)$plans->total(),
            'hotspot_plans' => 0,
            'metered_plans' => 0,
            'assigned_customers' => 0,
            'active_subscriptions' => 0,
            'router_profile_users' => 0,
        ];

        foreach ($plans as $plan) {
            $category = $schema['has_category']
                ? strtolower((string)($plan->category ?? 'hotspot'))
                : 'hotspot';

            if ($category === 'metered') {
                $stats['metered_plans']++;
            } else {
                $stats['hotspot_plans']++;
            }

            $assigned = (int)($customerCounts[$plan->id] ?? 0);
            $subs = (int)($subscriptionCounts[$plan->id] ?? 0);

            $profile = trim((string)($plan->mk_profile ?? $plan->mikrotik_profile ?? ''));
            $routerUsers = $profile !== '' ? (int)($profileCounts[$profile] ?? 0) : 0;

            $plan->assigned_customers = $assigned;
            $plan->active_subscriptions = $subs;
            $plan->router_profile_users = $routerUsers;

            $stats['assigned_customers'] += $assigned;
            $stats['active_subscriptions'] += $subs;
            $stats['router_profile_users'] += $routerUsers;
        }

        return view('services.index', [
            'plans' => $plans,
            'schema' => $schema,
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        return view('services.create', [
            'schema' => $this->schemaFlags(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePlan($request);
        $payload = $this->buildPayload($data, null);

        try {
            $plan = Package::query()->create($payload);
        } catch (QueryException $e) {
            $this->throwCreateUpdateValidationError($e);
            throw $e;
        }
        $this->syncRouterProfile($plan, $payload);

        return redirect()
            ->route('services.index')
            ->with('success', 'Plan created successfully.');
    }

    public function edit(Package $package)
    {
        $schema = $this->schemaFlags();

        $dataLimitMb = null;
        if ($schema['has_data_limit']) {
            $bytes = (float)($package->data_limit ?? 0);
            if ($bytes > 0) {
                $dataLimitMb = round($bytes / (1024 * 1024), 2);
            }
        }

        return view('services.edit', [
            'plan' => $package,
            'schema' => $schema,
            'dataLimitMb' => $dataLimitMb,
        ]);
    }

    public function update(Request $request, Package $package)
    {
        $data = $this->validatePlan($request);
        $payload = $this->buildPayload($data, $package);

        try {
            $package->fill($payload);
            $package->save();
        } catch (QueryException $e) {
            $this->throwCreateUpdateValidationError($e);
            throw $e;
        }

        $this->syncRouterProfile($package, $payload);

        return redirect()
            ->route('services.index')
            ->with('success', 'Plan updated successfully.');
    }

    public function destroy(Package $package)
    {
        if ($this->hasColumn('packages', 'status')) {
            $package->status = 'archived';
            $package->save();

            return redirect()
                ->route('services.index')
                ->with('success', 'Plan archived.');
        }

        if ($this->hasColumn('packages', 'is_active')) {
            $package->is_active = false;
            $package->save();

            return redirect()
                ->route('services.index')
                ->with('success', 'Plan disabled.');
        }

        $package->delete();

        return redirect()
            ->route('services.index')
            ->with('success', 'Plan deleted.');
    }

    private function validatePlan(Request $request): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'profile' => 'nullable|string|max:255',
            'rate_limit' => 'nullable|string|max:255',
            'data_limit_mb' => 'nullable|numeric|min:0',
            'auto_create_profile' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ];

        if ($this->hasColumn('packages', 'category')) {
            $rules['category'] = 'required|string|in:hotspot,metered';
        }

        if ($this->hasColumn('packages', 'speed')) {
            $rules['speed'] = 'required|string|max:255';
        }
        if ($this->hasColumn('packages', 'price')) {
            $rules['price'] = 'required|numeric|min:0';
        }
        if ($this->hasColumn('packages', 'duration')) {
            $rules['duration'] = 'nullable|integer|min:1';
        }
        if ($this->hasColumn('packages', 'duration_minutes')) {
            $rules['duration_minutes'] = 'nullable|integer|min:1';
        }
        if ($this->hasColumn('packages', 'description')) {
            $rules['description'] = 'nullable|string';
        }
        if ($this->hasColumn('packages', 'status')) {
            $rules['status'] = 'nullable|in:active,inactive,archived';
        }

        $data = $request->validate($rules);

        $category = $this->hasColumn('packages', 'category')
            ? strtolower((string)($data['category'] ?? 'hotspot'))
            : 'hotspot';

        if ($this->schemaFlags()['has_profile_column']) {
            $hasProfile = trim((string)($data['profile'] ?? '')) !== '';
            $autoProfile = (bool)($data['auto_create_profile'] ?? false);
            if (!$hasProfile && !$autoProfile) {
                throw ValidationException::withMessages([
                    'profile' => 'Provide a profile name or enable auto-create profile.',
                ]);
            }
        }

        if ($category === 'hotspot') {
            $hasDuration = !empty($data['duration']) || !empty($data['duration_minutes']);
            if (!$hasDuration && ($this->hasColumn('packages', 'duration') || $this->hasColumn('packages', 'duration_minutes'))) {
                throw ValidationException::withMessages([
                    'duration' => 'Duration is required for hotspot plans.',
                ]);
            }
        }

        return $data;
    }

    private function buildPayload(array $data, ?Package $existing): array
    {
        $payload = [
            'name' => trim((string)$data['name']),
        ];

        $category = $this->hasColumn('packages', 'category')
            ? strtolower((string)($data['category'] ?? 'hotspot'))
            : 'hotspot';

        if ($this->hasColumn('packages', 'category')) {
            $payload['category'] = $category;
        }

        if ($this->hasColumn('packages', 'price') && array_key_exists('price', $data)) {
            $payload['price'] = $data['price'] !== null ? (float)$data['price'] : 0;
        }

        if ($this->hasColumn('packages', 'speed')) {
            $speed = trim((string)($data['speed'] ?? ($existing?->speed ?? '')));
            if ($speed === '' && $this->hasColumn('packages', 'rate_limit')) {
                $speed = trim((string)($data['rate_limit'] ?? ($existing?->rate_limit ?? '')));
            }
            if ($speed === '') {
                // Preserve compatibility with strict schemas that require a non-null speed.
                $speed = '1M/1M';
            }

            $payload['speed'] = $speed;
        }

        $durationMinutes = $data['duration_minutes'] ?? null;
        $durationHours = $data['duration'] ?? null;

        $fallbackDurationHours = (int)($existing?->duration ?? 0);
        if ($fallbackDurationHours <= 0) {
            $fallbackDurationHours = 1;
        }
        $fallbackDurationMinutes = (int)($existing?->duration_minutes ?? 0);
        if ($fallbackDurationMinutes <= 0) {
            $fallbackDurationMinutes = $fallbackDurationHours * 60;
        }

        if ($durationMinutes === null && $durationHours !== null) {
            $durationMinutes = (int)$durationHours * 60;
        }
        if ($durationHours === null && $durationMinutes !== null) {
            $durationHours = max(1, (int)ceil(((int)$durationMinutes) / 60));
        }

        if ($this->hasColumn('packages', 'duration_minutes')) {
            if ($category === 'metered') {
                // Keep a safe fallback to satisfy strict schemas while metered logic ignores duration.
                $payload['duration_minutes'] = $fallbackDurationMinutes;
            } elseif ($durationMinutes !== null) {
                $payload['duration_minutes'] = (int)$durationMinutes;
            }
        }

        if ($this->hasColumn('packages', 'duration')) {
            if ($category === 'metered') {
                // Keep a safe fallback to satisfy strict schemas while metered logic ignores duration.
                $payload['duration'] = $fallbackDurationHours;
            } elseif ($durationHours !== null) {
                $payload['duration'] = (int)$durationHours;
            }
        }

        if ($this->hasColumn('packages', 'data_limit')) {
            $mb = $data['data_limit_mb'] ?? null;
            if ($category === 'metered') {
                $payload['data_limit'] = null;
            } elseif ($mb !== null && $mb !== '') {
                $payload['data_limit'] = (int)round(((float)$mb) * 1024 * 1024);
            } elseif (!$existing) {
                $payload['data_limit'] = null;
            }
        }

        $profile = trim((string)($data['profile'] ?? ''));
        $autoCreate = (bool)($data['auto_create_profile'] ?? false);
        if ($profile === '' && $autoCreate) {
            $profile = 'plan_' . Str::slug($payload['name'], '_');
        }

        if ($profile !== '') {
            if ($autoCreate) {
                $profile = $this->nextAvailableProfile($profile, $existing);
            } elseif ($this->profileExists($profile, $existing)) {
                throw ValidationException::withMessages([
                    'profile' => 'This profile name already exists. Use a different profile or enable auto-create profile.',
                ]);
            }
        }

        if ($this->hasColumn('packages', 'mk_profile') && $profile !== '') {
            $payload['mk_profile'] = $profile;
        }
        if ($this->hasColumn('packages', 'mikrotik_profile') && $profile !== '') {
            $payload['mikrotik_profile'] = $profile;
        }

        if ($this->hasColumn('packages', 'rate_limit')) {
            $rateLimit = trim((string)($data['rate_limit'] ?? ''));
            if ($rateLimit === '' && $this->hasColumn('packages', 'speed')) {
                $rateLimit = trim((string)($data['speed'] ?? ($existing?->speed ?? '')));
            }
            $payload['rate_limit'] = $rateLimit;
        }

        if ($this->hasColumn('packages', 'description') && array_key_exists('description', $data)) {
            $payload['description'] = $data['description'];
        }

        if ($this->hasColumn('packages', 'status')) {
            $payload['status'] = $data['status'] ?? ($existing?->status ?? 'active');
        }

        if ($this->hasColumn('packages', 'is_active')) {
            if (array_key_exists('is_active', $data)) {
                $payload['is_active'] = (bool)$data['is_active'];
            } elseif (array_key_exists('status', $data)) {
                $payload['is_active'] = ($data['status'] ?? 'active') === 'active';
            }
        }

        if ($this->hasColumn('packages', 'auto_create_profile')) {
            $payload['auto_create_profile'] = $autoCreate;
        }

        return $payload;
    }

    private function throwCreateUpdateValidationError(QueryException $e): void
    {
        $text = strtolower((string)$e->getMessage());
        if (
            str_contains($text, 'packages_mikrotik_profile_unique')
            || (str_contains($text, 'duplicate entry') && str_contains($text, 'mikrotik_profile'))
        ) {
            throw ValidationException::withMessages([
                'profile' => 'This profile already exists. Use a different profile name or enable auto-create profile.',
            ]);
        }
    }

    private function profileExists(string $profile, ?Package $existing = null): bool
    {
        $profile = trim($profile);
        if ($profile === '' || !$this->schemaFlags()['has_profile_column']) {
            return false;
        }

        $query = Package::query();
        if ($existing && $existing->exists) {
            $query->where('id', '!=', $existing->id);
        }

        $query->where(function ($inner) use ($profile) {
            $first = true;
            if ($this->hasColumn('packages', 'mk_profile')) {
                if ($first) {
                    $inner->where('mk_profile', $profile);
                    $first = false;
                } else {
                    $inner->orWhere('mk_profile', $profile);
                }
            }
            if ($this->hasColumn('packages', 'mikrotik_profile')) {
                if ($first) {
                    $inner->where('mikrotik_profile', $profile);
                } else {
                    $inner->orWhere('mikrotik_profile', $profile);
                }
            }
        });

        return $query->exists();
    }

    private function nextAvailableProfile(string $baseProfile, ?Package $existing = null): string
    {
        $baseProfile = trim($baseProfile);
        if ($baseProfile === '') {
            $baseProfile = 'plan_' . Str::lower(Str::random(8));
        }

        $baseProfile = Str::limit($baseProfile, 255, '');
        if (!$this->profileExists($baseProfile, $existing)) {
            return $baseProfile;
        }

        $seed = Str::limit($baseProfile, 245, '');
        for ($i = 2; $i <= 9999; $i++) {
            $candidate = $seed . '_' . $i;
            if (!$this->profileExists($candidate, $existing)) {
                return $candidate;
            }
        }

        return Str::limit($seed . '_' . time(), 255, '');
    }

    private function syncRouterProfile(Package $plan, array $payload): void
    {
        $profile = trim((string)(
            $payload['mk_profile']
            ?? $payload['mikrotik_profile']
            ?? $plan->mk_profile
            ?? $plan->mikrotik_profile
            ?? ''
        ));

        if ($profile === '') {
            return;
        }

        $rateLimit = trim((string)(
            $payload['rate_limit']
            ?? $payload['speed']
            ?? $plan->rate_limit
            ?? $plan->speed
            ?? ''
        ));

        [$api, $error] = $this->connectApi();
        if ($error) {
            Log::warning('Plan profile sync skipped', ['plan_id' => $plan->id, 'error' => $error]);
            return;
        }

        try {
            $rows = $api->comm('/ip/hotspot/user/profile/print', ['?name' => $profile]) ?? [];
            $existing = is_array($rows) ? ($rows[0] ?? null) : null;

            $profilePayload = ['name' => $profile];
            if ($rateLimit !== '') {
                $profilePayload['rate-limit'] = $rateLimit;
            }

            if ($existing && !empty($existing['.id'])) {
                $profilePayload['.id'] = $existing['.id'];
                $api->comm('/ip/hotspot/user/profile/set', $profilePayload);
            } else {
                $api->comm('/ip/hotspot/user/profile/add', $profilePayload);
            }
        } catch (\Throwable $e) {
            Log::warning('Plan profile sync failed', [
                'plan_id' => $plan->id,
                'profile' => $profile,
                'error' => $e->getMessage(),
            ]);
        } finally {
            try {
                $api->disconnect();
            } catch (\Throwable) {
            }
        }
    }

    private function connectApi(): array
    {
        $api = new RouterOSAPI();
        $config = config('mikrotik');

        $host = $config['host'] ?? null;
        $user = $config['user'] ?? null;
        $pass = (string)($config['pass'] ?? '');
        $port = (int)($config['port'] ?? 8728);

        if (!$host || !$user) {
            return [null, 'MikroTik config missing (host/user).'];
        }

        try {
            $api->port = $port;
            $ok = $api->connect($host, $user, $pass);
            if (!$ok) {
                return [null, 'Could not connect to MikroTik.'];
            }

            return [$api, null];
        } catch (\Throwable $e) {
            return [null, $e->getMessage()];
        }
    }

    private function schemaFlags(): array
    {
        return [
            'has_speed' => $this->hasColumn('packages', 'speed'),
            'has_rate_limit' => $this->hasColumn('packages', 'rate_limit'),
            'has_duration' => $this->hasColumn('packages', 'duration'),
            'has_duration_minutes' => $this->hasColumn('packages', 'duration_minutes'),
            'has_data_limit' => $this->hasColumn('packages', 'data_limit'),
            'has_price' => $this->hasColumn('packages', 'price'),
            'has_status' => $this->hasColumn('packages', 'status'),
            'has_is_active' => $this->hasColumn('packages', 'is_active'),
            'has_description' => $this->hasColumn('packages', 'description'),
            'has_auto_create_profile' => $this->hasColumn('packages', 'auto_create_profile'),
            'has_profile_column' => $this->hasColumn('packages', 'mk_profile') || $this->hasColumn('packages', 'mikrotik_profile'),
            'has_category' => $this->hasColumn('packages', 'category'),
        ];
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (!array_key_exists($key, self::$columnCache)) {
            try {
                self::$columnCache[$key] = Schema::hasColumn($table, $column);
            } catch (\Throwable) {
                self::$columnCache[$key] = false;
            }
        }

        return self::$columnCache[$key];
    }

    private function hasTable(string $table): bool
    {
        $key = 'table.' . $table;
        if (!array_key_exists($key, self::$columnCache)) {
            try {
                self::$columnCache[$key] = Schema::hasTable($table);
            } catch (\Throwable) {
                self::$columnCache[$key] = false;
            }
        }

        return (bool)self::$columnCache[$key];
    }

    private function routerProfileCounts(): array
    {
        [$api, $error] = $this->connectApi();
        if ($error) {
            return [];
        }

        try {
            $rows = $api->comm('/ip/hotspot/user/print') ?? [];
            $counts = [];
            foreach ($rows as $row) {
                $profile = trim((string)($row['profile'] ?? ''));
                if ($profile === '') {
                    continue;
                }
                $counts[$profile] = (int)($counts[$profile] ?? 0) + 1;
            }
            return $counts;
        } catch (\Throwable $e) {
            Log::warning('Failed building router profile counts', ['error' => $e->getMessage()]);
            return [];
        } finally {
            try {
                $api->disconnect();
            } catch (\Throwable) {
            }
        }
    }
}
