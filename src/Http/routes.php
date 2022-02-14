<?php
// Write your routes here

use Illuminate\Support\Facades\Route;
use Insane\Journal\Http\Controllers\AccountController;
use Insane\Journal\Http\Controllers\InvoiceController;
use Insane\Journal\Http\Controllers\PaymentsController;
use Insane\Journal\Http\Controllers\ProductController;
use Insane\Journal\Http\Controllers\TransactionController;

Route::middleware(config('jetstream.middleware', ['web']))->group(function() {

    Route::group(['middleware' => ['auth', 'verified']], function () {
        Route::resource('/accounts', AccountController::class);
        Route::get('/statements/{category}', [AccountController::class, 'statements'])->name('statements.index');
        Route::resource('/transactions', TransactionController::class);
        Route::post('/transactions/{id}/approve', [TransactionController::class, 'approve'])->name('transactions.approve');
        Route::resource('/products', ProductController::class);
        Route::resource('/invoices', InvoiceController::class);
        Route::resource('/payments', PaymentsController::class);
        Route::post('/invoices/{id}/payment', [InvoiceController::class, 'addPayment']);
        Route::delete('/invoices/{id}/payment/{paymentId}', [InvoiceController::class, 'deletePayment']);
    });
});
