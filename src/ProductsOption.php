<?php

namespace Insane\Journal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductsOption extends Model
{
    use HasFactory;

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
