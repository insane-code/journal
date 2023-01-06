<?php

namespace Insane\Journal\Jobs\Invoice;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Insane\Journal\Models\Invoice\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Insane\Journal\Models\Invoice\InvoiceRelation;

class CreateInvoiceRelations implements ShouldQueue
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
      InvoiceRelation::query()->where('invoice_id', $this->invoice->id)->delete();

      foreach ($this->formData['related_invoices'] as $relation) {
        foreach ($relation['items'] as $relatedInvoiceId) {
          InvoiceRelation::create([
            "team_id" => $this->invoice->team_id,
            "user_id" => $this->invoice->user_id,
            "name" => $relation['name'],
            "invoice_id" => $this->invoice->id,
            "related_invoice_id" => $relatedInvoiceId,
            "date" => $relation['date'] ?? $this->invoice->date,
          ]);
        }
      }
    }
}
