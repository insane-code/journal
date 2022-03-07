<?php

namespace Insane\Journal\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Insane\Journal\Models\Core\Image;

class Product extends Model
{
    use HasFactory;
    protected $fillable = ['team_id','user_id', 'name', 'sku', 'description', 'available'];

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function variants() {
        return $this->hasMany(ProductsVariant::class);
    }

    public function price() {
        return $this->hasOne(ProductsPrice::class)->where('is_main', true);
    }

    public function priceList() {
        return $this->hasMany(ProductsPrice::class)->whereNull('is_main');
    }

    public function options() {
        return $this->hasMany(ProductsOption::class);
    }

    public static function addProduct($data) {
        $product = self::create($data);
        $price = $data['price'] ?? [
            'value' => 0,
        ];
        $priceList = $data['price_list'] ?? [];

        $product->price()->create(array_merge($price, [
            'user_id' => $product->user_id,
            'team_id' => $product->team_id,
            'is_main' => true
        ]));

        foreach ($priceList as $priceItem) {
            $product->price()->create(
                array_merge($priceItem,
                    [
                        'user_id' => $product->user_id,
                        'team_id' => $product->team_id
                    ]
                )
            );
        }

        if (isset($data['options'])) {
            $product->options()->create(array_merge($data['options'], ['user_id' => $product->user_id, 'team_id' => $product->team_id]));
        }
        return $product;
    }

    public static function updateProduct($data) {
        $product = self::find($data['id']);
        $product->update($data);

        $price = $data['price'] ?? [
            'value' => 0,
        ];

        $priceList = $data['price_list'] ?? [];
        $prices = array_merge([$price], $priceList);
        foreach ($prices as $priceItem) {
            if (isset($priceItem['id'])) {
                $savedPrice = ProductsPrice::find($priceItem['id']);
                $savedPrice->update($priceItem);
            } else {
                $product->price()->create(
                    array_merge($priceItem,
                        [
                            'user_id' => $product->user_id,
                            'team_id' => $product->team_id
                        ]
                    )
                );
            }
        }
        if (isset($data['options'])) {
            $product->options()->create(array_merge($data['options'], ['user_id' => $product->user_id, 'team_id' => $product->team_id]));
        }
        return $product;
    }
}
