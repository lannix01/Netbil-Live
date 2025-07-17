<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\InvoiceController;

Route::get('/metrics', [MetricsController::class, 'fetch']);



Route::get('/metrics', [MetricsController::class, 'fetchAll']);
Route::get('/hotspot/users', [MetricsController::class, 'listHotspotUsers']);
Route::get('/hotspot/leases', [MetricsController::class, 'listLeases']);
Route::get('/interfaces', [MetricsController::class, 'listInterfaces']);
Route::post('/hotspot/disconnect/{mac}', [MetricsController::class, 'disconnectHotspotUser']);
// api.php
Route::get('/invoices/users', [InvoiceController::class, 'users']); // DataTables feed
Route::get('/invoices/{userId}', [InvoiceController::class, 'userDetails']); // One user
