<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\OrderController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;

// Order Routes
Route::apiResource('orders', OrderController::class);

// Discount Calculation
Route::post('discounts/calculate', [DiscountController::class, 'calculate']);

// Product Routes
Route::get('products', [ProductController::class, 'index']);
Route::post('products', [ProductController::class, 'store']);
Route::get('products/{id}', [ProductController::class, 'show']);

// Customer Routes
Route::get('customers', [CustomerController::class, 'index']);
