<?php

namespace Insane\Journal\Jobs\Invoice;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Insane\Journal\Models\Invoice\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Insane\Journal\Models\Core\Tax;
use Insane\Journal\Models\Invoice\InvoiceLine;
use Insane\Journal\Models\Invoice\InvoiceLineTax;

class CreateInvoiceLine implements ShouldQueue
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
        InvoiceLine::query()->where('invoice_id', $this->invoice->id)->delete();
        InvoiceLineTax::query()->where('invoice_id', $this->invoice->id)->delete();
        foreach ($this->formData['items'] as $item) {
            $line = $this->invoice->lines()->create([
                "team_id" => $this->invoice->team_id,
                "user_id" => $this->invoice->user_id,
                "concept" => $item['concept'],
                "index" => $item['index'],
                "product_id" => $item['product_id'] ?? null,
                "quantity" => $item['quantity'],
                "price" => $item['price'],
                "amount" => $item['amount'],
            ]);

            isset($item['taxes']) ? $this->createItemTaxes($item['taxes'], $line) : null;
        }

        $this->invoice->save();

        return $this->invoice;
    }

    private function createItemTaxes($taxes, $line) {
        foreach ($taxes as $index => $tax) {
            if (isset($tax['name'])) {
                    $taxRate = 0;
                    if (isset($tax['tax_id'])) {
                        dd($tax);
                        $taxRate = Tax::find($tax['tax_id'])->rate;
                    } else {
                        $taxRate = (double) $tax['rate'];
                    }
                    $taxLineTotal = (double) $taxRate * $line->amount / 100;
                    $line->taxes()->create([
                        "team_id" => $this->invoice->team_id,
                        "user_id" => $this->invoice->user_id,
                        "invoice_id" => $this->invoice->id,
                        "invoice_line_id" => $line->id,
                        "tax_id" => $tax['id'],
                        "name" => $tax['name'],
                        "rate" => $taxRate,
                        "amount" => $taxLineTotal,
                        "amount_base" => $line->amount,
                        "index" => $index,
                    ]);
            }
        }
    }
}
