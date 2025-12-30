<?php

namespace Insane\Journal\Models\Core;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Insane\Journal\Events\AccountCreated;
use Insane\Journal\Events\AccountDeleted;
use Insane\Journal\Events\AccountUpdated;
use Insane\Journal\Models\Accounting\Reconciliation;

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
      'alias',
      'description',
      'currency_code',
      'opening_balance',
      'credit_limit',
      'credit_closing_day',
      'index',
      'archivable',
      'balance_type',
      'type',
      'archived'
    ];

    protected $dispatchesEvents = [
        'created' => AccountCreated::class,
        'saved' => AccountUpdated::class,
        'deleted' => AccountDeleted::class,
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

            if ($account->category) {
              $account->type = $account->category->type;
              $account->balance_type = $account->type == 1 ?  self::BALANCE_TYPE_DEBIT: self::BALANCE_TYPE_CREDIT ;
            }

            if ($account->account_detail_type_id && !$account->category) {
                $detailType = AccountDetailType::find($account->account_detail_type_id);
                $account->balance_type = $detailType?->config['balance_type'] ?? self::BALANCE_TYPE_DEBIT;
                $account->type = $account->balance_type == self::BALANCE_TYPE_CREDIT ? -1 : 1;
                $account->category_id = $account->category_id ?? $detailType?->config['category_id'] ?? null;
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

    public function reconciliationLast()
    {
        return $this->hasOne(Reconciliation::class)->orderByDesc('date');
    }

    public function reconciliations()
    {
        return $this->hasMany(Reconciliation::class)->orderByDesc('date');
    }

    public function transactionSplits($limit = 25, $startDate, $endDate, $filters = [])
    {
        $status = $filters['status'] ?? Transaction::STATUS_VERIFIED;
        return Transaction::whereHas('lines', function ($query) use($filters) {
            $query->where('account_id', $this->id)
            ->when($filters['direction'] ?? null, fn($q) => $q->where('type', $filters['direction']));
        })
        ->with(['splits','payee', 'category', 'splits.payee','account', 'counterAccount'])
        ->where('transactions.status', $status)
        ->orderByDesc('date')
        ->whereBetween('date', [$startDate, $endDate])
        ->when($limit, fn($q) => $q->limit($limit))
        ->filterLine($filters)
        ->get();
    }

    public function transactionsToReconcile($startDate, $endDate)
    {
        return DB::table("transaction_lines")->selectRaw("
            transaction_lines.id AS id,
            transaction_lines.transaction_id,
            transaction_lines.team_id,
            transaction_lines.user_id
        ")->where([
            'transaction_lines.account_id' => $this->id,
            'transactions.status' => Transaction::STATUS_VERIFIED
        ])
        ->whereNull('reconciliation_entries.id')
        ->join('transactions', 'transactions.id', 'transaction_lines.transaction_id')
        ->leftJoin('reconciliation_entries', 'reconciliation_entries.transaction_line_id', 'transaction_lines.id')
        ->orderByDesc('transactions.date')
        ->when($startDate && $endDate, fn($q) => $q->whereBetween('transactions.date', [$startDate, $endDate]))
        ->when($endDate, fn($q) => $q->where('transactions.date', '<=', $endDate))
        ->get();
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
        ->where('transactions.status', Transaction::STATUS_VERIFIED)
        ->join('transactions', 'transactions.id', 'transaction_lines.transaction_id')
        ->sum(DB::raw("amount * type"));
    }

    public static function guessAccount($session, $labels, $data = []) {
        $accountSlug = Str::lower(Str::slug($labels[0], "_"));
        $account = Account::where(['team_id' => $session['team_id'], 'display_id' => $accountSlug])->limit(1)->get();
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

    public static function findByDisplayId(string $name, int $teamId) {
      return Account::where(function ($query) use ($name) {
          return $query->where('display_id', Str::lower(Str::slug($name, "_")))->orWhere('name', $name);
      })->where('team_id', $teamId)->first();
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
        ->with(['reconciliationLast'])
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
        $number = $account->category->lastAccountNumber()->where('team_id', $account->team_id)->first()?->number ?? $account->category->number;
        $account->number = $number + 1;
      }
    }

    public function openBalance($amount, $type = 1, $counterAccountId = null) {
      $formData = [
        'account_id' => $this->id,
        'team_id' => $this->team_id,
        'user_id' => $this->user_id,
        'date' => Carbon::now()->format('Y-m-d H:i:s'),
        'payee_id' => 'new',
        'payee_label' => $this->user->name,
        'currency_code' => $this->currency_code ?? "DOP",
        'counter_account_id' => $counterAccountId ?? Account::guessAccount($this, ['opening_balance_capital', 'business_owner_contribution']),
        'description' => 'Starting Balance',
        'direction' => $type == 1 ? Transaction::DIRECTION_DEBIT : Transaction::DIRECTION_CREDIT,
        'total' => $amount,
        'items' => [],
        'status' => Transaction::STATUS_VERIFIED,
        'metaData' => json_encode([
              "resource_id" => "SYSTEM:$this->id",
              "resource_origin" => 'SYSTEM',
              "resource_type" => 'transaction',
          ])
      ];

      Transaction::createTransaction($formData);
    }

    public function getVerifiedTransactionLines() {
        return DB::table('transaction_lines')
            ->selectRaw("COALESCE(SUM(amount * transaction_lines.type), 0) as balance")
            ->where('transaction_lines.account_id', $this->id)
            ->join('transactions', fn ($q) => $q->on('transactions.id', 'transaction_lines.transaction_id')
                ->where('status', Transaction::STATUS_VERIFIED)
        );
    }

    public function getMonthBalance(string $yearMonth, bool $hasCategories = false)
    {
        if (!$this->resource_type_id) {
            return $this->transactionLines()
            ->whereHas('transaction', fn ($q) => $q->where('status', Transaction::STATUS_VERIFIED))
            ->whereRaw("date_format(transaction_lines.date, '%Y-%m') = '$yearMonth'")
            ->selectRaw("COALESCE(SUM(amount * type), 0) as balance")
            ->when($hasCategories, fn ($q) => $q->whereRaw('(category_id IS NOT NULL AND category_id != 0)'))
            ->first();
        } else {
            return $this->creditLines()
            ->whereRaw("date_format(date, '%Y-%m') = '$yearMonth'")
            ->sum(DB::raw("amount * type"));
        }
    }

    public function getMonthFundedSpending(string $yearMonth)
    {
        if (!$this->resource_type_id) {
            return $this->getVerifiedTransactionLines()
            ->whereRaw('(transaction_lines.category_id IS NOT NULL AND transaction_lines.category_id != 0)')
            ->whereRaw("date_format(transaction_lines.date, '%Y-%m') = '$yearMonth'")
            ->where('type', -1)
            ->first();
        }
    }

    public function getMonthPayments(string $yearMonth)
    {
        if (!$this->resource_type_id) {
            $result = $this->getVerifiedTransactionLines()
            ->whereRaw("date_format(transaction_lines.date, '%Y-%m') = '$yearMonth'")
            ->where('type', 1);

            return $result->first();
        }
    }
}
