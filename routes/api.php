<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SmsDlrController;
use App\Http\Controllers\MegaPayController;
use App\Modules\PettyCash\Controllers\Api\V1\AuthController as PettyApiAuthController;
use App\Modules\PettyCash\Controllers\Api\V1\CreditController as PettyApiCreditController;
use App\Modules\PettyCash\Controllers\Api\V1\InsightsController as PettyApiInsightsController;
use App\Modules\PettyCash\Controllers\Api\V1\MaintenanceController as PettyApiMaintenanceController;
use App\Modules\PettyCash\Controllers\Api\V1\MasterDataController as PettyApiMasterDataController;
use App\Modules\PettyCash\Controllers\Api\V1\NotificationController as PettyApiNotificationController;
use App\Modules\PettyCash\Controllers\Api\V1\ReportController as PettyApiReportController;
use App\Modules\PettyCash\Controllers\Api\V1\SpendingController as PettyApiSpendingController;
use App\Modules\PettyCash\Controllers\Api\V1\TokenHostelController as PettyApiTokenHostelController;

Route::get('/metrics', [MetricsController::class, 'fetch']);


Route::post('/sms/advanta/dlr', [SmsDlrController::class, 'advanta']);

Route::get('/metrics', [MetricsController::class, 'fetchAll']);
Route::get('/hotspot/users', [MetricsController::class, 'listHotspotUsers']);
Route::get('/hotspot/leases', [MetricsController::class, 'listLeases']);
Route::get('/interfaces', [MetricsController::class, 'listInterfaces']);
Route::post('/hotspot/disconnect/{mac}', [MetricsController::class, 'disconnectHotspotUser']);
// api.php
Route::get('/invoices/users', [InvoiceController::class, 'users']); // DataTables feed
Route::get('/invoices/{userId}', [InvoiceController::class, 'userDetails']); // One user

Route::prefix('megapay')->group(function () {
    Route::post('/initiate', [MegaPayController::class, 'initiate']);
    Route::post('/webhook', [MegaPayController::class, 'webhook']); // add ?token=... in MegaPay dashboard
    Route::get('/status/{reference}', [MegaPayController::class, 'statusByReference']);
});

