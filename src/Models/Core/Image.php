<?php

namespace Insane\Journal\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;
    protected $fillable = ['team_id','user_id', 'name','url'];

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
