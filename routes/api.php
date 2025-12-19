<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\DashboardController;

// Products
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/categories', [ProductController::class, 'categories']);
    Route::get('/{id}', [ProductController::class, 'show']);
});

// Transactions
Route::prefix('transactions')->group(function () {
    Route::get('/', [TransactionController::class, 'index']);
    Route::post('/', [TransactionController::class, 'store']);
});

// Dashboard
Route::prefix('dashboard')->group(function () {
    Route::get('/summary', [DashboardController::class, 'summary']);
    Route::get('/sales-by-category', [DashboardController::class, 'categorySales']);
    Route::get('/top-products', [DashboardController::class, 'bestSelling']);
    Route::get('/monthly-trend', [DashboardController::class, 'salesTrend']);
    Route::get('/complete', [DashboardController::class, 'complete']);
});
