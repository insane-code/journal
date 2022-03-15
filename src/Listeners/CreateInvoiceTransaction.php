<?php

namespace Insane\Journal\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Insane\Journal\Events\PaymentReceived;
use Insane\Journal\Jobs\Invoice\CreateInvoiceTransaction as InvoiceCreateInvoiceTransaction;

class CreateInvoiceTransaction
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(PaymentReceived $event)
    {
       return new InvoiceCreateInvoiceTransaction($event->invoice, $event->formData);
    }
}
