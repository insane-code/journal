<?php

namespace Insane\Journal\Models\Core;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
    const BALANCE_TYPE_CREDIT = 'credit';
    const BALANCE_TYPE_DEBIT = 'debit';
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['balance'];

    protected $fillable = [
      'team_id',
      'user_id',
      'category_id',
      'account_detail_type_id',
      'client_id',
      'number',
      'display_id',
      'name',
      'description',
      'currency_code',
      'opening_balance',
      'index',
      'archivable',
      'balance_type',
      'type',
      'archived'
    ];

    protected static function booted()
    {
        static::creating(function ($account) {
            if (is_string($account->category_id)) {
                $account->category_id = Category::findOrCreateByName([
                    'team_id' => $account->team_id,
                    'user_id' => $account->user_id,
                ], $account->category_id);
            }

            if ($account->account_detail_type_id) {
                $detailType = AccountDetailType::find($account->account_detail_type_id);
                $account->balance_type = $detailType?->config['balance_type'] ?? self::BALANCE_TYPE_DEBIT;
                $account->category_id = $account->category_id ?? $detailType?->config['category_id'] ?? null;
                $account->type = $account->balance_type == self::BALANCE_TYPE_CREDIT ? -1 : 1;
            }

            self::setNumber($account);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function payee()
    {
        return $this->belongsTo(Payee::class);
    }

    public function detailType()
    {
        return $this->belongsTo(AccountDetailType::class, 'account_detail_type_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
    public function transactionLines()
    {
        return $this->hasMany(TransactionLine::class)->orderByDesc('date');
    }

    public function expense_transactions()
    {
        return $this->transactions()->where('type', Transaction::DIRECTION_CREDIT);
    }

    public function income_transactions()
    {
        return $this->transactions()->whereIn('type', Transaction::DIRECTION_DEBIT);
    }

    public function lastTransactionDate() {
        return $this->hasOneThrough(Transaction::class, TransactionLine::class, 'account_id', 'id')->orderByDesc('date')->limit(1);
    }

    /**
     * Get account balance.
     *
     * @return string
     */
    public function getBalanceAttribute()
    {
        return $this->transactionLines()
        ->where('transactions.status', 'verified')
        ->join('transactions', 'transactions.id', 'transaction_lines.transaction_id')
        ->sum(DB::raw("amount * type"));
    }

    public static function guessAccount($session, $labels, $data = []) {

        $accountSlug = Str::lower(Str::slug($labels[0], "_"));
        $account = Account::where(['user_id' => $session['user_id'], 'display_id' => $accountSlug])->limit(1)->get();
        if (count($account)) {
            return $account[0]->id;
        } else {
            $categoryId = null;
            if (isset($labels[1])) {
                $categoryId = Category::findOrCreateByName($session, $labels[1]);
            }
            $account = Account::create([
                'user_id' => $session['user_id'],
                'team_id' => $session['team_id'],
                'display_id' => $accountSlug,
                'name' => $labels[0],
                'description' => $labels[0],
                'currency_code' => $data['currency_code'] ?? "DOP",
                'category_id' => $categoryId,
            ]);

            return $account->id;
        }
    }

    public function addPayee() {
        return Payee::create([
            'team_id' => $this->team_id,
            'user_id' =>  $this->user_id,
            'name' => "Transfer: $this->name",
            'account_id' => $this->id
        ]);
    }

    public static function getByDetailTypes($teamId, $detailTypes = AccountDetailType::ALL) {
        return Account::where('accounts.team_id', $teamId)
        ->byDetailTypes($detailTypes)
        ->orderBy('accounts.index')
        ->get();
    }

    public function scopeByDetailTypes($query, array $detailTypes = AccountDetailType::ALL) {
      return $query
      ->join('account_detail_types', 'account_detail_types.id', '=', 'accounts.account_detail_type_id')
      ->whereIn('account_detail_types.name', $detailTypes)
      ->select('accounts.*');
    }

    public static function getByCategories($teamId, $categories = []) {
      return Account::where('accounts.team_id', $teamId)
      ->byCategories($categories)
      ->orderBy('accounts.index')
      ->get();
    }

     public function scopeByCategories($query, array $categories = []) {
      return $query
      ->join('categories', 'categories.id', '=', 'accounts.category_id')
      ->whereIn('categories.display_id', $categories)
      ->select('accounts.*');
    }

    //  Utils
    public static function setNumber($account)
    {
      if ($account->category) {
        $number = $account->category->lastSubcategoryNumber?->number + 1 ?? $account->category->number;
        $account->number = $number + 1;
      }
    }
}
