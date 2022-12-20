<?php

namespace Insane\Journal;

use Insane\Journal\Contracts\DeleteAccounts;

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

      /**
     * Register a class / callback that should be used to delete teams.
     *
     * @param  string  $class
     * @return void
     */
    public static function deleteAccountUsing(string $class)
    {
        return app()->singleton(DeleteAccounts::class, $class);
    }


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
}
