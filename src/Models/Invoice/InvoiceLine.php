<?php

namespace Insane\Journal\Models\Invoice;

use Illuminate\Database\Eloquent\Model;

class InvoiceLine extends Model
{
    protected $fillable = ['team_id','user_id','product_id', 'concept', 'amount', 'index', 'price', 'quantity', 'subtotal', 'discount'];


    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function invoice() {
        return $this->belongsTo(Invoice::class);
    }

    public function taxes() {
        return $this->hasMany(InvoiceLineTax::class);
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
