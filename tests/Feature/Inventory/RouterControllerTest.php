<?php

namespace Tests\Feature\Inventory;

use App\Modules\Inventory\Models\InventoryUser;
use App\Modules\Inventory\Support\InventoryAccess;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RouterControllerTest extends TestCase
{
    public function test_admin_can_query_in_stock_router_data_from_skybrix(): void
    {
        Config::set('inventory.router_api.enabled', true);
        Config::set('inventory.router_api.base_url', 'https://api.skybrix.co.ke/v1');
        Config::set('inventory.router_api.username', 'router-user');
        Config::set('inventory.router_api.password', 'router-secret');
        Config::set('inventory.router_api.default_page_size', 20);
        Config::set('inventory.router_api.max_page_size', 100);

        Http::fake([
            'https://api.skybrix.co.ke/v1/inventory/in-stock-routers*' => Http::response([
                'status' => 'success',
                'message' => 'Request processed successfully',
                'data' => [
                    [
                        'id' => '72',
                        'serial_number' => '485754434642369C',
                        'mac_address' => '485754434642369C',
                        'batch_id' => '4',
                        'batch_number' => 'BATCH-20260102095754',
                        'brand' => 'Tenda',
                        'model_number' => 'F3 N300 Generic',
                        'description' => '',
                        'status' => 'working',
                        'location_name' => 'STORE/OFFICE',
                        'created_at' => '2026-01-02 10:03:34',
                    ],
                ],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 20,
                    'total' => 61,
                    'page_count' => 4,
                ],
                'error' => null,
            ], 200),
        ]);

        $user = new InventoryUser([
            'name' => 'Inventory Admin',
            'email' => 'inventory-admin@example.com',
            'inventory_role' => 'admin',
            'inventory_enabled' => true,
            'inventory_force_password_change' => false,
        ]);
        $user->id = 101;

        $response = $this
            ->actingAs($user, 'inventory')
            ->get('/inventory/routers?section=in-stock&search=Tenda&batch_number=BATCH-20260102095754&page_size=20');

        $response
            ->assertOk()
            ->assertSee('Routers')
            ->assertSee('In Stock')
            ->assertSee('485754434642369C')
            ->assertSee('BATCH-20260102095754')
            ->assertSee('STORE/OFFICE');

        Http::assertSent(function ($request) {
            $query = [];
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            return $request->hasHeader('Authorization')
                && $request->method() === 'GET'
                && $request->url() !== ''
                && str_starts_with($request->url(), 'https://api.skybrix.co.ke/v1/inventory/in-stock-routers')
                && ($query['page'] ?? null) === '1'
                && ($query['page_size'] ?? null) === '20'
                && ($query['search'] ?? null) === 'Tenda'
                && ($query['batch_number'] ?? null) === 'BATCH-20260102095754';
        });
    }

    public function test_router_page_shows_configuration_message_when_credentials_are_missing(): void
    {
        Config::set('inventory.router_api.enabled', true);
        Config::set('inventory.router_api.username', '');
        Config::set('inventory.router_api.password', '');

        $user = new InventoryUser([
            'name' => 'Inventory Admin',
            'email' => 'inventory-admin-2@example.com',
            'inventory_role' => 'admin',
            'inventory_enabled' => true,
            'inventory_force_password_change' => false,
        ]);
        $user->id = 102;

        $response = $this
            ->actingAs($user, 'inventory')
            ->get('/inventory/routers');

        $response
            ->assertOk()
            ->assertSee('Skybrix query unavailable')
            ->assertSee('Skybrix router API credentials are not configured.');

        Http::assertNothingSent();
    }

    public function test_batch_workspace_aggregates_multiple_pages_without_repeating_page_one(): void
    {
        Config::set('inventory.router_api.enabled', true);
        Config::set('inventory.router_api.base_url', 'https://api.skybrix.co.ke/v1');
        Config::set('inventory.router_api.username', 'router-user');
        Config::set('inventory.router_api.password', 'router-secret');
        Config::set('inventory.router_api.default_page_size', 20);
        Config::set('inventory.router_api.max_page_size', 100);
        Config::set('inventory.router_api.collection_page_limit', 0);

        Http::fake(function ($request) {
            $query = [];
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);
            $page = (int) ($query['page'] ?? 1);

            if (str_starts_with($request->url(), 'https://api.skybrix.co.ke/v1/inventory/in-stock-routers')) {
                return Http::response([
                    'message' => 'In stock',
                    'data' => $page === 1
                        ? [[
                            'serial_number' => 'SN-001',
                            'batch_number' => 'BATCH-AGG-001',
                            'brand' => 'Huawei',
                            'model_number' => 'HG8546M',
                            'location_name' => 'STORE/OFFICE',
                            'status' => 'working',
                            'created_at' => '2026-03-14 09:00:00',
                        ]]
                        : [[
                            'serial_number' => 'SN-002',
                            'batch_number' => 'BATCH-AGG-001',
                            'brand' => 'Huawei',
                            'model_number' => 'HG8546M',
                            'location_name' => 'STORE/OFFICE',
                            'status' => 'working',
                            'created_at' => '2026-03-14 09:05:00',
                        ]],
                    'pagination' => ['current_page' => $page, 'per_page' => 100, 'total' => 2, 'page_count' => 2],
                ], 200);
            }

            return Http::response([
                'message' => 'Empty',
                'data' => [],
                'pagination' => ['current_page' => 1, 'per_page' => 100, 'total' => 0, 'page_count' => 1],
            ], 200);
        });

        $user = new InventoryUser([
            'name' => 'Inventory Admin',
            'email' => 'inventory-admin-pages@example.com',
            'inventory_role' => 'admin',
            'inventory_enabled' => true,
            'inventory_force_password_change' => false,
        ]);
        $user->id = 120;

        $response = $this
            ->actingAs($user, 'inventory')
            ->get('/inventory/routers?section=batches');

        $response
            ->assertOk()
            ->assertViewHas('routerPaginator', function ($paginator) {
                $items = $paginator->items();
                if ($items === [] || !is_array($items[0] ?? null)) {
                    return false;
                }

                return ($items[0]['batch_number'] ?? null) === 'BATCH-AGG-001'
                    && ($items[0]['quantity'] ?? null) === 2
                    && ($items[0]['available_count'] ?? null) === 2;
            });
    }

    public function test_batch_workspace_groups_router_states_by_batch(): void
    {
        Config::set('inventory.router_api.enabled', true);
        Config::set('inventory.router_api.base_url', 'https://api.skybrix.co.ke/v1');
        Config::set('inventory.router_api.username', 'router-user');
        Config::set('inventory.router_api.password', 'router-secret');
        Config::set('inventory.router_api.default_page_size', 20);
        Config::set('inventory.router_api.max_page_size', 100);

        Http::fake([
            'https://api.skybrix.co.ke/v1/inventory/in-stock-routers*' => Http::response([
                'message' => 'In stock',
                'data' => [
                    [
                        'serial_number' => 'SN-001',
                        'batch_number' => 'BATCH-20260314090612',
                        'brand' => 'Huawei',
                        'model_number' => 'HG8546M',
                        'location_name' => 'STORE/OFFICE',
                        'status' => 'working',
                        'created_at' => '2026-03-13 09:00:00',
                    ],
                ],
                'pagination' => ['current_page' => 1, 'per_page' => 100, 'total' => 1, 'page_count' => 1],
            ], 200),
            'https://api.skybrix.co.ke/v1/inventory/undeployed-routers*' => Http::response([
                'message' => 'With techs',
                'data' => [
                    [
                        'serial_number' => 'SN-002',
                        'batch_number' => 'BATCH-20260314090612',
                        'brand' => 'Huawei',
                        'model_number' => 'HG8546M',
                        'technician_name' => 'Nicholas Mutua',
                        'assigned_date' => '2026-03-13 11:30:00',
                    ],
                ],
                'pagination' => ['current_page' => 1, 'per_page' => 100, 'total' => 1, 'page_count' => 1],
            ], 200),
            'https://api.skybrix.co.ke/v1/inventory/deployed-routers*' => Http::response([
                'message' => 'Deployed',
                'data' => [
                    [
                        'serial_number' => 'SN-003',
                        'batch_number' => 'BATCH-20260314090612',
                        'brand' => 'Huawei',
                        'model_number' => 'HG8546M',
                        'site_id' => '421378',
                        'site_name' => 'Westlands POP',
                        'status' => 'working',
                        'router_status' => 'online',
                        'installed_date' => '2026-03-14 08:30:00',
                        'is_primary' => '1',
                    ],
                ],
                'pagination' => ['current_page' => 1, 'per_page' => 100, 'total' => 1, 'page_count' => 1],
            ], 200),
            'https://api.skybrix.co.ke/v1/inventory/faulty-routers*' => Http::response([
                'message' => 'Faulty',
                'data' => [
                    [
                        'serial_number' => 'SN-004',
                        'batch_number' => 'BATCH-20260314090612',
                        'brand' => 'Huawei',
                        'model_number' => 'HG8546M',
                        'status' => 'faulty',
                        'updated_at' => '2026-03-14 09:00:00',
                    ],
                ],
                'pagination' => ['current_page' => 1, 'per_page' => 100, 'total' => 1, 'page_count' => 1],
            ], 200),
        ]);

        $user = new InventoryUser([
            'name' => 'Inventory Admin',
            'email' => 'inventory-admin-3@example.com',
            'inventory_role' => 'admin',
            'inventory_enabled' => true,
            'inventory_force_password_change' => false,
        ]);
        $user->id = 103;

        $response = $this
            ->actingAs($user, 'inventory')
            ->get('/inventory/routers?section=batches&focus_batch=BATCH-20260314090612');

        $response
            ->assertOk()
            ->assertSee('Stock Batches')
            ->assertSee('BATCH-20260314090612')
            ->assertSee('Nicholas Mutua')
            ->assertSee('Westlands POP')
            ->assertSee('Faulty 1');
    }

    public function test_batch_workspace_recovers_missing_batch_counts_from_batch_search(): void
    {
        Config::set('inventory.router_api.enabled', true);
        Config::set('inventory.router_api.base_url', 'https://api.skybrix.co.ke/v1');
        Config::set('inventory.router_api.username', 'router-user');
        Config::set('inventory.router_api.password', 'router-secret');
        Config::set('inventory.router_api.default_page_size', 20);
        Config::set('inventory.router_api.max_page_size', 100);

        Http::fake(function ($request) {
            $query = [];
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);
            $search = (string) ($query['search'] ?? '');

            if (str_starts_with($request->url(), 'https://api.skybrix.co.ke/v1/inventory/in-stock-routers')) {
                return Http::response([
                    'message' => 'In stock',
                    'data' => [
                        [
                            'serial_number' => 'IN-SEARCH-001',
                            'batch_number' => 'BATCH-SEARCH-001',
                            'brand' => 'Huawei',
                            'model_number' => 'HG8546M',
                            'location_name' => 'STORE/OFFICE',
                            'status' => 'working',
                            'created_at' => '2026-03-14 09:00:00',
                        ],
                    ],
                    'pagination' => ['current_page' => 1, 'per_page' => 100, 'total' => 1, 'page_count' => 1],
                ], 200);
            }

            if (str_starts_with($request->url(), 'https://api.skybrix.co.ke/v1/inventory/undeployed-routers')) {
                return Http::response([
                    'message' => 'With techs',
                    'data' => $search === 'BATCH-SEARCH-001'
                        ? [
                            [
                                'serial_number' => 'TECH-001',
                                'brand' => 'Huawei',
                                'model_number' => 'HG8546M',
                                'technician_name' => 'Tech Alpha',
                                'assigned_date' => '2026-03-14 10:00:00',
                            ],
                            [
                                'serial_number' => 'TECH-002',
                                'brand' => 'Huawei',
                                'model_number' => 'HG8546M',
                                'technician_name' => 'Tech Beta',
                                'assigned_date' => '2026-03-14 10:30:00',
                            ],
                        ]
                        : [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => 100,
                        'total' => $search === 'BATCH-SEARCH-001' ? 2 : 0,
                        'page_count' => 1,
                    ],
                ], 200);
            }

            if (str_starts_with($request->url(), 'https://api.skybrix.co.ke/v1/inventory/deployed-routers')) {
                return Http::response([
                    'message' => 'Deployed',
                    'data' => $search === 'BATCH-SEARCH-001'
                        ? [
                            [
                                'serial_number' => 'DEP-001',
                                'brand' => 'Huawei',
                                'model_number' => 'HG8546M',
                                'site_id' => '421001',
                                'site_name' => 'Site One',
                                'installed_date' => '2026-03-14 11:00:00',
                                'is_primary' => '1',
                            ],
                            [
                                'serial_number' => 'DEP-002',
                                'brand' => 'Huawei',
                                'model_number' => 'HG8546M',
                                'site_id' => '421002',
                                'site_name' => 'Site Two',
                                'installed_date' => '2026-03-14 11:10:00',
                                'is_primary' => '0',
                            ],
                            [
                                'serial_number' => 'DEP-003',
                                'brand' => 'Huawei',
                                'model_number' => 'HG8546M',
                                'site_id' => '421001',
                                'site_name' => 'Site One',
                                'installed_date' => '2026-03-14 11:20:00',
                                'is_primary' => '0',
                            ],
                        ]
                        : [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => 100,
                        'total' => $search === 'BATCH-SEARCH-001' ? 3 : 0,
                        'page_count' => 1,
                    ],
                ], 200);
            }

            return Http::response([
                'message' => 'Empty',
                'data' => [],
                'pagination' => ['current_page' => 1, 'per_page' => 100, 'total' => 0, 'page_count' => 1],
            ], 200);
        });

        $user = new InventoryUser([
            'name' => 'Inventory Admin',
            'email' => 'inventory-admin-search@example.com',
            'inventory_role' => 'admin',
            'inventory_enabled' => true,
            'inventory_force_password_change' => false,
        ]);
        $user->id = 105;

        $response = $this
            ->actingAs($user, 'inventory')
            ->get('/inventory/routers?section=batches&focus_batch=BATCH-SEARCH-001');

        $response
            ->assertOk()
            ->assertSee('BATCH-SEARCH-001')
            ->assertSee('With Techs 2')
            ->assertSee('Deployed 3')
            ->assertSee('Tech Alpha')
            ->assertSee('Site One')
            ->assertDontSee('0 techs • 0 sites')
            ->assertViewHas('routerPaginator', function ($paginator) {
                $items = $paginator->items();
                if ($items === [] || !is_array($items[0] ?? null)) {
                    return false;
                }

                return ($items[0]['batch_number'] ?? null) === 'BATCH-SEARCH-001'
                    && ($items[0]['with_techs_count'] ?? null) === 2
                    && ($items[0]['deployed_count'] ?? null) === 3
                    && ($items[0]['quantity'] ?? null) === 6;
            });
    }

    public function test_deployed_router_tab_shows_flat_api_rows(): void
    {
        Config::set('inventory.router_api.enabled', true);
        Config::set('inventory.router_api.base_url', 'https://api.skybrix.co.ke/v1');
        Config::set('inventory.router_api.username', 'router-user');
        Config::set('inventory.router_api.password', 'router-secret');
        Config::set('inventory.router_api.default_page_size', 20);
        Config::set('inventory.router_api.max_page_size', 100);

        Http::fake([
            'https://api.skybrix.co.ke/v1/inventory/deployed-routers*' => Http::response([
                'message' => 'Deployed',
                'data' => [
                    [
                        'serial_number' => 'MAIN-SN-001',
                        'batch_number' => 'BATCH-001',
                        'brand' => 'Huawei',
                        'model_number' => 'HG8546M',
                        'mac_address' => 'MAC-001',
                        'site_id' => '421378',
                        'site_name' => 'Westlands POP',
                        'status' => 'working',
                        'router_status' => 'online',
                        'installed_date' => '2026-03-14 08:30:00',
                        'is_primary' => '1',
                    ],
                    [
                        'serial_number' => 'CHAIN-SN-002',
                        'batch_number' => 'BATCH-002',
                        'brand' => 'Tenda',
                        'model_number' => 'F3',
                        'mac_address' => 'MAC-002',
                        'site_id' => '421378',
                        'site_name' => 'Westlands POP',
                        'status' => 'working',
                        'router_status' => 'online',
                        'installed_date' => '2026-03-14 09:15:00',
                        'is_primary' => '0',
                    ],
                ],
                'pagination' => ['current_page' => 1, 'per_page' => 100, 'total' => 2, 'page_count' => 1],
            ], 200),
        ]);

        $user = new InventoryUser([
            'name' => 'Inventory Admin',
            'email' => 'inventory-admin-4@example.com',
            'inventory_role' => 'admin',
            'inventory_enabled' => true,
            'inventory_force_password_change' => false,
        ]);
        $user->id = 104;

        $response = $this
            ->actingAs($user, 'inventory')
            ->get('/inventory/routers?section=deployed&site_id=421378');

        $response
            ->assertOk()
            ->assertSee('Deployed Routers')
            ->assertSee('Westlands POP')
            ->assertSee('421378')
            ->assertSee('MAIN-SN-001')
            ->assertSee('CHAIN-SN-002')
            ->assertSee('BATCH-001')
            ->assertSee('Main')
            ->assertSee('Working')
            ->assertSee('Online');

        Http::assertSentCount(1);
    }

    public function test_technician_router_page_only_shows_their_assigned_routers(): void
    {
        InventoryAccess::forgetResolvedPermissions();
        Schema::shouldReceive('hasTable')->andReturn(false);

        Config::set('inventory.router_api.enabled', true);
        Config::set('inventory.router_api.base_url', 'https://api.skybrix.co.ke/v1');
        Config::set('inventory.router_api.username', 'router-user');
        Config::set('inventory.router_api.password', 'router-secret');
        Config::set('inventory.router_api.default_page_size', 20);
        Config::set('inventory.router_api.max_page_size', 100);

        Http::fake([
            'https://api.skybrix.co.ke/v1/inventory/undeployed-routers*' => Http::response([
                'message' => 'With techs',
                'data' => [
                    [
                        'serial_number' => 'TECH-SN-001',
                        'batch_number' => 'BATCH-TECH-001',
                        'brand' => 'Huawei',
                        'model_number' => 'HG8546M',
                        'technician_id' => '6',
                        'technician_name' => 'Okodoi Technician',
                        'assigned_date' => '2026-03-14 13:00:00',
                        'expected_deployment_date' => '2026-03-15 09:00:00',
                        'urgency' => 'scheduled',
                        'days_assigned' => '1',
                    ],
                ],
                'pagination' => ['current_page' => 1, 'per_page' => 100, 'total' => 1, 'page_count' => 1],
            ], 200),
        ]);

        $user = new InventoryUser([
            'name' => 'Okodoi Technician',
            'email' => 'okodoi-tech@example.com',
            'inventory_role' => 'technician',
            'inventory_enabled' => true,
            'inventory_force_password_change' => false,
        ]);
        $user->id = 6;

        $response = $this
            ->actingAs($user, 'inventory')
            ->get('/inventory/routers?section=deployed&search=HG8546');

        $response
            ->assertOk()
            ->assertSee('My Assigned Routers')
            ->assertSee('TECH-SN-001')
            ->assertSee('BATCH-TECH-001')
            ->assertSee('2026-03-14 13:00:00')
            ->assertDontSee('Stock Batches')
            ->assertDontSee('Admin Deploy');

        Http::assertSent(function ($request) {
            $query = [];
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            return str_starts_with($request->url(), 'https://api.skybrix.co.ke/v1/inventory/undeployed-routers')
                && ($query['technician_id'] ?? null) === '6'
                && !array_key_exists('search', $query);
        });

        Http::assertSentCount(1);
    }

    public function test_technician_router_page_falls_back_to_identity_match_when_skybrix_ids_differ(): void
    {
        InventoryAccess::forgetResolvedPermissions();
        Schema::shouldReceive('hasTable')->andReturn(false);

        Config::set('inventory.router_api.enabled', true);
        Config::set('inventory.router_api.base_url', 'https://api.skybrix.co.ke/v1');
        Config::set('inventory.router_api.username', 'router-user');
        Config::set('inventory.router_api.password', 'router-secret');
        Config::set('inventory.router_api.default_page_size', 20);
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
                            'serial_number' => 'TECH-SN-024',
                            'batch_number' => 'BATCH-TECH-024',
                            'brand' => 'Huawei',
                            'model_number' => 'HG8546M',
                            'technician_id' => '24',
                            'technician_name' => 'Shaddrack Okodoi',
                            'technician_email' => 'shadrackkdoi@gmail.com',
                            'technician_phone' => '0795495912',
                            'assigned_date' => '2026-03-14 13:00:00',
                            'expected_deployment_date' => '2026-03-15 09:00:00',
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
            ->get('/inventory/routers?section=with-techs&search=HG8546');

        $response
            ->assertOk()
            ->assertSee('My Assigned Routers')
            ->assertSee('TECH-SN-024')
            ->assertSee('BATCH-TECH-024')
            ->assertSee('Overdue');

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
}
