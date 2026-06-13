<?php

use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\IpController;
use App\Http\Controllers\Api\LookupController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\RentalAgreementController;
use App\Http\Controllers\Api\ServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public JSON API (stateless — no session, no CSRF)
|--------------------------------------------------------------------------
| Mirrors the Next.js public /api/* routes, consumed by the Alpine front-end.
| Registered with the /api prefix by bootstrap/app.php.
*/

Route::get('/services', [ServiceController::class, 'index']);
Route::get('/equipment', [EquipmentController::class, 'index']);
Route::get('/lookup', [LookupController::class, 'index']);
Route::post('/quote', [QuoteController::class, 'store']);
Route::get('/ip', [IpController::class, 'show']);

Route::get('/rental-agreement/{token}', [RentalAgreementController::class, 'show']);
Route::post('/rental-agreement/{token}', [RentalAgreementController::class, 'sign']);
