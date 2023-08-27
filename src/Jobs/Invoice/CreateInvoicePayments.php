<?php

namespace Insane\Journal\Jobs\Invoice;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Insane\Journal\Models\Invoice\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateInvoicePayments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $invoice;
    protected $formData;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Invoice $invoice, $formData)
    {
        $this->invoice = $invoice;
        $this->formData = $formData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
      if (isset($this->formData['payment_details'])) {
        $paymentDetails = $this->formData['payment_details'];
        $this->invoice->markAsPaid($paymentDetails);
      }
    }
}
