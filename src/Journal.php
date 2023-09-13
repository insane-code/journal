<?php

namespace Insane\Journal;

use App\Domains\Journal\Actions\AccountStatementShow;
use Insane\Journal\Contracts\AccountCreates;
use Insane\Journal\Contracts\AccountDeletes;
use Insane\Journal\Contracts\AccountStatementLists;
use Insane\Journal\Contracts\AccountUpdates;
use Insane\Journal\Contracts\CategoryListClientBalances;
use Insane\Journal\Contracts\InvoicePaymentCreates;
use Insane\Journal\Contracts\InvoicePaymentDeletes;
use Insane\Journal\Contracts\InvoicePaymentMarkAsPaid;
use Insane\Journal\Contracts\PdfExporter;

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
        app()->singleton(AccountStatementShow::class, $class);
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
