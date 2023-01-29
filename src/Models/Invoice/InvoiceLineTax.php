<?php

namespace Insane\Journal\Models\Invoice;

use Illuminate\Database\Eloquent\Model;
use Insane\Journal\Models\Core\Tax;
use Insane\Journal\Models\Product\Product;

class InvoiceLineTax extends Model
{
    protected $fillable = [
      'team_id',
      'user_id',
      'invoice_id',
      'tax_id',
      'amount',
      'amount_base',
      'rate',
      'index',
      'type',
      'name',
      'label',
      'concept',
      'quantity',
      'subtotal',
      'discount'
    ];

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function tax() {
        return $this->belongsTo(Tax::class);
    }

    static public function updateStock($lineItem) {
        if ($lineItem->product) {
            $lineItem->product->updateStock();
        }
    }

    static public function updateStockFromService($productId) {
        $product = Product::find($productId);
        $product->updateStock();
        return $product;
    }
}
