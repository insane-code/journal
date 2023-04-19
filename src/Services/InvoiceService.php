<?php

namespace Insane\Journal\Services;

use Insane\Journal\Jobs\Invoice\CreateInvoiceLine;
use Insane\Journal\Jobs\Invoice\CreateInvoiceTransaction;
use Illuminate\Support\Facades\Bus;
use Insane\Journal\Jobs\Invoice\CreateInvoiceRelations;
use Insane\Journal\Journal;
use Insane\Journal\Models\Core\Category;
use Insane\Journal\Models\Core\Tax;
use Insane\Journal\Models\Invoice\Invoice;
use Insane\Journal\Models\Product\Product;

class InvoiceService
{
    public function __construct(protected InvoiceValidatorService $validator)
    {
      
    }

    public function getEditableData(Invoice $invoice) {
        return [
            'invoice' => $invoice->getInvoiceData(),
            'products' => Product::where([
                'team_id' => $invoice->team_id
            ])->with(['price'])->get(),
            "categories" => Category::where([
                'depth' => 1
            ])->with([
                'subCategories',
                'accounts' => function ($query) use ($invoice) {
                    $query->where('team_id', '=', $invoice->team_id);
                },
                'accounts.lastTransactionDate'
            ])->get(),
            'type' => $invoice->type,
            'clients' => Journal::listClientsOf($invoice->team_id),
            'availableTaxes' => Tax::where("team_id", $invoice->team_id)->get(),
            ];
    }

    public function update(Invoice $invoice, $postData) {
        $this->validator->validateUpdate($invoice, $postData);

        $invoice->update($postData);
        Bus::chain([
          new CreateInvoiceLine($invoice, $postData),
          new CreateInvoiceTransaction($invoice,
            [
                'transactionType' => 'invoice',
                'direction' => 'DEPOSIT',
                'account_id' => $invoice->account_id,
                'date' => $invoice->date,
                'description' => $invoice->concept,
                'total' => $invoice->total,
            ]
          ),
          new CreateInvoiceRelations($invoice, $postData)
        ])->dispatch();
        return $invoice;
    }
}
