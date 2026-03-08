<?php

use Illuminate\Support\Facades\Route;
use App\Modules\PettyCash\Controllers\DashboardController;
use App\Modules\PettyCash\Controllers\CreditController;
use App\Modules\PettyCash\Controllers\BatchController;
use App\Modules\PettyCash\Controllers\Spending\BikeController;
use App\Modules\PettyCash\Controllers\Spending\MealController;
use App\Modules\PettyCash\Controllers\Spending\TokenController;
use App\Modules\PettyCash\Controllers\Spending\OtherController;
use App\Modules\PettyCash\Controllers\ReportsController;
use App\Modules\PettyCash\Controllers\BikeMasterController;
use App\Modules\PettyCash\Controllers\RespondentController;
use App\Modules\PettyCash\Controllers\MaintenancesController;
use App\Modules\PettyCash\Controllers\LedgerController;
use App\Modules\PettyCash\Controllers\NotificationsController;
use App\Modules\PettyCash\Controllers\ProfileController;
use App\Modules\PettyCash\Controllers\SettingsController;







Route::get('/', [DashboardController::class, 'index'])->name('petty.dashboard');
// Notifications
Route::get('/notifications', [NotificationsController::class, 'index'])->name('petty.notifications.index');
Route::post('/notifications/mark-all-read', [NotificationsController::class, 'markAllRead'])->name('petty.notifications.read_all');
Route::post('/notifications/{notification}/read', [NotificationsController::class, 'markRead'])->name('petty.notifications.read_one');
Route::post('/notifications/admin-contacts', [NotificationsController::class, 'storeAdminContact'])->name('petty.notifications.admin_contacts.store');
Route::delete('/notifications/admin-contacts/{contact}', [NotificationsController::class, 'destroyAdminContact'])->name('petty.notifications.admin_contacts.destroy');
Route::post('/notifications/sms-templates', [NotificationsController::class, 'storeSmsTemplate'])->name('petty.notifications.sms_templates.store');
Route::delete('/notifications/sms-templates/{template}', [NotificationsController::class, 'destroySmsTemplate'])->name('petty.notifications.sms_templates.destroy');
Route::post('/notifications/sms-settings', [NotificationsController::class, 'saveSmsSettings'])->name('petty.notifications.sms_settings.save');
Route::post('/notifications/auto-check', [NotificationsController::class, 'runAutoCheck'])->name('petty.notifications.auto_check');

Route::get('/profile', [ProfileController::class, 'index'])->name('petty.profile.index');
Route::get('/settings', [SettingsController::class, 'index'])->name('petty.settings.index');
Route::post('/settings/users', [SettingsController::class, 'storeUser'])->name('petty.settings.users.store');
Route::put('/settings/users/{user}', [SettingsController::class, 'updateUser'])->name('petty.settings.users.update');


// Credits
Route::get('/credits', [CreditController::class, 'index'])->name('petty.credits.index');
Route::get('/credits/create', [CreditController::class, 'create'])->name('petty.credits.create');
Route::post('/credits', [CreditController::class, 'store'])->name('petty.credits.store');
Route::get('/credits/pdf', [CreditController::class, 'pdf'])->name('petty.credits.pdf');

// Batches
Route::get('/batches', [BatchController::class, 'index'])->name('petty.batches.index');
Route::get('/batches/{id}', [BatchController::class, 'show'])->name('petty.batches.show');

// Bikes spendings
Route::get('/spendings/bikes', [BikeController::class, 'index'])->name('petty.bikes.index');
Route::get('/spendings/bikes/create', [BikeController::class, 'create'])->name('petty.bikes.create');
Route::post('/spendings/bikes', [BikeController::class, 'store'])->name('petty.bikes.store');
Route::get('/spendings/bikes/pdf', [BikeController::class, 'pdf'])->name('petty.bikes.pdf');

