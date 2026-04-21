<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DossierController;
use App\Http\Controllers\Api\TarifasController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Catálogo público (sin autenticación)
Route::get('/categories/public', [CatalogController::class, 'publicCategories']);
Route::get('/products/catalog', [CatalogController::class, 'catalog']);
Route::get('/products/filters', [CatalogController::class, 'filters']);
Route::get('/products/{id}/detail', [CatalogController::class, 'detail']);
Route::post('/products/{id}/calculate', [CatalogController::class, 'calculateMaterials']);
Route::post('/products/{id}/pdf', [CatalogController::class, 'downloadPdf']);
Route::post('/products/{id}/send-email', [CatalogController::class, 'sendEmail']);

// Autenticación
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:3,60');

// Contacto
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,60');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Área de cliente
    Route::prefix('client')->group(function () {
        Route::get('/products', [ClientController::class, 'products']);
        Route::get('/products/{id}/detail', [ClientController::class, 'productDetail']);
        Route::get('/presupuestos', [ClientController::class, 'presupuestos']);
        Route::post('/presupuestos', [ClientController::class, 'storePresupuesto']);
        Route::get('/profile', [ClientController::class, 'profile']);
        Route::put('/profile', [ClientController::class, 'updateProfile']);
    });

    // Tarifas
    Route::prefix('tarifas')->group(function () {
        Route::get('/', [TarifasController::class, 'index']);
        Route::get('/export', [TarifasController::class, 'export']);
        Route::get('/customer-types', [TarifasController::class, 'customerTypes']);
        Route::get('/categories', [TarifasController::class, 'categories']);
        Route::get('/brands', [TarifasController::class, 'brands']);
        Route::get('/dossier', [DossierController::class, 'generate']);
    });
});

