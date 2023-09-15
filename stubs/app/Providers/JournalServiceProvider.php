<?php

namespace App\Providers;

use App\Domains\Journal\Actions\AccountDelete;
use App\Domains\Journal\Actions\AccountStatementList;
use App\Domains\Journal\Actions\AccountStatementShow;
use App\Domains\Journal\Actions\AccountUpdate;
use App\Domains\Journal\Actions\InvoicePaymentCreate;
use App\Domains\Journal\Actions\InvoicePaymentPay;
use Illuminate\Support\ServiceProvider;
use Insane\Journal\Journal;

class JournalServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Journal::createAccountUsing(AccountCreate::class);
        Journal::updateAccountUsing(AccountUpdate::class);
        Journal::deleteAccountUsing(AccountDelete::class);
        Journal::listAccountStatementsUsing(AccountStatementList::class);
        Journal::showAccountStatementsUsing(AccountStatementShow::class);
        
        Journal::listCategoryBalanceUsing(CategoryList::class);

        Journal::payInvoiceUsing(InvoicePaymentPay::class);
        Journal::createInvoicePaymentUsing(InvoicePaymentCreate::class);
        Journal::deleteInvoicePaymentUsing(InvoicePaymentDelete::class);

        Journal::listTransactionsUsing(InvoicePaymentDelete::class);
        Journal::createTransactionsUsing(InvoicePaymentDelete::class);
        Journal::showTransactionsUsing(InvoicePaymentDelete::class);
        Journal::updateTransactionsUsing(InvoicePaymentDelete::class);
        Journal::deleteTransactionUsing(InvoicePaymentDelete::class);
        Journal::approveTransactionUsing(InvoicePaymentDelete::class);
        Journal::bulkApproveTransactionsUsing(InvoicePaymentDelete::class);
        Journal::bulkDeleteTransactionsUsing(InvoicePaymentDelete::class);


    }
}
