<?php

namespace Insane\Journal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductsPrice extends Model
{
    use HasFactory;
    protected $fillable = ['team_id','user_id', 'product_id', 'name', 'currency', 'products_variant_id', 'value', 'is_main'];

    public function images()
    {
        return $this->morphMany(Image::class, 'imageble');
    }

    public function variants() {
        return $this->hasMany(ProductsVariant::class);
    }

    public function price() {
        return $this->hasOne(ProductsPrice::class);
    }

    public function options() {
        return $this->hasMany(ProductsOption::class);
    }
}
