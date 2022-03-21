<?php

namespace Insane\Journal\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTaxes extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'tax_id', 'user_id', 'team_id'];
}
