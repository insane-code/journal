<?php

namespace Insane\Journal;

use Insane\Journal\Contracts\AccountCatalogCreates;
use Insane\Journal\Contracts\AccountCreates;
use Insane\Journal\Contracts\AccountDeletes;
use Insane\Journal\Contracts\AccountDetailTypesCreates;
use Insane\Journal\Contracts\AccountStatementLists;
use Insane\Journal\Contracts\AccountStatementShows;
use Insane\Journal\Contracts\AccountUpdates;
use Insane\Journal\Contracts\CategoryListClientBalances;
use Insane\Journal\Contracts\InvoicePaymentCreates;
use Insane\Journal\Contracts\InvoicePaymentDeletes;
use Insane\Journal\Contracts\InvoicePaymentMarkAsPaid;
use Insane\Journal\Contracts\PdfExporter;
use Insane\Journal\Contracts\TransactionApproves;
use Insane\Journal\Contracts\TransactionBulkApproves;
use Insane\Journal\Contracts\TransactionCategoriesCreates;
use Insane\Journal\Contracts\TransactionCreates;
use Insane\Journal\Contracts\TransactionDeletes;
use Insane\Journal\Contracts\TransactionLists;
use Insane\Journal\Contracts\TransactionShows;
use Insane\Journal\Contracts\TransactionUpdates;

class Journal
{
    /**
     * Indicates the class that will serve as client for invoices
     */
    public static $customerModel = 'App\\Models\\User';
     /**
     * Indicates if Jetstream routes will be registered.
     *
     * @var bool
     */
    public static $registersRoutes = true;

   // customers / client related setup
    public static function useCustomerModel(string $model) {
      static::$customerModel = $model;
    }

    public static function findClient($clientId) {
      return $clientId ? (new static::$customerModel)
          ->where('customer_id', $clientId)->get() : null;
    }

    public static function listClientsOf($teamId) {
      return (new static::$customerModel)->where('team_id', $teamId)->get();
    }

    /***
     * account actions
     */
    //
    public static function createAccountUsing(string $class): void
    {
        app()->singleton(AccountCreates::class, $class);
    }

    public static function updateAccountUsing(string $class): void
    {
        app()->singleton(AccountUpdates::class, $class);
    }

    public static function deleteAccountUsing(string $class): void
    {
        app()->singleton(AccountDeletes::class, $class);
    }

    /***
     *
     * Account statement related actions
     */
    public static function listAccountStatementsUsing(string $class): void
    {
        app()->singleton(AccountStatementLists::class, $class);
    }

    public static function showAccountStatementsUsing(string $class): void
    {
        app()->singleton(AccountStatementShows::class, $class);
    }


    /***
     *
     * Category related actions
     */
    public static function listCategoryBalanceUsing(string $class): void
    {
        app()->singleton(CategoryListClientBalances::class, $class);
    }

    /***
     *
     * Invoice Payment related actions
     */
    public static function createInvoicePaymentUsing(string $class): void
    {
        app()->singleton(InvoicePaymentCreates::class, $class);
    }

    public static function deleteInvoicePaymentUsing(string $class): void
    {
        app()->singleton(InvoicePaymentDeletes::class, $class);
    }

    public static function payInvoiceUsing(string $class): void
    {
        app()->singleton(InvoicePaymentMarkAsPaid::class, $class);
    }


    /***
     *
     * Transactions related actions
     */

     public static function listTransactionsUsing(string $class): void {
        app()->singleton(TransactionLists::class, $class);
     }

     public static function createTransactionsUsing(string $class) {
        app()->singleton(TransactionCreates::class, $class);
     }

     public static function showTransactionsUsing(string $class) {
        app()->singleton(TransactionShows::class, $class);
     }

     public static function updateTransactionsUsing(string $class) {
        app()->singleton(TransactionUpdates::class, $class);
     }

     public static function deleteTransactionUsing(string $class) {
        app()->singleton(TransactionDeletes::class, $class);
    }

    public static function approveTransactionUsing(string $class) {
         app()->singleton(TransactionApproves::class, $class);
    }

    public static function bulkApproveTransactionsUsing(string $class) {
         app()->singleton(TransactionBulkApproves::class, $class);
    }

    public static function bulkDeleteTransactionsUsing(string $class) {
         app()->singleton(TransactionBulkDeletes::class, $class);
     }

     public static function createAccountCatalogUsing(string $class) {
        app()->singleton(AccountCatalogCreates::class, $class);
     }

     public static function createAccountDetailTypesUsing(string $class) {
        app()->singleton(AccountDetailTypesCreates::class, $class);
     }

     public static function createTransactionCategoriesUsing(string $class) {
        app()->singleton(TransactionCategoriesCreates::class, $class);
     }


    /**
     * Register a class / callback that should be used to print the invoices.
     *
     * @param  string  $class
     * @return void
     */
    public static function printInvoiceUsing(string $class)
    {
        return app()->singleton(PdfExporter::class, $class);
    }
}
