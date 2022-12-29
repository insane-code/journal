<?php
namespace Insane\Journal\Traits;

use Illuminate\Support\Str;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Category;

trait HasResourceAccounts
{
    public function setResourceAccount($fieldName, string $parentCategory, $client = null)
    {
        $this->$fieldName = $this->$fieldName ?? self::createResourceAccount($this, $parentCategory, $client);
    }

    public static function createResourceAccount($payable, string $parentCategory, $client = null)
    {
        if ($category = Category::where('display_id', $parentCategory)->first()) {
            $accountName = $client
            ? "{$payable->owner_id} {$payable->owner?->fullName}"
            : "{$category->number}-{$payable->shortName}";

          $accounts = Account::firstOrCreate([
            'display_id' =>  Str::slug($accountName, '_'),
            "category_id" => $category->id,
            'team_id' => $payable->team_id,
            'user_id' => $payable->user_id
          ], [
            "client_id" => $payable->owner_id,
            "currency_code" => "DOP",
            "name" => $accountName
          ]);

          return $accounts->id;
        }
        return null;
    }
}