// Drilldown: all spendings for a bike by plate
Route::get('/bikes/{bike}/spendings', [BikeController::class, 'byBike'])->name('petty.bikes.byBike');


// Meals spendings
Route::get('/spendings/meals', [MealController::class, 'index'])->name('petty.meals.index');
Route::get('/spendings/meals/create', [MealController::class, 'create'])->name('petty.meals.create');
Route::post('/spendings/meals', [MealController::class, 'store'])->name('petty.meals.store');
Route::get('/spendings/meals/pdf', [MealController::class, 'pdf'])->name('petty.meals.pdf');



// Token/Hostels
Route::get('/spendings/tokens', [TokenController::class, 'index'])->name('petty.tokens.index');
Route::get('/spendings/tokens/create', [TokenController::class, 'create'])->name('petty.tokens.create');
Route::post('/spendings/tokens/hostels', [TokenController::class, 'storeHostel'])->name('petty.tokens.hostels.store');

Route::get('/spendings/tokens/hostels/{hostel}', [TokenController::class, 'showHostel'])->name('petty.tokens.hostels.show');
Route::post('/spendings/tokens/hostels/{hostel}/payments', [TokenController::class, 'storePayment'])->name('petty.tokens.payments.store');

// PDFs
Route::get('/spendings/tokens/pdf', [TokenController::class, 'pdfHostels'])->name('petty.tokens.pdf');
Route::get('/spendings/tokens/hostels/{hostel}/pdf', [TokenController::class, 'pdfHostelPayments'])->name('petty.tokens.hostels.pdf');


// Other spendings
Route::get('/spendings/others', [OtherController::class, 'index'])->name('petty.others.index');
Route::get('/spendings/others/create', [OtherController::class, 'create'])->name('petty.others.create');
Route::post('/spendings/others', [OtherController::class, 'store'])->name('petty.others.store');
Route::get('/spendings/others/pdf', [OtherController::class, 'pdf'])->name('petty.others.pdf');

// Reports
Route::get('/reports', [ReportsController::class, 'index'])->name('petty.reports.index');


// Specific reports
Route::get('/reports/bike', [ReportsController::class, 'bikeForm'])->name('petty.reports.bike.form');
Route::get('/reports/bike/pdf', [ReportsController::class, 'bikePdf'])->name('petty.reports.bike.pdf');

Route::get('/reports/respondent', [ReportsController::class, 'respondentForm'])->name('petty.reports.respondent.form');
Route::get('/reports/respondent/pdf', [ReportsController::class, 'respondentPdf'])->name('petty.reports.respondent.pdf');

Route::get('/reports/batch', [ReportsController::class, 'batchForm'])->name('petty.reports.batch.form');
Route::get('/reports/batch/pdf', [ReportsController::class, 'batchPdf'])->name('petty.reports.batch.pdf');

// Master / General statement
Route::get('/reports/general', [ReportsController::class, 'generalForm'])->name('petty.reports.general.form');
Route::get('/reports/general/pdf', [ReportsController::class, 'generalPdf'])->name('petty.reports.general.pdf');


//  Master Data

Route::get('/bikes-master', [BikeMasterController::class, 'index'])->name('petty.bikes_master.index');
Route::get('/bikes-master/create', [BikeMasterController::class, 'create'])->name('petty.bikes_master.create');
Route::post('/bikes-master', [BikeMasterController::class, 'store'])->name('petty.bikes_master.store');
Route::get('/bikes-master/{bike}/edit', [BikeMasterController::class, 'edit'])->name('petty.bikes_master.edit');
Route::put('/bikes-master/{bike}', [BikeMasterController::class, 'update'])->name('petty.bikes_master.update');

Route::get('/respondents', [RespondentController::class, 'index'])->name('petty.respondents.index');
Route::get('/respondents/create', [RespondentController::class, 'create'])->name('petty.respondents.create');
Route::post('/respondents', [RespondentController::class, 'store'])->name('petty.respondents.store');
Route::get('/respondents/{respondent}/edit', [RespondentController::class, 'edit'])->name('petty.respondents.edit');
Route::put('/respondents/{respondent}', [RespondentController::class, 'update'])->name('petty.respondents.update');


