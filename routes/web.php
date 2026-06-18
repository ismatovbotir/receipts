<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\AggregationsController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SalesController;
use Illuminate\Support\Facades\Route;

// Analytics pages: GET renders (reads session filter), POST stores filter then redirects (PRG pattern)
Route::match(['get','post'], '/',         [DashboardController::class, 'index'])->name('dashboard');
Route::match(['get','post'], '/sales',    [SalesController::class,     'index'])->name('sales.index');
Route::match(['get','post'], '/cashiers', [CashierController::class,   'index'])->name('cashiers.index');
Route::match(['get','post'], '/products', [ProductController::class,   'index'])->name('products.index');

Route::get('/receipts',                  [ReceiptController::class, 'index'])->name('receipts.index');
Route::get('/receipts/analytics/export', [ReceiptController::class, 'exportAnalytics'])->name('receipts.analytics.export');
Route::get('/receipts/{id}/json',        [ReceiptController::class, 'showJson'])->name('receipts.show.json');
Route::get('/receipts/{id}',             [ReceiptController::class, 'show'])->name('receipts.show');
Route::get('/aggregations',  [AggregationsController::class, 'index'])->name('aggregations');

Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');

Route::get('/calendar',                    [CalendarController::class, 'index'])->name('calendar');
Route::post('/calendar/notes',             [CalendarController::class, 'store'])->name('calendar.notes.store');
Route::delete('/calendar/notes/{id}',      [CalendarController::class, 'destroy'])->name('calendar.notes.destroy');
