<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\MediaController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - KKN Candles
|--------------------------------------------------------------------------
*/

// =====================
// Routes Publiques
// =====================

// Authentification (avec limitation stricte)
Route::prefix('auth')->middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
});

// Produits
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/featured', [ProductController::class, 'featured']);
    Route::get('/search', [ProductController::class, 'search']);
    Route::get('/{id}', [ProductController::class, 'show']);
});

// Catégories
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{id}', [CategoryController::class, 'show']);
    Route::get('/{id}/products', [CategoryController::class, 'products']);
});

// Contact
Route::post('/contact', [ContactController::class, 'store']);

// Médias (images publiques)
Route::get('/media', [MediaController::class, 'index']);

// Webhooks CinetPay (sans auth)
Route::prefix('payments/cinetpay')->group(function () {
    Route::post('/notify', [PaymentController::class, 'cinetpayNotify']);
    Route::get('/return', [PaymentController::class, 'cinetpayReturn']);
});

// =====================
// Routes Authentifiées
// =====================

Route::middleware('jwt.auth')->group(function () {
    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'updatePassword']);
    });

    // Panier
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/items', [CartController::class, 'addItem']);
        Route::put('/items/{item}', [CartController::class, 'updateItem']);
        Route::delete('/items/{item}', [CartController::class, 'removeItem']);
        Route::delete('/', [CartController::class, 'clear']);
    });

    // Commandes
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{order}', [OrderController::class, 'show']);
    });

    // Paiements
    Route::prefix('payments')->middleware('throttle:payments')->group(function () {
        Route::post('/cinetpay/init', [PaymentController::class, 'initCinetpay']);
        Route::get('/{payment}/status', [PaymentController::class, 'status']);
    });
});

// =====================
// Routes Admin
// =====================

Route::prefix('admin')->middleware(['jwt.auth', 'admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Gestion des commandes
    Route::prefix('orders')->group(function () {
        Route::get('/', [AdminOrderController::class, 'index']);
        Route::get('/{order}', [AdminOrderController::class, 'show']);
        Route::put('/{order}/status', [AdminOrderController::class, 'updateStatus']);
    });

    // Gestion des produits
    Route::prefix('products')->group(function () {
        Route::get('/', [AdminProductController::class, 'index']);
        Route::post('/', [AdminProductController::class, 'store']);
        Route::get('/{product}', [AdminProductController::class, 'show']);
        Route::put('/{product}', [AdminProductController::class, 'update']);
        Route::delete('/{product}', [AdminProductController::class, 'destroy']);
    });

    // Gestion des catégories
    Route::prefix('categories')->group(function () {
        Route::get('/', [AdminCategoryController::class, 'index']);
        Route::post('/', [AdminCategoryController::class, 'store']);
        Route::get('/{category}', [AdminCategoryController::class, 'show']);
        Route::put('/{category}', [AdminCategoryController::class, 'update']);
        Route::delete('/{category}', [AdminCategoryController::class, 'destroy']);
    });

    // Gestion des médias
    Route::prefix('media')->group(function () {
        Route::get('/', [MediaController::class, 'index']);
        Route::post('/upload', [MediaController::class, 'upload']);
        Route::delete('/{media}', [MediaController::class, 'destroy']);
    });
});
