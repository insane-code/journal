<?php

namespace Insane\Journal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    public function subCategories() {
        return $this->hasMany('Insane\Journal\Category', 'parent_id', 'id')->orderBy('index');
    }

    public function accounts() {
        return $this->hasMany('Insane\Journal\Account', 'category_id', 'id')->orderBy('index');
    }
}
