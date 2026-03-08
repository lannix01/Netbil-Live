<?php

namespace Modules\Inventory\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Policies\InventoryPolicy;

class InventoryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        Gate::define('inventory.admin', [InventoryPolicy::class, 'admin']);
        Gate::define('inventory.technician', [InventoryPolicy::class, 'technician']);

        Gate::define('inventory.manage', [InventoryPolicy::class, 'manage']);                 // admin only
        Gate::define('inventory.viewAssigned', [InventoryPolicy::class, 'viewAssigned']);     // tech + admin
        Gate::define('inventory.deploy', [InventoryPolicy::class, 'deploy']);                 // tech + admin
    }

    public function register(): void
    {
        //
    }
}
