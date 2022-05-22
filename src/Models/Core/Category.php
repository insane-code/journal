<?php

namespace Insane\Journal\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $fillable = ['team_id','user_id', 'client_id','parent_id' , 'display_id', 'name', 'description', 'depth', 'index', 'archivable', 'archived', 'resource_type'];

    public function subCategories() {
        return $this->hasMany(self::class, 'parent_id', 'id')->orderBy('index');
    }

    public function category() {
        return $this->belongsTo(Category::class, 'parent_id', 'id');
    }

    public function accounts() {
        return $this->hasMany(Account::class, 'category_id', 'id')->orderBy('index');
    }

    public static function findOrCreateByName(string $name) {
        $category = Category::where(['display_id' => $name])->limit(1)->get();
        return count($category) ? $category[0]->id : null;
    }

    public static function getChart($teamId) {
        return self::where([
            'depth' => 0
        ])->with([
            'subCategories',
            'subcategories.accounts' => function ($query) use ($teamId) {
                $query->where('team_id', '=', $teamId);
            },
            'subcategories.accounts.lastTransactionDate'
        ])->orderBy('index')->get();
    }

    public function getAllAccounts() {
        if (!$this->parent_id) {
            $accountIds = $this->subCategories->pluck('accounts')->flatten()->pluck('id')->toArray();
            return Account::whereIn('id', $accountIds)->get();
        } else {
            $this->accounts()->pluck('accounts.id')->toArray();
        }
    }

    public static function saveBulk($categories, $extraData) {
        foreach ($categories as $index => $category) {
            $parentCategory = Category::create(array_merge($category, $extraData, ['index' => $index]));
            if (isset($category['childs'])) {
                Category::saveBulk($category['childs'], array_merge($extraData, ['depth' => $extraData['depth'] + 1, 'parent_id' => $parentCategory->id]));
            }
        }
    }
}
