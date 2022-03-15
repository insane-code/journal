<?php

namespace Insane\Journal\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $fillable = ['team_id','user_id', 'client_id','parent_id' , 'display_id', 'name', 'description', 'depth', 'index', 'archivable', 'archived'];

    public function subCategories() {
        return $this->hasMany(Category::class, 'parent_id', 'id')->orderBy('index');
    }

    public function accounts() {
        return $this->hasMany(Account::class, 'category_id', 'id')->orderBy('index');
    }

    public static function findOrCreateByName(string $name) {
        $category = Category::where(['display_id' => $name])->limit(1)->get();
        return count($category) ? $category[0]->id : null;
    }

}
