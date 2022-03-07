<?php

namespace Insane\Journal\Jobs\Invoice;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Insane\Journal\Models\Invoice\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Insane\Journal\Models\Invoice\InvoiceLine;
use Insane\Journal\Models\Invoice\InvoiceLineTax;

class CreateInvoiceTransaction implements ShouldQueue
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
        InvoiceLine::query()->where('invoice_id', $this->invoice->id)->get();
        InvoiceLineTax::query()->where('invoice_id', $this->invoice->id)->get();
        $setting = \App\Model\Setting::where("team_id", $this->invoice->team_id)->get()->toArray();

        $this->formData['team_id'] = $this->invoice->team_id;
        $this->formData['user_id'] = $this->invoice->user_id;
        $this->formData['resource_id'] = $this->invoice->id;
        $this->formData['transactionable_id'] = Invoice::class;
        $this->formData['date'] = isset($this->formData['date']) ? $this->formData['date'] : date('Y-m-d');
        $this->formData["description"] = isset($this->formData["description"]) ? $this->formData["description"] : $this->invoice->description;
        $this->formData["direction"] = isset($this->formData["direction"]) ? $this->formData['direction'] : "DEPOSIT";
        $this->formData["total"] = isset($this->formData["total"]) ? $this->formData["total"] : $this->invoice->total;
        $this->formData["account_id"] = isset($this->formData['account_id']) ? $this->formData['account_id'] : $setting["default.{$this->formData['transactionType']}.account"];
        $this->formData["category_id"] = isset($this->formData["category_id"]) ? $this->formData["category_id"] : $setting["default.{$this->formData['transactionType']}.category"];
        
        $this->formData['items'] = [];

        $this->formData['items'][] = [
            "index" => 0,
            "product_id" => null,
            "quantity" => 1,
            "price" => $this->invoice->total,
            "amount" => $this->invoice->total,
            "taxes" => [],
        ];

        $this->formData['items'] = $this->getTransactionItems();


        $transaction = $this->invoice->transaction()->create($this->formData);
        return $transaction;
    }

    protected function getTransactionItems()
    {
        $items = [];
        $totalTaxes = InvoiceLineTax::where(["invoice_id" =>  $this->invoice->id])
            ->selectRaw('sum(amount) as amount, name')
            ->groupBy('tax_id')
            ->get();
        
        $items[] = [
            "index" => 0,
            "account_id" => $this->formData['account_id'],
            "category_id" => $this->invoice->account_client()->id,
            "type" => $this->formData['direction'] == "DEPOSIT" ? -1 : 1,
            "concept" => $this->formData['concept'],
            "amount" => $this->formData['total'],
            "anchor" => true,
        ];

        $items[] = [
            "index" => 1,
            "account_id" => $this->formData['account_id'],
            "category_id" => $this->formData['account_id'],
            "type" => $this->formData['direction'] == "DEPOSIT" ? 1 : -1,
            "concept" => $this->formData['concept'],
            "amount" => $this->invoice->subtotal,
            "anchor" => false,
        ];

        foreach ($totalTaxes as $index => $tax) {
            $items[] = [
                "index" => $index + 2,
                "account_id" => $tax['product_id'] ?? null,
                "category_id" => $this->formData['account_id'],
                "type" => $this->formData['direction'],
                "concept" => $tax['name'],
                "amount" => $tax['amount'],
                "anchor" => false,
            ];
        }
        return $items;
    }
}
