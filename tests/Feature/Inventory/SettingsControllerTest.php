<?php

namespace Tests\Feature\Inventory;

use App\Modules\Inventory\Models\InventoryUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_settings_and_update_user_access(): void
    {
        $admin = InventoryUser::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'inventory_role' => 'admin',
            'inventory_enabled' => true,
            'inventory_force_password_change' => false,
        ]);

        $user = InventoryUser::query()->create([
            'name' => 'Field User',
            'email' => 'field@example.com',
            'password' => Hash::make('password123'),
            'inventory_role' => 'viewer',
            'inventory_enabled' => true,
            'inventory_force_password_change' => false,
        ]);

        $this->actingAs($admin, 'inventory')
            ->get(route('inventory.settings.index', ['user' => $user->id]))
            ->assertOk()
            ->assertSee('Access Settings')
            ->assertSee('Field User');

        $this->actingAs($admin, 'inventory')
            ->put(route('inventory.settings.users.update', $user), [
                'name' => 'Field User Updated',
                'email' => 'field.updated@example.com',
                'role' => 'manager',
                'inventory_enabled' => '1',
                'inventory_force_password_change' => '1',
                'permissions' => [
                    'routers.view',
                    'deployments.view',
                    'deployments.manage',
                ],
            ])
            ->assertRedirect(route('inventory.settings.index', ['user' => $user->id]));

        $user->refresh();

        $this->assertSame('Field User Updated', $user->name);
        $this->assertSame('field.updated@example.com', $user->email);
        $this->assertSame('manager', $user->inventory_role);
        $this->assertTrue($user->inventory_enabled);
        $this->assertTrue($user->inventory_force_password_change);
        $this->assertEqualsCanonicalizing([
            'routers.view',
            'deployments.view',
            'deployments.manage',
            'profile.view',
        ], $user->inventory_permissions ?? []);
    }

    public function test_non_admin_cannot_open_settings_page(): void
    {
        $user = InventoryUser::query()->create([
            'name' => 'Viewer User',
            'email' => 'viewer@example.com',
            'password' => Hash::make('password123'),
            'inventory_role' => 'viewer',
            'inventory_permissions' => ['routers.view', 'profile.view'],
            'inventory_enabled' => true,
            'inventory_force_password_change' => false,
        ]);

        $this->actingAs($user, 'inventory')
            ->get(route('inventory.settings.index'))
            ->assertForbidden();
    }

    public function test_user_is_redirected_to_first_allowed_inventory_page_when_route_permission_is_missing(): void
    {
        Config::set('inventory.router_api.enabled', false);

        $user = InventoryUser::query()->create([
            'name' => 'Routers Only',
            'email' => 'routers@example.com',
            'password' => Hash::make('password123'),
            'inventory_role' => 'viewer',
            'inventory_permissions' => ['routers.view', 'profile.view'],
            'inventory_enabled' => true,
            'inventory_force_password_change' => false,
        ]);

        $this->actingAs($user, 'inventory')
            ->get(route('inventory.dashboard'))
            ->assertRedirect(route('inventory.routers.index'));

        $this->actingAs($user, 'inventory')
            ->get(route('inventory.routers.index'))
            ->assertOk()
            ->assertSee('Routers');
    }
}
