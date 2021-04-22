<?php

namespace Insane\Journal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = ['team_id','user_id', 'name', 'sku', 'description'];

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
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

    public static function addProduct($data) {
        $product = self::create($data);
        $product->price()->create(array_merge($data['price'], ['user_id' => $product->user_id, 'team_id' => $product->team_id]));
        if (isset($data['options'])) {
            $product->options()->create(array_merge($data['options'], ['user_id' => $product->user_id, 'team_id' => $product->team_id]));
        }
        return $product;
    }
}
