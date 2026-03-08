<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TarifasController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Autenticación
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Tarifas
    Route::prefix('tarifas')->group(function () {
        Route::get('/', [TarifasController::class, 'index']);
        Route::get('/export', [TarifasController::class, 'export']);
        Route::get('/customer-types', [TarifasController::class, 'customerTypes']);
        Route::get('/categories', [TarifasController::class, 'categories']);
        Route::get('/brands', [TarifasController::class, 'brands']);
    });
});

