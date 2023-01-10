<?php
namespace Insane\Journal\Events;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Insane\Journal\Models\Invoice\Invoice;

class InvoiceCreated {
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The transaction instance.
     *
     * @var \Insane\Journal\Models\Invoice\Invoice
     */
    public $invoice;
    /**
     * The transactionData array.
     *
     * @var array
     */
    public $invoiceData;

    /**
     * Create a new event instance.
     *
     * @param  \Insane\Journal\Models\Invoice\Invoice  $transaction
     * @return void
     */
    public function __construct(Invoice $invoice, array $invoiceData = [])
    {
        $this->invoice = $invoice;
        $this->invoiceData = $invoiceData;
    }
}
