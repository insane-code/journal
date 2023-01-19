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
        unset($this->formData['transactionType']);

        $this->formData['team_id'] = $this->invoice->team_id;
        $this->formData['user_id'] = $this->invoice->user_id;
        $this->formData['resource_id'] = $this->invoice->id;
        $this->formData['transactionable_id'] = Invoice::class;
        $this->formData['date'] =  $this->formData['date'] ?? $this->invoice->date ?? date('Y-m-d');
        $this->formData["description"] = $this->formData["description"] ?? $this->invoice->description;
        $this->formData["direction"] = $directions[$this->invoice->type];
        $this->formData["total"] =  $this->formData["total"] ?? $this->invoice->total;
        $this->formData["account_id"] = $this->formData['account_id'] ?? $setting["default.{$this->formData['transactionType']}.account"];
        $this->formData["counter_account_id"] = $this->formData['counter_account_id'] ?? $this->invoice->invoice_account_id;
        $this->formData["category_id"] = null;
        $this->formData["payee_id"] = $this->invoice->client_id;
        $this->formData["status"] = "verified";
        $this->formData["transactionable_id"] = $this->invoice->id;

        if ($transaction = $this->invoice->transaction) {
            $transaction->update($this->formData);
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

        $incomeAccount = $isSell ? $this->invoice->invoice_account_id : Account::where([
            "team_id" => $this->invoice->team_id,
            "display_id" => "products"])->first()->id;

        $lineCount = 0;
        // copy
        foreach ($this->invoice->lines as $line) {
            // debits
            $items[] = [
                "index" => $lineCount,
                "account_id" => $line->account_id ?? $incomeAccount,
                "category_id" => $line->category_id ?? null,
                "type" => -1,
                "concept" => $line->concept ?? $this->formData['concept'],
                "amount" => $line->amount ?? $this->formData['total'],
                "anchor" => false,
            ];

            // taxes and retentions
            $lineCount+= 1;

            foreach ($line->taxes as $index => $tax) {
                $lineCount+=$index;
                $items[] = [
                    "index" => $lineCount,
                    "account_id" => $tax->account_id ?? Account::guessAccount($this->invoice, [$tax['name'], 'sales_taxes'] ),
                    "category_id" => null,
                    "type" => 1,
                    "concept" => $tax['name'],
                    "amount" => $tax['amount'],
                    "anchor" => false,
                ];
            }
            // credits
            $items[] = [
                "index" => $lineCount,
                "account_id" => $line->category_id ?? $this->invoice->account_id,
                "category_id" => null,
                "type" => 1,
                "concept" => $line->concept ?? $this->formData['concept'],
                "amount" => $line->amount - $line->taxes->sum('amount'),
                "anchor" => false,
            ];
        }
        return $items;
    }


    protected function getBillItems()
    {
        $isExpense = $this->formData['direction'] == "DEPOSIT";
        $items = [];

        $mainAccount = $isExpense ? $this->invoice->account_id : Account::where([
          "team_id" => $this->invoice->team_id,
          "display_id" => "products"])->first()->id;
          $lineCount = 0;

          foreach ($this->invoice->lines as $line) {
                // debits
              $items[] = [
                  "index" => $lineCount,
                  "account_id" => $line->account_id ?? $mainAccount,
                  "category_id" => $line->category_id ?? null,
                  "type" => 1,
                  "concept" => $line->concept ?? $this->formData['concept'],
                  "amount" => $line->amount ?? $this->formData['total'],
                  "anchor" => false,
              ];

              // taxes and retentions
              $lineCount+= 1;
              foreach ($line->taxes as $index => $tax) {
                  $lineCount+=$index;
                  $items[] = [
                      "index" => $lineCount,
                      "account_id" => $tax->account_id ?? Account::guessAccount($this->invoice, [$tax['name'], 'sales_taxes'] ),
                      "category_id" => null,
                      "type" => -1,
                      "concept" => $tax['name'],
                      "amount" => $tax['amount'],
                      "anchor" => false,
                  ];
              }
              // credits
              $items[] = [
                  "index" => $lineCount,
                  "account_id" => $line->category_id ?? $this->invoice->invoice_account_id,
                  "category_id" => null,
                  "type" => -1,
                  "concept" => $line->concept ?? $this->formData['concept'],
                  "amount" => $line->amount - $line->taxes->sum('amount'),
                  "anchor" => false,
              ];
          }
          return $items;
    }
}
