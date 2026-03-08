<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AppDownloadController,
    ProfileController,
    DashboardController,
    DashboardFinanceController,
    InvoiceController,
    ChatsController,
    CustomerController,
    DhcpController,
    DeviceController,
    ReportController,
    SettingController,
    ControlPanelController,
    MetricsController,
    LogsController,
    ServicesController,
    MikrotikApiController,
    MikrotikTestController,
    CaptivePortalController,
    PackageController,
    AuthenticationController,
    SuperAdminLoginController,
    UserManagementController,
    ConnectionController,
    PaymentController,
    RevenueController,
    TerminalController
};

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
Route::get('/downloads/apps/pettycash', [AppDownloadController::class, 'pettycash'])
    ->name('downloads.apps.pettycash');
Route::get('/downloads/apps/pettycash.apk', [AppDownloadController::class, 'pettycash']);

// =======================
// CONNECT (PUBLIC UI)
// =======================
Route::match(['get', 'post'], '/connect', [ConnectionController::class, 'index'])->name('connect.index');
Route::post('/connect/hotspot', [ConnectionController::class, 'connectHotspot'])->name('connect.hotspot');
Route::post('/connect/hotspot/request-payment', [ConnectionController::class, 'requestHotspotPayment'])->name('connect.hotspot.request-payment');
Route::get('/connect/hotspot/payment-status', [ConnectionController::class, 'hotspotPaymentStatus'])->name('connect.hotspot.payment-status');
Route::post('/connect/metered', [ConnectionController::class, 'connectMetered'])->name('connect.metered');
Route::get('/connect/demo', [ConnectionController::class, 'demo'])->name('connect.demo');
Route::post('/connect/demo/start', [ConnectionController::class, 'startDemo'])
    ->middleware('throttle:connect-demo-start')
    ->name('connect.demo.start');
Route::get('/connect/status/{connection}', [ConnectionController::class, 'status'])->name('connect.status');
Route::get('/connect/status/{connection}/poll', [ConnectionController::class, 'poll'])->name('connect.status.poll');
Route::get('/connect/hotspot/success/{connection}', [ConnectionController::class, 'hotspotSuccess'])->name('connect.hotspot.success');

Route::get('/connection/{connection}', [ConnectionController::class, 'status'])->name('connection.status');
Route::get('/connection/{connection}/poll', [ConnectionController::class, 'poll'])->name('connection.poll');

// PAYSTACK ROUTES
Route::get('/payment/paystack/start', [PaymentController::class, 'startPaystack'])->name('paystack.start');
Route::get('/payment/success', [PaymentController::class, 'paystackSuccess'])->name('paystack.success');
Route::post('/payment/webhook', [PaymentController::class, 'paystackWebhook'])->name('paystack.webhook');

// Public invoice payment links
Route::get('/pay/invoices/{token}', [RevenueController::class, 'publicInvoice'])->name('public.invoices.show');
Route::get('/pay/invoices/{token}/snapshot', [RevenueController::class, 'publicInvoiceSnapshot'])->name('public.invoices.snapshot');
Route::post('/pay/invoices/{token}/request-payment', [RevenueController::class, 'publicInvoiceRequestPayment'])->name('public.invoices.request-payment');
Route::get('/account/status/{token}', [CustomerController::class, 'publicStatus'])->name('public.account.status');

// =======================
// MIKROTIK PUBLIC APIs
// =======================
Route::get('/mikrotik/hotspot/active', [MikrotikApiController::class, 'hotspotActive'])->name('mikrotik.hotspot.active');
Route::get('/mikrotik/hotspot/users', [MikrotikApiController::class, 'hotspotUsers'])->name('mikrotik.hotspot.users');
Route::get('/mikrotik/hotspot/hosts', [MikrotikApiController::class, 'hotspotHosts'])->name('mikrotik.hotspot.hosts');
Route::get('/mikrotik/hotspot/cookies', [MikrotikApiController::class, 'hotspotCookies'])->name('mikrotik.hotspot.cookies');

