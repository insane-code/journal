<?php
// Write your routes here

use Illuminate\Support\Facades\Route;
use Insane\Journal\Http\Controllers\AccountController;


Route::middleware(config('jetstream.middleware', ['web']))->group(function() {
    Route::resource('/accounts', AccountController::class);
});

Route::apiResource('account','AccountController');
