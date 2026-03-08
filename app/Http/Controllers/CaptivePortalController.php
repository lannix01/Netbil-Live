<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Subscription;
use App\Models\Customer;
use App\Services\RouterosService;
use App\Services\MpesaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CaptivePortalController extends Controller
{
    protected RouterosService $ros;
    protected MpesaService $mpesa;

    public function __construct(RouterosService $ros, MpesaService $mpesa)
    {
        $this->ros = $ros;
        $this->mpesa = $mpesa;
    }

    /**
     * Landing page. MikroTik will add query params like mac, ip, link-login, etc.
     */
    public function landing(Request $req)
    {
        $packagesQuery = Package::query();
        if (Schema::hasColumn('packages', 'status')) {
            $packagesQuery->where('status', '!=', 'archived');
        }
        if (Schema::hasColumn('packages', 'category')) {
            $packagesQuery->where('category', 'hotspot');
        }
        $packages = $packagesQuery->get();
        // capture common router redirect params (optional)
        $prefill = $req->only(['mac', 'ip', 'link-login', 'uamip', 'uamport', 'username']);
        return view('portal.landing', compact('packages', 'prefill'));
    }

    /**
     * PPPoE activation flow
     */
    public function createPppoe(Request $req)
    {
        $data = $req->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'mac_address' => 'nullable|string',
            'username' => 'required|string',
            'password' => 'required|string',
            'profile' => 'nullable|string',
            'package_id' => 'nullable|exists:packages,id',
        ]);

        DB::beginTransaction();
        try {
            // create or find customer
            $customer = Customer::firstOrCreate(
                ['phone' => $data['phone'] ?? null],
                ['name' => $data['name'] ?? 'PPPoE Customer']
            );

            // create PPP secret on router
            $this->ros->addPppSecret($data['username'], $data['password'], $data['profile'] ?? null);

            // compute expiry if package selected
            $starts = Carbon::now();
            $expires = null;
            $price = 0;
            if (!empty($data['package_id'])) {
                $pkg = Package::find($data['package_id']);
                $minutes = $pkg->duration_minutes ?? ($pkg->duration * 60);
                $expires = $starts->copy()->addMinutes($minutes);
                $price = $pkg->price;
            }

            $sub = Subscription::create([
                'customer_id' => $customer->id,
                'package_id' => $data['package_id'] ?? null,
                'type' => 'pppoe',
                'username' => $data['username'],
                'password' => encrypt($data['password']),
                'mac_address' => $data['mac_address'] ?? null,
                'mk_profile' => $data['profile'] ?? null,
                'starts_at' => $starts,
                'expires_at' => $expires,
                'status' => 'active',
                'price_paid' => $price,
            ]);

            DB::commit();

            return view('portal.success', compact('sub'));
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('createPppoe error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return back()->withErrors('Failed to provision PPPoE. Try again.');
        }
    }

    /**
     * Hotspot flow: buy package (can be paid via STK push)
     */
    public function createHotspotSession(Request $req)
    {
        $data = $req->validate([
            'name' => 'nullable|string',
            'phone' => 'nullable|string',
            'mac_address' => 'nullable|string',
            'package_id' => 'required|exists:packages,id',
            'return_url' => 'nullable|url',
        ]);

        $pkg = Package::find($data['package_id']);
        if (!$pkg) return back()->withErrors('Invalid package');
        if (Schema::hasColumn('packages', 'category')) {
            $category = strtolower((string)($pkg->category ?? 'hotspot'));
            if ($category !== 'hotspot') {
                return back()->withErrors('Selected package is not available for hotspot access.');
            }
        }

        try {
            $customer = Customer::firstOrCreate(
                ['phone' => $data['phone'] ?? null],
                ['name' => $data['name'] ?? ('guest_'.$pkg->id)]
            );

            // compute expiry
            $minutes = $pkg->duration_minutes ?? ($pkg->duration * 60);
            $expires = now()->addMinutes($minutes);

            // generate credentials
            $username = 'guest_'.Str::random(6);
            $password = Str::random(8);

            // create hotspot user (RouterOS)
            $this->ros->addHotspotUser($username, $password, $pkg->mk_profile ?? $pkg->name, $minutes * 60);

            $sub = Subscription::create([
                'customer_id' => $customer->id,
                'package_id' => $pkg->id,
                'type' => 'hotspot',
                'username' => $username,
                'password' => encrypt($password),
                'mac_address' => $data['mac_address'] ?? null,
                'mk_profile' => $pkg->mk_profile ?? $pkg->name,
                'starts_at' => now(),
                'expires_at' => $expires,
                'status' => 'active',
                'price_paid' => $pkg->price,
            ]);

            // If mikroTik provided link-login param, auto-post credentials to it.
            $link = $req->input('link-login') ?? $data['return_url'] ?? null;

            return view('portal.hotspot-credentials', compact('sub','username','password','pkg','link'));
        } catch (\Throwable $e) {
            Log::error('createHotspotSession error: '.$e->getMessage());
            return back()->withErrors('Failed to provision hotspot session.');
        }
    }

    /**
     * Metered registration
     */
    public function createMeteredUser(Request $req)
    {
        $data = $req->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'mac_address' => 'nullable|string',
            'preferred_speed' => 'nullable|string',
        ]);

        try {
            $customer = Customer::firstOrCreate(
                ['phone' => $data['phone'] ?? null],
                ['name' => $data['name']]
            );

            $sub = Subscription::create([
                'customer_id' => $customer->id,
                'type' => 'metered',
                'mac_address' => $data['mac_address'] ?? null,
                'starts_at' => now(),
                'expires_at' => null,
                'status' => 'active',
                'price_paid' => 0,
                'meta' => ['preferred_speed' => $data['preferred_speed'] ?? null],
            ]);

            // Optionally whitelist on RouterOS (router-specific)
            if (!empty($data['mac_address'])) {
                $this->ros->whitelistMacForMetered($data['mac_address']);
            }

            return view('portal.metered-success', compact('sub'));
        } catch (\Throwable $e) {
            Log::error('createMeteredUser error: '.$e->getMessage());
            return back()->withErrors('Failed to register metered user.');
        }
    }

    /**
     * Initiate STK push for payment (M-Pesa). Returns JSON.
     */
    public function initiateStk(Request $req)
    {
        $data = $req->validate([
            'package_id' => 'required|exists:packages,id',
            'phone' => 'required|string',
            'callback_url' => 'nullable|url'
        ]);

        $pkg = Package::find($data['package_id']);
        if (!$pkg) {
            return response()->json(['error' => 'invalid package'], 422);
        }
        if (Schema::hasColumn('packages', 'category')) {
            $category = strtolower((string)($pkg->category ?? 'hotspot'));
            if ($category !== 'hotspot') {
                return response()->json(['error' => 'package category not allowed'], 422);
            }
        }
        try {
            $resp = $this->mpesa->stkPush($data['phone'], $pkg->price, [
                'package_id' => $pkg->id,
            ], $data['callback_url'] ?? route('portal.payment.webhook'));

            return response()->json($resp);
        } catch (\Throwable $e) {
            Log::error('STK push error: '.$e->getMessage());
            return response()->json(['error'=>'failed to start payment'], 500);
        }
    }

    /**
     * Payment webhook (provider posts payment result here)
     */
    public function paymentWebhook(Request $req)
    {
        // Provider-specific parsing. We assume $req contains `merchantRequestID` or metadata
        // Example: find subscription/order by metadata.package_id and phone, mark as paid.
        Log::info('paymentWebhook received', $req->all());

        // TODO: implement provider verification (signature) for production.
        // Here is a simple pattern:
        $payload = $req->all();

        // If MpesaService can verify and normalize:
        try {
            $result = $this->mpesa->handleWebhook($payload);
            if ($result && !empty($result['package_id']) && !empty($result['phone'])) {
                // create or update subscription: this heavily depends on how you created pre-order
                // For simplicity assume we have created a pending subscription with customer phone stored somewhere.
                // Implement your own order mapping logic (order IDs stored in DB).
            }
        } catch (\Throwable $e) {
            Log::error('paymentWebhook handler error: '.$e->getMessage());
        }

        return response()->json(['ok'=>true]);
    }
}
