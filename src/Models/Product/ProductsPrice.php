<?php

namespace Insane\Journal\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductsPrice extends Model
{
    use HasFactory;
    protected $fillable = ['team_id','user_id', 'product_id', 'name', 'currency', 'products_variant_id', 'value', 'is_main'];


    public function variants() {
        return $this->hasMany(ProductsVariant::class);
    }
}
