<?php

namespace Insane\Journal\Models\Quote;

use Illuminate\Database\Eloquent\Model;
use Insane\Journal\Models\Product\Product;

class QuoteLine extends Model
{
    protected $fillable = [
      'team_id',
      'user_id',
      'product_id', 
      'date',
      'concept', 
      'amount', 
      'index', 
      'price', 
      'quantity', 
      'subtotal', 
      'discount',
      'meta_data',
      'product_image'
    ];
    protected $with = ['taxes'];
    protected $casts = ['meta_data' => 'array'];

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function quote() {
        return $this->belongsTo(Quote::class);
    }

    public function taxes() {
        return $this->hasMany(QuoteLineTax::class);
    }
}
