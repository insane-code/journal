<?php

namespace Insane\Journal\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;
    protected $fillable = ['team_id','user_id', 'client_id','parent_id' , 'display_id', 'name', 'description', 'depth', 'index', 'resource_type'];

    public function subCategories() {
        return $this->hasMany(self::class, 'parent_id', 'id')->orderBy('index');
    }

    public function category() {
        return $this->belongsTo(Category::class, 'parent_id', 'id');
    }

    public function accounts() {
        return $this->hasMany(Account::class, 'category_id', 'id')->orderBy('index');
    }

    public static function findOrCreateByName($session, string $name, int $parentId = null, string $resourceType = 'transactions') {
        $category = Category::where(
            [
                'display_id' => Str::slug($name),
                'name' => $name,
                'team_id' => $session['team_id'],
            ])->limit(1)
            ->get();

        if (!$category->count()) {
            Category::create([
                'display_id' => Str::slug($name),
                'name' => $name,
                'parent_id' => $parentId,
                'user_id' => $session['user_id'],
                'team_id' => $session['team_id'],
                'depth' => $parentId ? 1 : 0,
                'index' => 0,
                'resource_type' => $resourceType
            ]);
        } else if ($category['0']->parent_id != $parentId) {
            $category['0']->parent_id = $parentId;
            $category['0']->depth = $parentId ? 1 : 0;
            $category['0']->save();
        }
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
