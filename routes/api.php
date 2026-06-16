<?php

use App\Http\Controllers\Api\AggregationController;
use App\Http\Controllers\Api\ReceiptController;
use Illuminate\Support\Facades\Route;

Route::post('/receipts',     [ReceiptController::class,     'store']);
Route::post('/aggregations', [AggregationController::class, 'generate']);
