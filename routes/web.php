<?php

use Illuminate\Support\Facades\Route;

use App\Modules\Inventory\Http\Controllers\Auth\InventoryAuthController;
use App\Modules\Inventory\Http\Controllers\Auth\InventoryPasswordController;

use App\Modules\Inventory\Http\Controllers\Inventory\InventoryDashboardController;
use App\Modules\Inventory\Http\Controllers\Inventory\ItemGroupController;
use App\Modules\Inventory\Http\Controllers\Inventory\ItemController;
use App\Modules\Inventory\Http\Controllers\Inventory\StockReceiptController;
use App\Modules\Inventory\Http\Controllers\Inventory\AssignmentController;
use App\Modules\Inventory\Http\Controllers\Inventory\DeploymentController;
use App\Modules\Inventory\Http\Controllers\Inventory\LogController;
use App\Modules\Inventory\Http\Controllers\Inventory\MovementController;
use App\Modules\Inventory\Http\Controllers\Inventory\TeamController;
use App\Modules\Inventory\Http\Controllers\Inventory\TeamAssignmentController;
use App\Modules\Inventory\Http\Controllers\Inventory\TeamDeploymentController;
use App\Modules\Inventory\Http\Controllers\Inventory\AdminDeploymentController;
use App\Modules\Inventory\Http\Controllers\Inventory\AlertsController;
use App\Modules\Inventory\Http\Controllers\Inventory\RouterController;
use App\Modules\Inventory\Http\Controllers\Inventory\SettingsController;
use App\Modules\Inventory\Http\Controllers\Inventory\TechnicianInventoryController;

Route::middleware(['web'])
    ->prefix('inventory')
    ->name('inventory.')
    ->group(function () {

        // Auth
        Route::get('login', [InventoryAuthController::class, 'showLogin'])->name('auth.login');
        Route::post('login', [InventoryAuthController::class, 'login'])->name('auth.login.submit');
        Route::post('logout', [InventoryAuthController::class, 'logout'])->name('auth.logout');
        Route::get('switch/technician', [InventoryAuthController::class, 'switchToTechnician'])->name('auth.switch.tech');

        // Password change (must be reachable even when force_password_change triggers)
        Route::middleware(['inventory.auth'])->group(function () {
            Route::get('password/change', [InventoryPasswordController::class, 'showChange'])->name('auth.password.change');
            Route::post('password/change', [InventoryPasswordController::class, 'update'])->name('auth.password.update');
        });

        // Protected Inventory app
        Route::middleware([
            'inventory.auth',
            'inventory.force_password_change',
            'inventory.permission',
            'inventory.activity',
        ])->group(function () {

            Route::prefix('tech')
                ->name('tech.')
                ->group(function () {
                    Route::get('dashboard', [TechnicianInventoryController::class, 'index'])->name('dashboard');
                    Route::get('items', [TechnicianInventoryController::class, 'index'])->name('items.index');
                    Route::get('items/{item}', [TechnicianInventoryController::class, 'show'])->name('items.show');
                    Route::get('sites/lookup', [TechnicianInventoryController::class, 'siteLookup'])->name('sites.lookup');
                });

            Route::post('deployments', [DeploymentController::class, 'store'])->name('deployments.store');

            Route::middleware(['inventory.role:admin'])->prefix('settings')->name('settings.')->group(function () {
                Route::get('/', [SettingsController::class, 'index'])->name('index');
                Route::post('users', [SettingsController::class, 'storeUser'])->name('users.store');
                Route::put('users/{user}', [SettingsController::class, 'updateUser'])->name('users.update');
                Route::post('users/{user}/send-login-sms', [SettingsController::class, 'sendLoginSms'])->name('users.send_login_sms');
                Route::post('users/{user}/reset-login', [SettingsController::class, 'resetLogin'])->name('users.reset_login');
            });

            // Dashboard home
            Route::get('/', [InventoryDashboardController::class, 'index'])->name('dashboard');
            Route::get('dashboard', [InventoryDashboardController::class, 'index'])->name('dashboard.alt');
            Route::get('routers', [RouterController::class, 'index'])->name('routers.index');

            // Alerts
            Route::prefix('alerts')->name('alerts.')->group(function () {
                Route::get('low-stock', [AlertsController::class, 'lowStock'])->name('low_stock');
            });

            Route::resource('item-groups', ItemGroupController::class);
            Route::resource('items', ItemController::class);

            Route::get('receipts', [StockReceiptController::class, 'index'])->name('receipts.index');
            Route::get('receipts/create', [StockReceiptController::class, 'create'])->name('receipts.create');
            Route::post('receipts', [StockReceiptController::class, 'store'])->name('receipts.store');
            Route::get('receipts/{receipt}', [StockReceiptController::class, 'show'])->name('receipts.show');

            Route::get('assignments', [AssignmentController::class, 'index'])->name('assignments.index');
            Route::post('assignments', [AssignmentController::class, 'store'])->name('assignments.store');
            Route::patch('assignments/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');
            Route::delete('assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');

            Route::get('deployments', [DeploymentController::class, 'index'])->name('deployments.index');
            Route::get('admin/deploy', [AdminDeploymentController::class, 'create'])->name('admin.deploy.create');

            Route::get('logs', [LogController::class, 'index'])->name('logs.index');

            Route::get('movements', [MovementController::class, 'index'])->name('movements.index');
            Route::get('movements/transfer', [MovementController::class, 'transferForm'])->name('movements.transfer');
            Route::get('movements/return-to-store', [MovementController::class, 'returnToStoreForm'])->name('movements.return_to_store');
            Route::post('movements', [MovementController::class, 'store'])->name('movements.store');

            Route::get('teams', [TeamController::class, 'index'])->name('teams.index');
            Route::get('teams/create', [TeamController::class, 'create'])->name('teams.create');
            Route::post('teams', [TeamController::class, 'store'])->name('teams.store');
            Route::get('teams/{team}/edit', [TeamController::class, 'edit'])->name('teams.edit');
            Route::put('teams/{team}', [TeamController::class, 'update'])->name('teams.update');
            Route::delete('teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
            Route::post('teams/{team}/members', [TeamController::class, 'addMember'])->name('teams.members.store');
            Route::delete('teams/{team}/members/{member}', [TeamController::class, 'removeMember'])->name('teams.members.destroy');

            Route::get('team-assignments', [TeamAssignmentController::class, 'index'])->name('team_assignments.index');
            Route::post('team-assignments', [TeamAssignmentController::class, 'store'])->name('team_assignments.store');

            Route::get('team-deployments', [TeamDeploymentController::class, 'index'])->name('team_deployments.index');
            Route::post('team-deployments', [TeamDeploymentController::class, 'store'])->name('team_deployments.store');
        });
    });
