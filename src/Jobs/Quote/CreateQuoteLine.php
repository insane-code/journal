<?php

namespace Insane\Journal\Jobs\quote;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Insane\Journal\Models\Quote\Quote;
use Illuminate\Queue\InteractsWithQueue;
use Insane\Journal\Models\Quote\QuoteLine;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Insane\Journal\Models\Quote\QuoteLineTax;

class CreateQuoteLine implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Quote $quote,public array $formData)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        QuoteLine::query()->where('quote_id', $this->quote->id)->delete();
        QuoteLineTax::query()->where('quote_id', $this->quote->id)->delete();
        if (isset($this->formData['items']) && count($this->formData['items'])) {
            foreach ($this->formData['items'] as $index => $item) {
                $line = $this->quote->lines()->create([
                    "team_id" => $this->quote->team_id,
                    "user_id" => $this->quote->user_id,
                    "concept" => $item['concept'],
                    "category_id" => $item['category_id'] ?? null,
                    "date" => $item['date'] ?? $this->quote->date,
                    "index" => $item['index'] ?? $index,
                    "product_id" => $item['product_id'] ?? null,
                    "quantity" => $item['quantity'],
                    "price" => $item['price'],
                    "amount" => $item['amount'],
                    "product_image" => $item['product_image'] ?? "",
                    "meta_data" => $item['meta_data'] ?? [],
                ]);
    
                isset($item['taxes']) ? $this->createItemTaxes($item['taxes'], $line) : null;
            }
        } else {   
            $line = $this->quote->lines()->create([
                "team_id" => $this->quote->team_id,
                "user_id" => $this->quote->user_id,
                "concept" => $this->quote->concept,
                "category_id" => null,
                "date" => $this->quote->date,
                "index" => 0,
                "product_id" => null,
                "quantity" => 1,
                "price" => $this->formData['total'],
                "amount" => $this->formData['total'],
                "product_image" => "",
                "meta_data" => [],
            ]);

        }
        
        $this->quote->save();
        return $this->quote;
    }

    private function createItemTaxes($taxes, $line) {
        foreach ($taxes as $index => $tax) {
            if (isset($tax['name'])) {
                    $taxRate = (double) $tax['rate'];
                    $taxLineTotal = (double) $taxRate * $line->amount / 100;
                    $line->taxes()->create([
                        "team_id" => $this->quote->team_id,
                        "user_id" => $this->quote->user_id,
                        "quote_id" => $this->quote->id,
                        "quote_line_id" => $line->id,
                        "tax_id" => $tax['id'],
                        "name" => $tax['name'],
                        "is_fixed" => $tax['is_fixed'],
                        "label" => $tax['label'],
                        "concept" => $tax['description'] ?? $tax['concept'],
                        "rate" => $taxRate,
                        "type" => $tax['type'],
                        "amount" => $tax['amount'] ?? $taxLineTotal,
                        "amount_base" => $line->amount,
                        "index" => $index,
                    ]);
            }
        }
    }
}
