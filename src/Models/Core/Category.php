<?php

namespace Insane\Journal\Models\Core;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;
    protected $fillable = [
      'team_id',
      'user_id',
      'client_id',
      'parent_id',
      'display_id',
      'account_id',
      'number',
      'name',
      'alias',
      'description',
      'depth',
      'index',
      'type',
      'color',
      'resource_type'
    ];

    public static function booted() {
        static::creating(function ($category) {
            if (!$category->display_id) {
               $category->display_id = Str::Slug($category->name, "_");
            }
            self::setNumber($category);
        });
    }

    public function subCategories() {
        return $this->hasMany(self::class, 'parent_id', 'id')->orderBy('index');
    }

    public function lastSubcategoryNumber() {
      return $this->hasOne(self::class, 'parent_id', 'id')->latest('number');
    }

    public function lastAccountNumber() {
      return $this->hasOne(Account::class, 'category_id', 'id')->latest('number');
    }

    public function category() {
        return $this->belongsTo(Category::class, 'parent_id', 'id');
    }

    public function account() {
        return $this->belongsTo(Account::class);
    }

    public function accounts() {
        return $this->hasMany(Account::class, 'category_id', 'id')->orderBy('index');
    }

    public static function findOrCreateByName($session, string $name, int $parentId = null, string $resourceType = 'transactions') {
        $category = Category::where(function($query) use ($name) {
           return $query->where('display_id', Str::lower(Str::slug(trim($name), "_")))->orWhere('name', trim($name));
        })->where(function ($query) use ($session) {
            return $query->where('team_id', $session['team_id'])->orWhere('team_id', 0);
        })->first();

        if (!$category) {
            $category = Category::create([
                'display_id' => Str::slug(trim($name), "_"),
                'name' => trim($name),
                'parent_id' => $parentId,
                'user_id' => $session['user_id'],
                'team_id' => $session['team_id'],
                'depth' => $parentId ? 1 : 0,
                'index' => 0,
                'resource_type' => $resourceType
            ]);

            return $category->id;
        } else if ( $parentId && $category->parent_id != $parentId) {
            $category->parent_id = $parentId;
            $category->depth = $parentId ? 1 : 0;
            $category->save();
        }
        return $category ? $category->id : null;
    }

    public static function byUniqueField(string $uniqueId, $teamId = 0) {
      $category = Category::where(function($query) use ($uniqueId) {
         return $query
         ->where('display_id', Str::slug($uniqueId, "_"))
         ->orWhere('name', $uniqueId)
         ->orWhere('id', $uniqueId);
      })->where(function ($query) use ($teamId) {
          return $query->where('team_id', $teamId)->orWhere('team_id', 0);
      })->first();

      return $category;
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
            return Account::whereIn('id', $accountIds)->pluck('accounts.id')->toArray();
        } else {
            return $this->accounts->pluck('id')->toArray();
        }
    }

    public static function saveBulk(mixed $categories, mixed $extraData) {

        foreach ($categories as $index => $category) {
            $newCategory = array_merge($category, $extraData, ['index' => $index]);
            unset($newCategory['childs']);
            $parentCategory = Category::create($newCategory);



            if (isset($category['childs'])) {
                Category::saveBulk($category['childs'], array_merge(
                  $extraData, [
                    'depth' => $extraData['depth'] + 1,
                    'type' => $parentCategory->type,
                    'parent_id' => $parentCategory->id
                  ]));
            }
        }
    }

    //  Utils
    public static function setNumber($category)
    {
        if ($category->depth) {
            $number = $category->category->lastSubcategoryNumber?->number ?? $category->category->number;
            $category->number = $number + 100;
        }
    }

    public function transactionBalance($clientId) {
      return TransactionLine::selectRaw("sum(amount * transaction_lines.type) balance, accounts.*, categories.name cat_name")
        ->whereIn('transaction_lines.account_id', $this->accounts->pluck('id')->toArray())
        ->where([
          'payee_id' => $clientId,
        ])
        ->groupBy('transaction_lines.account_id', 'payee_id')
        ->join('accounts', 'accounts.id', 'transaction_lines.account_id')
        ->leftJoin('categories', 'categories.id', 'accounts.category_id')
        ->get();
    }


    public function scopeHasAccounts($query, mixed $accountIds) {
      if ($accountIds) {
        return $query->whereHas('accounts', function($query) use ($accountIds) {
          $query->when($accountIds, function($q) use ($accountIds) {
            $q->whereIn('accounts.id', $accountIds);
          });
        });
      }
      return $query;
    }

    public static function getCatalog($teamId) {
        return Category::where([
            'categories.team_id' => $teamId,
            'categories.resource_type' => 'accounts'
        ])
            ->whereNull('parent_id')
            ->orderBy('index')
            ->with(['subCategories'])
            ->get();
    }
}
