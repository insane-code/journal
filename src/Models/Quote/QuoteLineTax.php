<?php

namespace Insane\Journal\Models\Quote;

use Insane\Journal\Models\Core\Tax;
use Illuminate\Database\Eloquent\Model;
use Insane\Journal\Models\Product\Product;

class QuoteLineTax extends Model
{
    protected $fillable = [
      'team_id',
      'user_id',
      'quote_id',
      'tax_id',
      'is_fixed',
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
}
