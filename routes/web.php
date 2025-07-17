<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    ProfileController,
    DashboardController,
    InvoiceController,
    ChatController,
    ChatsController,
    CustomerController,
    DhcpController,
    DeviceController,
    ReportController,
    SettingController,
    ControlPanelController,
    MetricsController,
    LogsController,
    MikrotikTestController
};

// Public Route
Route::get('/', function () {
    return view('welcome');
});

// Authenticated & Verified Routes
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/refresh', [DashboardController::class, 'refresh'])->name('dashboard.refresh');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');

    // Customers
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/section/{section}', [CustomerController::class, 'section'])->name('customers.section');
    Route::post('/customers/disconnect', [CustomerController::class, 'disconnect'])->name('customers.disconnect');
    Route::post('/customers/monitor-traffic', [CustomerController::class, 'monitorTraffic'])->name('customers.monitor');

    // DHCP, Devices, Reports
    Route::get('/dhcp', [DhcpController::class, 'index'])->name('dhcp.index');
    Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // Chats
    Route::get('/chats', [ChatsController::class, 'index'])->name('chats.index');
    Route::post('/chats/send', [ChatsController::class, 'send'])->name('chats.send');
    Route::delete('/chats/delete/{id}', [ChatsController::class, 'destroy'])->name('chats.delete');
    Route::get('/chats/{phone}', [ChatsController::class, 'show'])->name('chats.show');

    // Logs
    Route::get('/logs', [LogsController::class, 'index'])->name('logs.index');

    // Settings & Control Panel
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::get('/control/panel', [ControlPanelController::class, 'index'])->name('control.panel');

    // MikroTik / Metrics
    Route::get('/mikrotik/test', [MikrotikTestController::class, 'index'])->name('mikrotik.test');
    Route::get('/mikrotiks', [MetricsController::class, 'index'])->name('mikrotiks.index');
    Route::get('/metrics', [MetricsController::class, 'fetch'])->name('metrics.fetch');
});

// Profile Routes (auth only, not verified)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Auth routes (Jetstream/Breeze/etc.)
require __DIR__ . '/auth.php';