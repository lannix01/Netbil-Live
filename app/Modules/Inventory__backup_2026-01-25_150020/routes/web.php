<?php

use Illuminate\Support\Facades\Route;

use App\Modules\Inventory\Http\Controllers\Inventory\ItemGroupController;
use App\Modules\Inventory\Http\Controllers\Inventory\ItemController;
use App\Modules\Inventory\Http\Controllers\Inventory\StockReceiptController;
use App\Modules\Inventory\Http\Controllers\Inventory\AssignmentController;
use App\Modules\Inventory\Http\Controllers\Inventory\DeploymentController;
use App\Modules\Inventory\Http\Controllers\Inventory\LogController;
use App\Modules\Inventory\Http\Controllers\Inventory\MovementController;
use App\Modules\Inventory\Http\Controllers\Inventory\TechnicianInventoryController;
use App\Modules\Inventory\Http\Controllers\Inventory\AdminDeploymentController;

use App\Modules\Inventory\Http\Controllers\Inventory\TeamController;
use App\Modules\Inventory\Http\Controllers\Inventory\TeamAssignmentController;
use App\Modules\Inventory\Http\Controllers\Inventory\TeamDeploymentController;

Route::middleware(['web', 'auth'])
    ->prefix('inventory')
    ->name('inventory.')
    ->group(function () {

        // Admin
        Route::middleware(['can:inventory.manage'])->group(function () {

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

            Route::get('admin/deploy', [AdminDeploymentController::class, 'create'])->name('admin.deploy.create');

            Route::get('alerts/low-stock', [ItemController::class, 'lowStock'])->name('alerts.low_stock');

            Route::get('logs', [LogController::class, 'index'])->name('logs.index');

            Route::get('movements', [MovementController::class, 'index'])->name('movements.index');
            Route::get('movements/transfer', [MovementController::class, 'transferForm'])->name('movements.transfer');
            Route::get('movements/return-to-store', [MovementController::class, 'returnToStoreForm'])->name('movements.return_to_store');
            Route::post('movements', [MovementController::class, 'store'])->name('movements.store');

            // Teams
            Route::get('teams', [TeamController::class, 'index'])->name('teams.index');
            Route::get('teams/create', [TeamController::class, 'create'])->name('teams.create');
            Route::post('teams', [TeamController::class, 'store'])->name('teams.store');
            Route::get('teams/{team}/edit', [TeamController::class, 'edit'])->name('teams.edit');
            Route::put('teams/{team}', [TeamController::class, 'update'])->name('teams.update');
            Route::delete('teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
            Route::post('teams/{team}/members', [TeamController::class, 'addMember'])->name('teams.members.store');
            Route::delete('teams/{team}/members/{member}', [TeamController::class, 'removeMember'])->name('teams.members.destroy');

            // Team assignment + deployment (bulk)
            Route::get('team-assignments', [TeamAssignmentController::class, 'index'])->name('team_assignments.index');
            Route::post('team-assignments', [TeamAssignmentController::class, 'store'])->name('team_assignments.store');

            Route::get('team-deployments', [TeamDeploymentController::class, 'index'])->name('team_deployments.index');
            Route::post('team-deployments', [TeamDeploymentController::class, 'store'])->name('team_deployments.store');
        });

        // Technician view (assigned monitoring)
        Route::middleware(['can:inventory.viewAssigned'])->group(function () {
            Route::get('my/items', [TechnicianInventoryController::class, 'index'])->name('tech.items.index');
            Route::get('my/items/{item}', [TechnicianInventoryController::class, 'show'])->name('tech.items.show');
        });

        // Deploy (technicians + admin)
        Route::middleware(['can:inventory.deploy'])->group(function () {
            Route::get('deployments', [DeploymentController::class, 'index'])->name('deployments.index');
            Route::post('deployments', [DeploymentController::class, 'store'])->name('deployments.store');
        });

    });
