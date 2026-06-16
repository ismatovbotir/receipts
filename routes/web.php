<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\AggregationsController;
use App\Http\Controllers\CalendarController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/receipts', [ReceiptController::class, 'index'])->name('receipts.index');
Route::get('/receipts/{id}', [ReceiptController::class, 'show'])->name('receipts.show');
Route::get('/aggregations', [AggregationsController::class, 'index'])->name('aggregations');

Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
Route::post('/calendar/notes', [CalendarController::class, 'store'])->name('calendar.notes.store');
Route::delete('/calendar/notes/{id}', [CalendarController::class, 'destroy'])->name('calendar.notes.destroy');
