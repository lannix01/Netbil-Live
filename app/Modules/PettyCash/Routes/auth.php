<?php

use Illuminate\Support\Facades\Route;
use App\Modules\PettyCash\Controllers\Auth\LoginController;

Route::get('/login', [LoginController::class, 'show'])
    ->name('petty.login');

Route::post('/login', [LoginController::class, 'authenticate'])
    ->name('petty.login.submit');

Route::post('/logout', [LoginController::class, 'logout'])
    ->name('petty.logout');