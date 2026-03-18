<?php

namespace Tests\Feature\Inventory;

use App\Modules\Inventory\Models\InventoryUser;
use App\Modules\Inventory\Support\InventoryAccess;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TechnicianInventoryControllerTest extends TestCase
{
    public function test_technician_dashboard_shows_router_assignments_found_by_identity_fallback(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not available in this environment.');
        }

        InventoryAccess::forgetResolvedPermissions();
        Schema::shouldReceive('hasTable')->andReturn(false);

        Config::set('inventory.router_api.enabled', true);
        Config::set('inventory.router_api.base_url', 'https://api.skybrix.co.ke/v1');
        Config::set('inventory.router_api.username', 'router-user');
        Config::set('inventory.router_api.password', 'router-secret');
        Config::set('inventory.router_api.max_page_size', 100);

        Http::fake(function ($request) {
            $query = [];
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            if (($query['technician_id'] ?? null) === '4') {
                return Http::response([
                    'message' => 'With techs',
                    'data' => [],
                    'pagination' => ['current_page' => 1, 'per_page' => 100, 'total' => 0, 'page_count' => 1],
                ], 200);
            }

            if (($query['search'] ?? null) === 'okodoi') {
                return Http::response([
                    'message' => 'With techs',
                    'data' => [
                        [
                            'serial_number' => '4857544310328A88',
                            'batch_number' => 'BATCH-20260106085614',
                            'brand' => 'HUAWEI',
                            'model_number' => 'HG8546M',
                            'technician_id' => '24',
                            'technician_name' => 'Shaddrack Okodoi',
                            'technician_email' => 'shadrackkdoi@gmail.com',
                            'assigned_date' => '2026-01-06 08:56:14',
                            'expected_deployment_date' => '2026-01-06',
                            'urgency' => 'overdue',
                            'days_assigned' => '67',
                        ],
                    ],
                    'pagination' => ['current_page' => 1, 'per_page' => 100, 'total' => 1, 'page_count' => 1],
                ], 200);
            }

            return Http::response([
                'message' => 'With techs',
                'data' => [],
                'pagination' => ['current_page' => 1, 'per_page' => 100, 'total' => 0, 'page_count' => 1],
            ], 200);
        });

        $user = new InventoryUser([
            'name' => 'Okodoi Technician',
            'email' => 'okodoi@skybrix.co.ke',
            'inventory_role' => 'technician',
            'inventory_enabled' => true,
            'inventory_force_password_change' => false,
        ]);
        $user->id = 4;

        $response = $this
            ->actingAs($user, 'inventory')
            ->get('/inventory/tech/dashboard');

        $response
            ->assertOk()
            ->assertSee('Assigned Routers')
            ->assertSee('4857544310328A88')
            ->assertSee('Batch BATCH-20260106085614')
            ->assertSee('Overdue')
            ->assertSee('1 Routers');

        Http::assertSent(function ($request) {
            $query = [];
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            return ($query['technician_id'] ?? null) === '4';
        });

        Http::assertSent(function ($request) {
            $query = [];
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            return ($query['search'] ?? null) === 'okodoi';
        });
    }

    public function test_technician_site_lookup_returns_registered_sites(): void
    {
        InventoryAccess::forgetResolvedPermissions();
        Schema::shouldReceive('hasTable')->once()->with('inventory_users')->andReturn(false);

        Config::set('inventory.router_api.enabled', true);
        Config::set('inventory.router_api.base_url', 'https://api.skybrix.co.ke/v1');
        Config::set('inventory.router_api.username', 'router-user');
        Config::set('inventory.router_api.password', 'router-secret');

        Http::fake([
            'https://api.skybrix.co.ke/v1/onts*' => Http::response([
                'message' => 'Sites',
                'data' => [
                    [
                        'site_id' => '421662',
                        'site_name' => 'Silicone Valley Hostel Nyawita',
                        'serial' => '48575443106DEA0D',
                        'status' => 'online',
                        'rx_power' => '-25.68',
                        'tx_power' => '',
                        'olt' => 'MA5683T',
                        'slot' => '1',
                        'pon' => '9',
                        'acs_last_inform' => '2026-03-14 10:50:27',
                        'reg_date' => '2026-03-01 08:00:00',
                    ],
                ],
                'pagination' => ['current_page' => 1, 'per_page' => 8, 'total' => 1, 'page_count' => 1],
            ], 200),
        ]);

        $user = new InventoryUser([
            'name' => 'Field Technician',
            'email' => 'field-tech@example.com',
            'inventory_role' => 'technician',
            'inventory_enabled' => true,
            'inventory_force_password_change' => false,
        ]);
        $user->id = 320;

        $response = $this
            ->actingAs($user, 'inventory')
            ->getJson('/inventory/tech/sites/lookup?q=Silicone');

        $response
            ->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('data.0.site_id', '421662')
            ->assertJsonPath('data.0.site_name', 'Silicone Valley Hostel Nyawita')
            ->assertJsonPath('data.0.site_serial', '48575443106DEA0D')
            ->assertJsonPath('data.0.olt', 'MA5683T');
    }

    public function test_viewer_cannot_access_technician_site_lookup(): void
    {
        InventoryAccess::forgetResolvedPermissions();
        Schema::shouldReceive('hasTable')->once()->with('inventory_users')->andReturn(false);

        $user = new InventoryUser([
            'name' => 'Inventory Viewer',
            'email' => 'inventory-viewer@example.com',
            'inventory_role' => 'viewer',
            'inventory_enabled' => true,
            'inventory_force_password_change' => false,
        ]);
        $user->id = 321;

        $this
            ->actingAs($user, 'inventory')
            ->getJson('/inventory/tech/sites/lookup?q=Silicone')
            ->assertForbidden();
    }
}
