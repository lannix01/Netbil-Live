<?php

use Illuminate\Support\Facades\Route;
use App\Modules\PettyCash\Controllers\Auth\LoginController;
use App\Modules\PettyCash\Controllers\RespondentController;

Route::get('/login', [LoginController::class, 'show'])
    ->name('petty.login');

Route::post('/login', [LoginController::class, 'authenticate'])
    ->name('petty.login.submit');

Route::post('/logout', [LoginController::class, 'logout'])
    ->name('petty.logout');

Route::get('/respondent-cards/{token}', [RespondentController::class, 'publicCard'])
    ->name('petty.respondents.card.public.show');
Route::post('/respondent-cards/{token}/download', [RespondentController::class, 'publicCardDownload'])
    ->name('petty.respondents.card.public.download');
