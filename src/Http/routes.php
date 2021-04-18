<?php
// Write your routes here

use Illuminate\Support\Facades\Route;
use Insane\Journal\Http\Controllers\AccountController;
use Insane\Journal\Http\Controllers\TransactionController;

Route::middleware(config('jetstream.middleware', ['web']))->group(function() {
    Route::resource('/accounts', AccountController::class);
    Route::resource('/transactions', TransactionController::class);
});

Route::apiResource('account','AccountController');
Route::apiResource('transaction','TransactionController');
