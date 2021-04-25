<?php

namespace Insane\Journal;

use Illuminate\Support\Facades\Facade;

class JournalFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'journal';
    }
}
