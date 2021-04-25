<?php

namespace Insane\Journal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Category extends Model
{
    use HasFactory;
    protected $fillable = ['team_id','user_id', 'client_id','parent_id' , 'display_id', 'name', 'description', 'depth', 'index', 'archivable', 'archived'];

    public function subCategories() {
        return $this->hasMany('Insane\Journal\Category', 'parent_id', 'id')->orderBy('index');
    }

    public function accounts() {
        return $this->hasMany('Insane\Journal\Account', 'category_id', 'id')->orderBy('index');
    }

    public static function findOrCreateByName(string $name) {
        $category = Category::where(['display_id' => $name])->limit(1)->get();
        return count($category) ? $category[0]->id : null;
    }

}