/*
|--------------------------------------------------------------------------
| Authenticated & Verified Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/refresh', [DashboardController::class, 'refresh']);
    Route::get('/dashboard/data', [DashboardController::class, 'data']);
    Route::get('/dashboard/amount-this-month', [DashboardFinanceController::class, 'amountThisMonth'])
        ->name('dashboard.amount-this-month');


 //invoices/payments
    Route::get('/invoices', [RevenueController::class, 'index'])
    ->name('revenue.index');
    Route::get('/invoices/poll', [RevenueController::class, 'pollInvoices'])->name('invoices.poll');
    Route::get('/invoices/{invoice}/view', [RevenueController::class, 'showInvoice'])->name('invoices.view');
    Route::get('/invoices/{invoice}/print', [RevenueController::class, 'printInvoice'])->name('invoices.print');
    Route::post('/invoices/{invoice}/status', [RevenueController::class, 'updateInvoiceStatus'])->name('invoices.status');
    Route::post('/invoices/{invoice}/reminder', [RevenueController::class, 'sendInvoiceReminder'])->name('invoices.reminder');
    Route::post('/invoices/{invoice}/delete', [RevenueController::class, 'deleteInvoice'])->name('invoices.delete');
    Route::post('/invoices/request-payment', [RevenueController::class, 'requestInvoicePayment'])->name('invoices.request-payment');


    // Chats
    Route::get('/chats', [ChatsController::class, 'index'])->name('chats.index');
    Route::post('/chats/send', [ChatsController::class, 'send'])->name('chats.send');
    Route::delete('/chats/delete/{id}', [ChatsController::class, 'destroy'])->name('chats.delete');
    Route::post('/chats/{id}/retry', [ChatsController::class, 'retry'])->name('chats.retry');
    Route::get('/chats/thread/{phone}', [ChatsController::class, 'thread'])->name('chats.thread');
    Route::get('/chats/{phone}', [ChatsController::class, 'show'])->name('chats.show');

    // Customers (THIS is what your customers.index refresh uses)
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/section/{section}', [CustomerController::class, 'section'])->name('customers.section');
    Route::post('/customers/disconnect', [CustomerController::class, 'disconnect'])->name('customers.disconnect');
    Route::post('/customers/monitor-traffic', [CustomerController::class, 'monitorTraffic'])->name('customers.monitor');
    Route::get('/customers/user-details', [CustomerController::class, 'userDetails'])->name('customers.user.details');
    Route::post('/customers/user/update', [CustomerController::class, 'updateUser'])->name('customers.user.update');
    Route::post('/customers/user/create', [CustomerController::class, 'createUser'])->name('customers.user.create');
    Route::post('/customers/user/profile', [CustomerController::class, 'saveCustomerProfile'])->name('customers.user.profile');
    Route::post('/customers/user/disable', [CustomerController::class, 'disableUser'])->name('customers.user.disable');
    Route::post('/customers/user/enable', [CustomerController::class, 'enableUser'])->name('customers.user.enable');
    Route::post('/customers/user/extend-package', [CustomerController::class, 'extendPackage'])->name('customers.user.extend-package');
    Route::post('/customers/user/expire-package', [CustomerController::class, 'expirePackage'])->name('customers.user.expire-package');
    Route::post('/customers/user/billing-rate', [CustomerController::class, 'saveBillingRate'])->name('customers.user.billing-rate');
    Route::post('/customers/user/generate-invoice', [CustomerController::class, 'generateInvoice'])->name('customers.user.generate-invoice');
    Route::post('/customers/host/block', [CustomerController::class, 'blockHost'])->name('customers.host.block');
    Route::post('/customers/cookie/delete', [CustomerController::class, 'deleteCookie'])->name('customers.cookie.delete');

    // DHCP / Devices / Reports
    Route::get('/dhcp', [DhcpController::class, 'index'])->name('dhcp.index');
    Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // Logs
    Route::get('/logs', [LogsController::class, 'index'])->name('logs.index');
    Route::get('/logs/fetch', [LogsController::class, 'fetch'])->name('logs.fetch');

    // Services
    Route::get('/services', [ServicesController::class, 'index'])->name('services.index');
    Route::get('/services/create', [ServicesController::class, 'create'])->name('services.create');
    Route::post('/services', [ServicesController::class, 'store'])->name('services.store');
    Route::get('/services/{package}/edit', [ServicesController::class, 'edit'])->name('services.edit');
    Route::put('/services/{package}', [ServicesController::class, 'update'])->name('services.update');
    Route::delete('/services/{package}', [ServicesController::class, 'destroy'])->name('services.destroy');

    // Metrics / MikroTik
    Route::match(['get', 'post'], '/mikrotik/test', [MikrotikTestController::class, 'index'])->name('mikrotik.test');
    Route::get('/mikrotiks', [MetricsController::class, 'index'])->name('mikrotiks.index');
    Route::get('/metrics', [MetricsController::class, 'fetch'])->name('metrics.fetch');

    // Settings & Control Panel
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::get('/control/panel', [ControlPanelController::class, 'index'])->name('control.panel');

    // Terminal
    Route::post('/terminal/run', [TerminalController::class, 'run'])->name('terminal.run');
});

/*
|--------------------------------------------------------------------------
| User Management (Auth Only)
|--------------------------------------------------------------------------
*/
Route::prefix('control/users')->middleware(['auth'])->group(function () {
    Route::get('/', [UserManagementController::class, 'index'])->name('users.panel');
    Route::get('/{user}', [UserManagementController::class, 'show']);
    Route::post('/update/{user}', [UserManagementController::class, 'update']);
    Route::post('/toggle-login/{user}', [UserManagementController::class, 'toggleLogin']);
    Route::post('/reset-password/{user}', [UserManagementController::class, 'resetPassword']);
});

