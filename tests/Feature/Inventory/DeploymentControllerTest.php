<?php

namespace Tests\Feature\Inventory;

use App\Modules\Inventory\Models\InventoryUser;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DeploymentControllerTest extends TestCase
{
    public function test_deployments_site_workspace_shows_modal_with_main_and_chain_routers(): void
    {
        Config::set('inventory.router_api.enabled', true);
        Config::set('inventory.router_api.base_url', 'https://api.skybrix.co.ke/v1');
        Config::set('inventory.router_api.username', 'router-user');
        Config::set('inventory.router_api.password', 'router-secret');
        Config::set('inventory.router_api.default_page_size', 20);
        Config::set('inventory.router_api.max_page_size', 100);
        Config::set('inventory.router_api.collection_page_limit', 0);

        Http::fake([
            'https://api.skybrix.co.ke/v1/inventory/deployed-routers*' => Http::response([
                'message' => 'Deployed',
                'data' => [
                    [
                        'serial_number' => 'MAIN-SN-001',
                        'batch_number' => 'BATCH-001',
                        'brand' => 'Huawei',
                        'model_number' => 'HG8546M',
                        'site_id' => '421662',
                        'site_name' => 'Silicone Valley Hostel Nyawita',
                        'status' => 'working',
                        'router_status' => 'online',
                        'installed_date' => '2026-03-14 09:10:00',
                        'is_primary' => '1',
                    ],
                    [
                        'serial_number' => 'CHAIN-SN-002',
                        'batch_number' => 'BATCH-001',
                        'brand' => 'Tenda',
                        'model_number' => 'F3',
                        'site_id' => '421662',
                        'site_name' => 'Silicone Valley Hostel Nyawita',
                        'status' => 'working',
                        'router_status' => 'online',
                        'installed_date' => '2026-03-14 09:12:00',
                        'is_primary' => '0',
                    ],
                ],
                'pagination' => ['current_page' => 1, 'per_page' => 100, 'total' => 2, 'page_count' => 1],
            ], 200),
            'https://api.skybrix.co.ke/v1/onts*' => Http::response([
                'message' => 'Sites',
                'data' => [
                    [
                        'site_id' => '421662',
                        'site_name' => 'Silicone Valley Hostel Nyawita',
                        'serial' => 'MAIN-SN-001',
                        'status' => 'online',
                        'rx_power' => '-25.68',
                        'tx_power' => '1.8',
                        'acs_last_inform' => '2026-03-14 10:50:27',
                        'olt' => 'MA5683T',
                        'slot' => '1',
                        'pon' => '9',
                    ],
                ],
                'pagination' => ['current_page' => 1, 'per_page' => 100, 'total' => 1, 'page_count' => 1],
            ], 200),
        ]);

        $user = new InventoryUser([
            'name' => 'Inventory Admin',
            'email' => 'inventory-admin-deployments@example.com',
            'inventory_role' => 'admin',
            'inventory_enabled' => true,
            'inventory_force_password_change' => false,
        ]);
        $user->id = 140;

        $response = $this
            ->actingAs($user, 'inventory')
            ->get('/inventory/deployments?section=sites&focus_site=421662');

        $response
            ->assertOk()
            ->assertSee('Silicone Valley Hostel Nyawita')
            ->assertSee('Routers 2')
            ->assertSee('MAIN-SN-001')
            ->assertSee('CHAIN-SN-002')
            ->assertSee('-25.68')
            ->assertSee('View Site')
            ->assertSee('Update Deployment');
    }

    public function test_deployments_site_workspace_uses_ont_serial_as_main_when_no_router_row_matches(): void
    {
        Config::set('inventory.router_api.enabled', true);
        Config::set('inventory.router_api.base_url', 'https://api.skybrix.co.ke/v1');
        Config::set('inventory.router_api.username', 'router-user');
        Config::set('inventory.router_api.password', 'router-secret');
        Config::set('inventory.router_api.default_page_size', 20);
        Config::set('inventory.router_api.max_page_size', 100);
        Config::set('inventory.router_api.collection_page_limit', 0);

        Http::fake([
            'https://api.skybrix.co.ke/v1/inventory/deployed-routers*' => Http::response([
                'message' => 'Deployed',
                'data' => [],
                'pagination' => ['current_page' => 1, 'per_page' => 100, 'total' => 0, 'page_count' => 1],
            ], 200),
            'https://api.skybrix.co.ke/v1/onts*' => Http::response([
                'message' => 'Sites',
                'data' => [
                    [
                        'site_id' => '420937',
                        'site_name' => 'Ariro Gas Market Guest House',
                        'serial' => '4857544312F3969A',
                        'status' => 'online',
                        'rx_power' => '-20.04',
                        'tx_power' => null,
                        'acs_last_inform' => '2026-03-14 11:20:29',
                        'olt' => 'MA5683T',
                        'slot' => '1',
                        'pon' => '10',
                    ],
                ],
                'pagination' => ['current_page' => 1, 'per_page' => 100, 'total' => 1, 'page_count' => 1],
            ], 200),
        ]);

        $user = new InventoryUser([
            'name' => 'Inventory Admin',
            'email' => 'inventory-admin-deployments-fallback@example.com',
            'inventory_role' => 'admin',
            'inventory_enabled' => true,
            'inventory_force_password_change' => false,
        ]);
        $user->id = 141;

        $response = $this
            ->actingAs($user, 'inventory')
            ->get('/inventory/deployments?section=sites&focus_site=420937');

        $response
            ->assertOk()
            ->assertSee('Ariro Gas Market Guest House')
            ->assertSee('Main 1')
            ->assertSee('Routers 1')
            ->assertSee('4857544312F3969A')
            ->assertDontSee('No main router detected yet.');
    }
}
