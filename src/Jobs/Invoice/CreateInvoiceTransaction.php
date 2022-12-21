<?php

namespace Insane\Journal\Jobs\Invoice;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Insane\Journal\Models\Invoice\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Insane\Journal\Models\Core\Account;
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
        $setting = Setting::where("team_id", $this->invoice->team_id)->get()->toArray();

        $directions = [
            Invoice::DOCUMENT_TYPE_INVOICE => 'DEPOSIT' ,
            Invoice::DOCUMENT_TYPE_BILL => 'WITHDRAW'
        ];
        $this->formData['team_id'] = $this->invoice->team_id;
        $this->formData['user_id'] = $this->invoice->user_id;
        $this->formData['resource_id'] = $this->invoice->id;
        $this->formData['transactionable_id'] = Invoice::class;
        $this->formData['date'] =  $this->formData['date'] ?? $this->invoice->date ?? date('Y-m-d');
        $this->formData["description"] = $this->formData["description"] ?? $this->invoice->description;
        $this->formData["direction"] = $directions[$this->invoice->type];
        $this->formData["total"] =  $this->formData["total"] ?? $this->invoice->total;
        $this->formData["account_id"] = $this->formData['account_id'] ?? $setting["default.{$this->formData['transactionType']}.account"];
        $this->formData["category_id"] = null;
        $this->formData["status"] = "verified";


        
        if ($this->invoice->transaction) {
            $transaction = $this->invoice->transaction()->update($this->formData);
        } else {
            $transaction = $this->invoice->transaction()->create($this->formData);
        }
        $items = $this->getTransactionItems();
        $transaction->createLines($items);
        return $transaction;
    }

    protected function getTransactionItems()
    {
       return $this->invoice->type == Invoice::DOCUMENT_TYPE_INVOICE ? $this->getInvoiceItems() : $this->getBillItems();
    }

    protected function getInvoiceItems()
    {
        $isSell = $this->formData['direction'] == "DEPOSIT";
        $items = [];
        $totalTaxes = InvoiceLineTax::where(["invoice_id" =>  $this->invoice->id])
            ->selectRaw('sum(amount) as amount, name')
            ->groupBy(['tax_id', 'name'])
            ->get();

        $mainAccount = $isSell ? $this->invoice->account_id : Account::where([
            "team_id" => $this->invoice->team_id, 
            "display_id" => "products"])->first()->id;

        $items[] = [
            "index" => 0,
            "account_id" => $mainAccount,
            "category_id" => null,
            "type" => $isSell ? 1 : -1,
            "concept" => $this->formData['concept'],
            "amount" => $this->formData['total'],
            "anchor" => true,
        ];

        $items[] = [
            "index" => 1,
            "account_id" => $this->invoice->invoice_account_id,
            "category_id" => null,
            "type" => $isSell ? -1 : 1,
            "concept" => $this->formData['concept'],
            "amount" => $this->invoice->subtotal,
            "anchor" => false,
        ];

        foreach ($totalTaxes as $index => $tax) {
            $items[] = [
                "index" => $index + 2,
                "account_id" => Account::guessAccount($this->invoice, [$tax['name'], 'sales_taxes'] ),
                "category_id" => null,
                "type" => $this->formData['direction'] == "DEPOSIT" ? -1 : 1,
                "concept" => $tax['name'],
                "amount" => $tax['amount'],
                "anchor" => false,
            ];
        }
        return $items;
    }

    
    protected function getBillItems()
    {
        $isSell = $this->formData['direction'] == "DEPOSIT";
        $items = [];
        $totalTaxes = InvoiceLineTax::where(["invoice_id" =>  $this->invoice->id])
            ->selectRaw('sum(amount) as amount, name')
            ->groupBy(['tax_id', 'name'])
            ->get();

        $mainAccount = $isSell ? $this->invoice->account_id : Account::where([
            "team_id" => $this->invoice->team_id, 
            "display_id" => "products"])->first()->id;

        $items[] = [
            "index" => 0,
            "account_id" => $mainAccount,
            "category_id" => null,
            "type" => 1,
            "concept" => $this->formData['concept'],
            "amount" => $this->formData['total'],
            "anchor" => true,
        ];

        $items[] = [
            "index" => 1,
            "account_id" => $this->invoice->account_id,
            "category_id" => null,
            "type" => -1,
            "concept" => $this->formData['concept'],
            "amount" => $this->invoice->subtotal,
            "anchor" => false,
        ];

        foreach ($totalTaxes as $index => $tax) {
            $items[] = [
                "index" => $index + 2,
                "account_id" => Account::guessAccount($this->invoice, [$tax['name'], 'sales_taxes'] ),
                "category_id" => null,
                "type" => 1,
                "concept" => $tax['name'],
                "amount" => $tax['amount'],
                "anchor" => false,
            ];
        }
        return $items;
    }
}
