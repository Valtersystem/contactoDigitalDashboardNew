<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\SubscriptionController;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {


    Route::resource('/clients', ClientController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('products', ProductController::class);

    Route::get('/products/{product}/assets', [AssetController::class, 'index'])->name('products.assets.index');
    Route::post('/products/{product}/assets', [AssetController::class, 'store'])->name('products.assets.store');
    Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy');
    Route::put('/assets/{asset}', [AssetController::class, 'update'])->name('assets.update');
    Route::patch('/assets/{asset}/status', [AssetController::class, 'updateStatus'])->name('assets.updateStatus'); // Rota corrigida


    Route::get('/rentals', [RentalController::class, 'index'])->name('rentals.index');
    Route::get('/rentals/create', [RentalController::class, 'create'])->name('rentals.create');
    Route::post('/rentals', [RentalController::class, 'store'])->name('rentals.store');
    Route::get('/rentals/{rental}/return', [RentalController::class, 'showReturnForm'])->name('rentals.return.form');
    Route::post('/rentals/{rental}/return', [RentalController::class, 'processReturn'])->name('rentals.return.process');


    Route::get('/asset-management', [AssetManagementController::class, 'index'])->name('asset-management.index');
    Route::patch('/asset-management/bulk/{rental_item}', [AssetManagementController::class, 'updateBulkStatus'])->name('asset-management.bulk.update');
    Route::patch('/asset-management/serialized/{asset}/status', [AssetManagementController::class, 'updateStatus'])->name('asset-management.updateStatus');

    Route::resource('subscriptions', SubscriptionController::class);
    Route::get('/reports', [FinancialReportController::class, 'index'])->name('reports.index');


});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
