<?php

namespace Insane\Journal\Providers;

use App\Events\PaymentReceived;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Insane\Journal\Listeners\CreateInvoiceTransaction;

class Event extends ServiceProvider {

    protected $listen = [
       PaymentReceived::class => [
            CreateInvoiceTransaction::class,
        ], 
    ];
}