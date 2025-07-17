<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use App\Libraries\RouterOSAPI;

class MetricsController extends Controller
{
    protected $API;

    public function __construct()
    {
        require_once app_path('Libraries/routeros_api.class.php');
        $this->API = new \RouterosAPI();

        $this->API->connect(
            config('mikrotik.host'),
            config('mikrotik.user'),
            config('mikrotik.pass'),
            config('mikrotik.port'),
            config('mikrotik.timeout')
        );
    }

    public function fetchAll(): JsonResponse
    {
        // --- MikroTik System Info ---
        $system = $this->API->comm('/system/resource/print')[0] ?? [];
        $identity = $this->API->comm('/system/identity/print')[0]['name'] ?? 'Unknown';

        // --- Active Hotspot Users ---
        $activeHotspots = $this->API->comm('/ip/hotspot/active/print') ?? [];

        // --- DHCP Leases (Connected devices) ---
        $leases = $this->API->comm('/ip/dhcp-server/lease/print') ?? [];

        // --- Interfaces ---
        $interfaces = $this->API->comm('/interface/print') ?? [];

        // --- Interface Traffic (RX/TX) ---
        $traffic = [];
        foreach ($interfaces as $iface) {
            $ifaceName = $iface['name'];
            $stats = $this->API->comm('/interface/monitor-traffic', [
                'interface' => $ifaceName,
                'once' => ''
            ]);
            $traffic[] = [
                'name' => $ifaceName,
                'rx' => $stats[0]['rx-bits-per-second'] ?? 0,
                'tx' => $stats[0]['tx-bits-per-second'] ?? 0,
            ];
        }

        // --- Laravel DB Stats ---
        $totalUsers = DB::table('customers')->count();
        $activeUsers = DB::table('customers')->where('status', 'active')->count();
        $totalInvoices = DB::table('invoices')->count();
        $totalPayments = DB::table('payments')->count();
        $totalDevices = DB::table('devices')->count();

        return response()->json([
            'system' => $system,
            'identity' => $identity,
            'hotspots' => $activeHotspots,
            'leases' => $leases,
            'interfaces' => $interfaces,
            'traffic' => $traffic,
            'totals' => [
                'customers' => $totalUsers,
                'active_customers' => $activeUsers,
                'invoices' => $totalInvoices,
                'payments' => $totalPayments,
                'devices' => $totalDevices,
            ]
        ]);
    }

    public function disconnectHotspotUser($mac): JsonResponse
    {
        try {
            $users = $this->API->comm('/ip/hotspot/active/print');
            foreach ($users as $user) {
                if ($user['mac-address'] === $mac) {
                    $this->API->comm('/ip/hotspot/active/remove', [
                        '.id' => $user['.id']
                    ]);
                    return response()->json(['success' => true, 'message' => 'User disconnected.']);
                }
            }
            return response()->json(['success' => false, 'message' => 'MAC address not found.']);
        } catch (\Exception $e) {
            Log::error("Error disconnecting user: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Server error.']);
        }
    }

    public function listHotspotUsers(): JsonResponse
    {
        $users = $this->API->comm('/ip/hotspot/user/print');
        return response()->json($users);
    }

    public function listLeases(): JsonResponse
    {
        $leases = $this->API->comm('/ip/dhcp-server/lease/print');
        return response()->json($leases);
    }

    public function listInterfaces(): JsonResponse
    {
        $interfaces = $this->API->comm('/interface/print');
        return response()->json($interfaces);
    }
}