/*
|--------------------------------------------------------------------------
| Packages
|--------------------------------------------------------------------------
*/
Route::prefix('packages')->group(function () {
    Route::get('/', [PackageController::class, 'index'])->name('packages.index');
    Route::get('/create', [PackageController::class, 'create'])->name('packages.create');
    Route::post('/', [PackageController::class, 'store'])->name('packages.store');
    Route::get('/{package}/edit', [PackageController::class, 'edit'])->name('packages.edit');
    Route::put('/{package}', [PackageController::class, 'update'])->name('packages.update');
    Route::delete('/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');
});

// Admin Settings - Ads
Route::prefix('settings/ads')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [\App\Http\Controllers\AdsController::class, 'index'])->name('ads.index');
    Route::post('/store', [\App\Http\Controllers\AdsController::class, 'store'])->name('ads.store');
    Route::post('/toggle/{ad}', [\App\Http\Controllers\AdsController::class, 'toggle'])->name('ads.toggle');
    Route::delete('/delete/{ad}', [\App\Http\Controllers\AdsController::class, 'destroy'])->name('ads.destroy');
});

/*
|--------------------------------------------------------------------------
| Authentication / Super Admin
|--------------------------------------------------------------------------
*/
Route::get('/superadmin/login', [SuperAdminLoginController::class, 'showLoginForm'])->name('superadmin.login');
Route::post('/superadmin/login', [SuperAdminLoginController::class, 'login'])->name('superadmin.login.post');
Route::post('/superadmin/logout', [SuperAdminLoginController::class, 'logout'])->name('superadmin.logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/authentication', [AuthenticationController::class, 'index'])->name('authentication');
    Route::post('/authentication/{user}/verify', [AuthenticationController::class, 'verify'])->name('authentication.verify');
    Route::delete('/authentication/{user}/delete', [AuthenticationController::class, 'destroy'])->name('authentication.delete');
});

/*
|--------------------------------------------------------------------------
| Profile
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit']);
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::delete('/profile', [ProfileController::class, 'destroy']);
});

require __DIR__ . '/auth.php';
