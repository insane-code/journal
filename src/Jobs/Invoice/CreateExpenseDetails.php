<?php

namespace Insane\Journal\Jobs\Invoice;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Insane\Journal\Models\Invoice\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateExpenseDetails implements ShouldQueue
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
      if (isset($this->formData['expense_details']) && $this->invoice->type == Invoice::DOCUMENT_TYPE_BILL) {
        $expense = $this->formData['expense_details'];
        $this->invoice->expenseDetails()->create([
          'customer_id' => $expense['customer_id'],
          'payment_account_id' => $expense['customer_id'],
          'is_personal' => $expense['is_personal'],
          'is_billable' => $expense['is_billable'],
          'expense_type' => $expense['expense_type']
        ]);
      }
    }
}
