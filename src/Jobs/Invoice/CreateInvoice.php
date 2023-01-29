<?php

namespace Insane\Journal\Jobs\Invoice;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Insane\Journal\Models\Invoice\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Events\InvoiceCreated;
use Insane\Journal\Events\InvoiceSaving;

class CreateInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $invoice;
    protected $formData;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($formData)
    {
        $this->formData = $formData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
      $invoiceData = $this->formData;
      event(new InvoiceSaving($invoiceData));
      DB::transaction(function () use ($invoiceData) {
        $this->invoice = Invoice::create($invoiceData);
        Bus::chain([
            new CreateInvoiceLine($this->invoice, $invoiceData),
            new CreateInvoiceTransaction($this->invoice, array_merge(
                $this->invoice->toArray(),
                [
                    'transactionType' => 'invoice',
                    'direction' => 'DEPOSIT',
                    'account_id' => $this->invoice->account_id,
                    'date' => $this->invoice->date,
                    'description' => $this->invoice->concept,
                    'total' => $this->invoice->total,
                ]
            )),
            new CreateInvoiceRelations($this->invoice, $invoiceData)
        ])->dispatch();
      });
      event(new InvoiceCreated($this->invoice, $invoiceData));
      return $this->invoice;
    }


}
