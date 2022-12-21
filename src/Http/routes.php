<?php
// Write your routes here

use Illuminate\Support\Facades\Route;
use Insane\Journal\Http\Controllers\AccountController;
use Insane\Journal\Http\Controllers\InvoiceController;
use Insane\Journal\Http\Controllers\PaymentsController;
use Insane\Journal\Http\Controllers\ProductController;
use Insane\Journal\Http\Controllers\ReportController;
use Insane\Journal\Http\Controllers\TransactionController;

Route::middleware(config('jetstream.middleware', ['web']))->group(function() {

    Route::group(['middleware' => ['auth', 'verified']], function () {
        // Accounting
        Route::resource('/accounts', AccountController::class);
        Route::get('/statements/{category}', [AccountController::class, 'statements'])->name('statements.category');
        Route::get('/statements', [AccountController::class, 'statementsIndex'])->name('statements.index');
        Route::get('/reports/{category}', [ReportController::class, 'category'])->name('report.category');
        Route::resource('/transactions', TransactionController::class);
        Route::post('/transactions/{id}/approve', [TransactionController::class, 'approve'])->name('transactions.approve');
        Route::post('/transactions/remove-all-drafts', [TransactionController::class, 'removeDrafts'])->name('transactions.removeDrafts');
        Route::post('/transactions/approve-all-drafts', [TransactionController::class, 'approveDrafts'])->name('transactions.approveDrafts');
        
        // Products
        Route::resource('/products', ProductController::class);
        
        // invoicing
        Route::resource('/invoices', InvoiceController::class);
        Route::post('/invoices/{id}/payment', [InvoiceController::class, 'addPayment']);
        Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print']);
        Route::post('/invoices/{id}/mark-as-paid', [InvoiceController::class, 'markAsPaid']);
        Route::delete('/invoices/{id}/payment/{paymentId}', [InvoiceController::class, 'deletePayment']);
        
        // Bills
        Route::resource('/bills', InvoiceController::class);
        // Payments
        Route::resource('/payments', PaymentsController::class);
    });
});