Route::prefix('petty/v1')->middleware('petty.api.meta')->group(function () {
    Route::post('/auth/login', [PettyApiAuthController::class, 'login'])
        ->middleware('throttle:petty-api-login');

    Route::middleware(['throttle:petty-api', 'petty.api.auth'])->group(function () {
        Route::get('/auth/me', [PettyApiAuthController::class, 'me']);
        Route::post('/auth/logout', [PettyApiAuthController::class, 'logout']);
        Route::post('/auth/logout-all', [PettyApiAuthController::class, 'logoutAll']);
        Route::post('/auth/refresh', [PettyApiAuthController::class, 'refresh']);
        Route::get('/auth/tokens', [PettyApiAuthController::class, 'sessions']);
        Route::delete('/auth/tokens/current', [PettyApiAuthController::class, 'logoutCurrent']);
        Route::delete('/auth/tokens/{tokenId}', [PettyApiAuthController::class, 'revokeSession'])
            ->whereNumber('tokenId');

        Route::get('/batches/available', [PettyApiTokenHostelController::class, 'availableBatches']);

        Route::get('/masters/bikes', [PettyApiMasterDataController::class, 'bikes']);
        Route::post('/masters/bikes', [PettyApiMasterDataController::class, 'storeBike']);
        Route::match(['put', 'patch'], '/masters/bikes/{bike}', [PettyApiMasterDataController::class, 'updateBike']);
        Route::delete('/masters/bikes/{bike}', [PettyApiMasterDataController::class, 'destroyBike']);

        Route::get('/masters/respondents', [PettyApiMasterDataController::class, 'respondents']);
        Route::post('/masters/respondents', [PettyApiMasterDataController::class, 'storeRespondent']);
        Route::match(['put', 'patch'], '/masters/respondents/{respondent}', [PettyApiMasterDataController::class, 'updateRespondent']);
        Route::delete('/masters/respondents/{respondent}', [PettyApiMasterDataController::class, 'destroyRespondent']);

        Route::get('/credits', [PettyApiCreditController::class, 'index']);
        Route::post('/credits', [PettyApiCreditController::class, 'store']);
        Route::get('/credits/{credit}', [PettyApiCreditController::class, 'show']);
        Route::match(['put', 'patch'], '/credits/{credit}', [PettyApiCreditController::class, 'update']);

        Route::get('/spendings', [PettyApiSpendingController::class, 'index']);
        Route::post('/spendings', [PettyApiSpendingController::class, 'store']);
        Route::get('/spendings/{spending}', [PettyApiSpendingController::class, 'show']);
        Route::match(['put', 'patch'], '/spendings/{spending}', [PettyApiSpendingController::class, 'update']);
        Route::delete('/spendings/{spending}', [PettyApiSpendingController::class, 'destroy']);

        Route::get('/maintenances/schedule', [PettyApiMaintenanceController::class, 'schedule']);
        Route::get('/maintenances/history', [PettyApiMaintenanceController::class, 'history']);
        Route::get('/maintenances/unroadworthy', [PettyApiMaintenanceController::class, 'unroadworthy']);
        Route::get('/maintenances/bikes/{bike}', [PettyApiMaintenanceController::class, 'showBike']);
        Route::post('/maintenances/bikes/{bike}/services', [PettyApiMaintenanceController::class, 'storeService']);
        Route::match(['put', 'patch'], '/maintenances/services/{service}', [PettyApiMaintenanceController::class, 'updateService']);
        Route::delete('/maintenances/services/{service}', [PettyApiMaintenanceController::class, 'destroyService']);
        Route::post('/maintenances/bikes/{bike}/unroadworthy', [PettyApiMaintenanceController::class, 'setUnroadworthy']);

        Route::get('/insights/dashboard', [PettyApiInsightsController::class, 'dashboard']);
        Route::get('/insights/ledger', [PettyApiInsightsController::class, 'ledger']);

        Route::get('/reports/lookups', [PettyApiReportController::class, 'lookups']);
        Route::get('/reports/general', [PettyApiReportController::class, 'general']);

        Route::get('/notifications', [PettyApiNotificationController::class, 'index']);
        Route::post('/notifications', [PettyApiNotificationController::class, 'store']);
        Route::post('/notifications/read-all', [PettyApiNotificationController::class, 'markAllRead']);
        Route::post('/notifications/{notification}/read', [PettyApiNotificationController::class, 'markRead']);

        Route::get('/tokens/batches/available', [PettyApiTokenHostelController::class, 'availableBatches']);
        Route::get('/tokens/onts', [PettyApiTokenHostelController::class, 'ontCatalog']);
        Route::get('/tokens/hostels', [PettyApiTokenHostelController::class, 'index']);
        Route::post('/tokens/hostels', [PettyApiTokenHostelController::class, 'storeHostel']);
        Route::get('/tokens/hostels/{hostel}', [PettyApiTokenHostelController::class, 'show']);
        Route::match(['put', 'patch'], '/tokens/hostels/{hostel}', [PettyApiTokenHostelController::class, 'updateHostel']);
        Route::post('/tokens/hostels/{hostel}/merge-ont', [PettyApiTokenHostelController::class, 'mergeHostelOnt']);
        Route::delete('/tokens/hostels/{hostel}', [PettyApiTokenHostelController::class, 'destroyHostel']);
        Route::post('/tokens/hostels/{hostel}/payments', [PettyApiTokenHostelController::class, 'storePayment']);
        Route::match(['put', 'patch'], '/tokens/payments/{payment}', [PettyApiTokenHostelController::class, 'updatePayment']);
        Route::delete('/tokens/payments/{payment}', [PettyApiTokenHostelController::class, 'destroyPayment']);
    });
});
