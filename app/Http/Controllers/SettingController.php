<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Ad;
use App\Http\Controllers\MikrotikTestController;
use Illuminate\Support\Facades\Schema;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'mikrotiks');
        $action = $request->get('action', null);
        $editPackage = null;

        // Default collections / data for tabs
        $packages = collect();
        $portal = [];
        $payments = collect();
        $subs = collect();
        $ads = collect();

        // Defaults for MikroTik
        $connected = false;
        $host = config('mikrotik.host');
        $webfigUrl = $this->buildWebfigUrl($host);
        $system = [];

        // --- MIKROTIK TAB ---
        if ($activeTab === 'mikrotiks') {
            $mikrotikController = app(MikrotikTestController::class);

            try {
                $response = $mikrotikController->system();
                $data = $response->getData(true);

                $connected = $data['connected'] ?? false;
                $host = $data['host'] ?? config('mikrotik.host');
                $webfigUrl = $this->buildWebfigUrl($host);
                $system = $data['system'] ?? [];
            } catch (\Exception $e) {
                $connected = false;
                $host = config('mikrotik.host');
                $webfigUrl = $this->buildWebfigUrl($host);
                $system = [];
            }
        }

        // --- PACKAGES TAB ---
        if ($activeTab === 'packages') {
            $packages = Package::latest()->get();
            if ($action === 'edit') {
                $editPackage = Package::find($request->get('id'));
            }
        }

        // --- PORTAL TAB ---
        if ($activeTab === 'portal') {
            $hotspotPlans = 0;
            $meteredPlans = 0;

            if (Schema::hasColumn('packages', 'category')) {
                $hotspotPlans = Package::query()->where('category', 'hotspot')->count();
                $meteredPlans = Package::query()->where('category', 'metered')->count();
            } else {
                $hotspotPlans = Package::query()->count();
            }

            $portal = [
                'preview_url' => route('connect.index', [
                    'mac' => 'AA:BB:CC:DD:EE:FF',
                    'ip' => $request->ip(),
                    'link-login' => '',
                ]),
                'open_url' => route('connect.index'),
                'hotspot_plans' => $hotspotPlans,
                'metered_plans' => $meteredPlans,
                'generated_at' => now()->toDateTimeString(),
            ];
        }

        // --- PAYMENTS TAB ---
        if ($activeTab === 'payments') {
            $payments = Payment::latest()->get();
        }

        // --- SUBSCRIPTIONS TAB ---
        if ($activeTab === 'subs') {
            $subs = Subscription::latest()->get();
        }

        // --- ADS TAB ---
        if ($activeTab === 'ads') {
            // Fetch ads ordered by priority descending
            $ads = Ad::query()
                ->orderByDesc('priority')
                ->orderByDesc('id')
                ->get();
        }

        return view('settings.index', compact(
            'activeTab',
            'action',
            'editPackage',
            'packages',
            'portal',
            'payments',
            'subs',
            'ads',          //  now always defined if tab is ads
            'connected',
            'host',
            'webfigUrl',
            'system'
        ));
    }

    private function buildWebfigUrl(?string $host): ?string
    {
        $host = trim((string) $host);
        if ($host === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $host) === 1) {
            return rtrim($host, '/');
        }

        return 'http://' . rtrim($host, '/');
    }
}
