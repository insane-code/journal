<?php
namespace Insane\Journal\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Category;
use Insane\Journal\Models\Core\Transaction;

abstract class Transactionable extends Model
{
    protected $creditCategory;
    protected $creditAccount;

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($transactionable) {
            $transactionable->client_account_id = $transactionable->client_account_id ?? self::createContactAccount($transactionable);
            $transactionable->account_id = $transactionable->account_id ?? self::createPayableAccount($transactionable);
        });

        static::deleting(function ($transactionable) {
            Transaction::where('transactionable_id', $transactionable->id)
            ->where('transactionable_type', self::class)->delete();
        });
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function account_client()
    {
        return Account::where('client_id', $this->client_id)->limit(1)->get()[0];
    }

    public function transaction() {
        return $this->morphOne(Transaction::class, "transactionable");
    }

    // accounting
    public static function createContactAccount($payable)
    {
        $categoryName = self::getCategoryName($payable);
        $category = Category::where('display_id', $categoryName)->first();
        $accountDisplayId = "client_{$payable->client_id}_{$payable->client?->names}";

        $account = Account::firstOrCreate([
            "team_id" => $payable->team_id,
            'client_id' => $payable->client_id,
            'category_id' => $category->id,
            'display_id' => $accountDisplayId
        ], [
            "user_id" => $payable->user_id,
            "name" => "{$payable->client->names} Account",
            "currency_code" => "DOP"
        ]);
       
        return $account->id;
    }

    public static function createPayableAccount($payable)
    {
        $category = Category::where('display_id', $payable->creditCategory)->first();
        $accountDisplayId = Str::slug($payable->creditAccount, '_');
        $accounts = Account::firstOrCreate([
            'display_id' =>  $accountDisplayId,
            "category_id" => $category->id,
            'team_id' => $payable->team_id,
            'user_id' => $payable->user_id
        ], [
            "currency_code" => "DOP",
            "name" => $payable->creditAccount
        ]);

        return $accounts->id;
    }

    public function createTransaction(mixed $formData = [])
    {
        $formData['team_id'] = $this->team_id;
        $formData['user_id'] = $this->user_id;
        $formData['resource_id'] = $this->id;
        $formData['transactionable_id'] = self::class;
        $formData['date'] = isset($formData['date']) ? $formData['date'] : date('Y-m-d');
        $formData["description"] = isset($formData["description"]) ? $formData["description"] : $this->getTransactionDescription();
        $formData["direction"] = isset($formData["direction"]) ? $formData["direction"] : $this->getTransactionDirection();
        $formData["total"] = isset($formData["total"]) ? $formData["total"] : $this->total;
        $formData["account_id"] = isset($formData['account_id']) ? $formData['account_id'] : $this->getAccountId();
        $formData["counter_account_id"] = $this->client_account_id;
        $formData["status"] = "verified";
        
        if ($this->transaction) {
            $transaction = $this->transaction()->update($formData);
        } else {
            $transaction = $this->transaction()->create($formData);
        }
        $items = $this->getTransactionItems();
        $transaction->createLines($items);
        return $transaction;
    }

    abstract function getTransactionItems();
    abstract function getTransactionDescription();
    abstract function getTransactionDirection();
    abstract function getAccountId();
    abstract function getCounterAccountId();
}