// Credits edit
Route::get('/credits/{credit}/edit', [\App\Modules\PettyCash\Controllers\CreditController::class, 'edit'])->name('petty.credits.edit');
Route::put('/credits/{credit}', [\App\Modules\PettyCash\Controllers\CreditController::class, 'update'])->name('petty.credits.update');

// Bike spendings edit
Route::get('/spendings/bikes/{spending}/edit', [\App\Modules\PettyCash\Controllers\Spending\BikeController::class, 'edit'])->name('petty.bikes.edit');
Route::put('/spendings/bikes/{spending}', [\App\Modules\PettyCash\Controllers\Spending\BikeController::class, 'update'])->name('petty.bikes.update');

// Meals edit
Route::get('/spendings/meals/{spending}/edit', [\App\Modules\PettyCash\Controllers\Spending\MealController::class, 'edit'])->name('petty.meals.edit');
Route::put('/spendings/meals/{spending}', [\App\Modules\PettyCash\Controllers\Spending\MealController::class, 'update'])->name('petty.meals.update');

// Others edit
Route::get('/spendings/others/{spending}/edit', [\App\Modules\PettyCash\Controllers\Spending\OtherController::class, 'edit'])->name('petty.others.edit');
Route::put('/spendings/others/{spending}', [\App\Modules\PettyCash\Controllers\Spending\OtherController::class, 'update'])->name('petty.others.update');

// Token payments edit (individual payment rows)
Route::get('/spendings/tokens/payments/{payment}/edit', [\App\Modules\PettyCash\Controllers\Spending\TokenController::class, 'editPayment'])->name('petty.tokens.payments.edit');
Route::put('/spendings/tokens/payments/{payment}', [\App\Modules\PettyCash\Controllers\Spending\TokenController::class, 'updatePayment'])->name('petty.tokens.payments.update');


Route::get('/spendings/meals/{spending}/edit', [\App\Modules\PettyCash\Controllers\Spending\MealController::class, 'edit'])->name('petty.meals.edit');
Route::put('/spendings/meals/{spending}', [\App\Modules\PettyCash\Controllers\Spending\MealController::class, 'update'])->name('petty.meals.update');

// Ledger routes
Route::get('/ledger/spendings', [
    \App\Modules\PettyCash\Controllers\LedgerController::class,
    'index'
])->name('petty.ledger.spendings');
Route::get('ledger/spendings/pdf', [\App\Modules\PettyCash\Controllers\LedgerController::class, 'pdf'])
    ->name('petty.ledger.spendings.pdf');


// Route::post('/ledger/spendings/batch-update', [
//     \App\Modules\PettyCash\Controllers\LedgerController::class,
//     'batchUpdate'
// ])->name('petty.ledger.batchUpdate');

// Route::post('/ledger/spendings/{spending}', [
//     \App\Modules\PettyCash\Controllers\LedgerController::class,
//     'updateOne'
// ])->name('petty.ledger.updateOne');



// Maintenances (read-only module from bike maintenance spendings)




Route::get('/maintenances', [MaintenancesController::class, 'index'])->name('petty.maintenances.index');

Route::get('/maintenances/{bike}', [MaintenancesController::class, 'show'])->name('petty.maintenances.show');

Route::get('/maintenances/{bike}/service/create', [MaintenancesController::class, 'create'])->name('petty.maintenances.service.create');
Route::post('/maintenances/{bike}/service', [MaintenancesController::class, 'store'])->name('petty.maintenances.service.store');

Route::post('/maintenances/{bike}/unroadworthy', [MaintenancesController::class, 'saveUnroadworthy'])->name('petty.maintenances.unroadworthy');
