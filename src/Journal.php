<?php

namespace Insane\Journal;

use Insane\Journal\Contracts\DeleteAccounts;

class Journal
{
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
}
