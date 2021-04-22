<?php
// Write your routes here

use Illuminate\Support\Facades\Route;
use Insane\Journal\Http\Controllers\AccountController;
use Insane\Journal\Http\Controllers\InvoiceController;
use Insane\Journal\Http\Controllers\ProductController;
use Insane\Journal\Http\Controllers\TransactionController;

Route::middleware(config('jetstream.middleware', ['web']))->group(function() {

    Route::group(['middleware' => ['auth', 'verified']], function () {
        Route::resource('/accounts', AccountController::class);
        Route::resource('/transactions', TransactionController::class);
        Route::resource('/products', ProductController::class);
        Route::resource('/invoices', InvoiceController::class);
        Route::post('/invoices/{id}/payment', [InvoiceController::class, 'addPayment']);
        Route::delete('/invoices/{id}/payment/{paymentId}', [InvoiceController::class, 'deletePayment']);
    });
});

Route::apiResource('account','AccountController');
Route::apiResource('transaction','TransactionController');
