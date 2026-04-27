<?php

use App\Http\Controllers\Api\Admin\AdminCategoryController;
use App\Http\Controllers\Api\Admin\AdminImageController;
use App\Http\Controllers\Api\Admin\AdminProductController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DossierController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TarifasController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Catálogo público legacy (sin autenticación)
Route::get('/categories/public', [CatalogController::class, 'publicCategories']);
Route::get('/products/catalog', [CatalogController::class, 'catalog']);
Route::get('/products/filters', [CatalogController::class, 'filters']);
Route::get('/products/{id}/detail', [CatalogController::class, 'detail']);
Route::post('/products/{id}/calculate', [CatalogController::class, 'calculateMaterials']);
Route::post('/products/{id}/pdf', [CatalogController::class, 'downloadPdf']);
Route::post('/products/{id}/send-email', [CatalogController::class, 'sendEmail']);

// Catálogo público v2 (sin autenticación)
Route::prefix('categories')->group(function () {
    Route::get('/tree', [CategoryController::class, 'tree']);
    Route::get('/by-path', [CategoryController::class, 'byPath']);
    Route::get('/{id}/products', [CategoryController::class, 'products']);
    Route::get('/{id}/filters', [CategoryController::class, 'filters']);
});

Route::get('/products/{id}/stock', [ProductController::class, 'stock'])
    ->where('id', '[0-9]+');
Route::get('/products/{slug}', [ProductController::class, 'show'])
    ->where('slug', '[a-z0-9\-]+');

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

    // Carrito
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/items', [CartController::class, 'addItem']);
        Route::put('/items/{id}', [CartController::class, 'updateItem']);
        Route::delete('/items/{id}', [CartController::class, 'removeItem']);
        Route::post('/merge', [CartController::class, 'merge']);
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

    // Admin portal (admin/superadmin only)
    Route::prefix('admin')->middleware('role:admin,superadmin')->group(function () {
        // Categories — reorder before {id} to avoid route conflicts
        Route::put('/categories/reorder', [AdminCategoryController::class, 'reorder']);
        Route::get('/categories', [AdminCategoryController::class, 'index']);
        Route::post('/categories', [AdminCategoryController::class, 'store']);
        Route::put('/categories/{id}', [AdminCategoryController::class, 'update']);
        Route::delete('/categories/{id}', [AdminCategoryController::class, 'destroy']);

        // Products — import/export before {id} to avoid route conflicts
        Route::post('/products/import', [AdminProductController::class, 'import']);
        Route::get('/products/export', [AdminProductController::class, 'export']);
        Route::get('/products', [AdminProductController::class, 'index']);
        Route::post('/products', [AdminProductController::class, 'store']);
        Route::put('/products/{id}', [AdminProductController::class, 'update']);
        Route::delete('/products/{id}', [AdminProductController::class, 'destroy']);
        Route::get('/products/{id}/price-history', [AdminProductController::class, 'priceHistory']);

        // Images
        Route::post('/images/upload', [AdminImageController::class, 'upload']);
    });
});

