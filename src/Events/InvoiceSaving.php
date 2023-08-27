<?php
namespace Insane\Journal\Events;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceSaving {
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The transactionData array.
     *
     * @var array
     */
    public $invoiceData;

    /**
     * Create a new event instance.
     *
     * @param  array $invoiceData
     * @return void
     */
    public function __construct(array $invoiceData = [])
    {
        $this->invoiceData = $invoiceData;
    }
}
