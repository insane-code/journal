<?php

namespace Insane\Journal\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Insane\Journal\Events\TransactionCreated;
use Illuminate\Support\Str;

class Transaction extends Model
{
    const DIRECTION_DEBIT = 'DEPOSIT';
    const DIRECTION_CREDIT = 'WITHDRAW';
    const DIRECTION_ENTRY = 'ENTRY';
    const STATUS_DRAFT = 'draft';
    const STATUS_PLANNED = 'planned';
    const STATUS_VERIFIED = 'verified';
    const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'team_id',
        'user_id',
        'payee_id',
        'transactionable_id',
        'category_id',
        'counter_account_id',
        'account_id',
        'transactionable_type' ,
        'date',
        'number',
        'description',
        'direction',
        'notes',
        'total',
        'has_splits',
        'is_transfer',
        'currency_code',
        'status'
    ];

       /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {

        static::creating(function ($transaction) {
            self::setNumber($transaction);
        });

        static::deleting(function ($transaction) {
            TransactionLine::where('transaction_id', $transaction->id)->delete();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(team::class);
    }

    public function account() {
        return $this->belongsTo(Account::class);
    }

    public function counterAccount() {
        return $this->belongsTo(Account::class,  'counter_account_id');
    }

    public function mainLine() {
        return $this->hasOne(TransactionLine::class)->where('anchor', true);
    }

    public function splits() {
        return $this->hasMany(TransactionLine::class)->where('is_split', true);
    }

    public function counterLine() {
        return $this->hasOne(TransactionLine::class)->where('anchor', false);
    }

    public function category() {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function transactionable()
    {
        return $this->morphTo('transactionable');
    }

    public function payee() {
        return $this->belongsTo(Payee::class);
    }

    public function lines() {
        return $this->hasMany(TransactionLine::class);
    }

    //  Utils
    static public function setNumber($transaction) {
        $isInvalidNumber = true;
        if ($transaction->number) {
            $isInvalidNumber = Transaction::where([
                "team_id" => $transaction->team_id,
                "number" => $transaction->number,
            ])
            ->get();

            $isInvalidNumber = count($isInvalidNumber);
        }

        if ($isInvalidNumber) {
            $result = Transaction::where([
                "team_id" => $transaction->team_id,
            ])->max('number');
            $transaction->number = $result + 1;
        }
    }

    public function calculateTotal()
    {
        $total = TransactionLine::where([
            "transaction_id" =>  $this->id,
            "type" =>  1
        ])->sum('amount');
        $this->updateQuietly(['total' => $total]);
    }

    static public function sanitizeData($transactionData, Transaction $transaction = null) {
        $account = Account::find($transactionData['account_id']);
        $currencyCode = $transactionData['currency_code'] ?? $account->currency_code;
        $payeeId = $transactionData["payee_id"] ?? null;
        $isNewPayee = Str::contains($payeeId, "new::");
    
        if (!isset($transactionData["payee_id"]) && $transactionData["counter_account_id"]) {
            $payee = Payee::findOrCreateByName($transactionData, Account::find($transactionData["counter_account_id"]));
            $payeeId = $payee->id;
        } else if ($payeeId == 'new' || $isNewPayee) {
            $label = $transactionData['payee_label'] ?? trim(Str::replace('new::', '', $transactionData["payee_id"])) ?? 'General Provider';
            $payee = Payee::findOrCreateByName($transaction?->toArray() ?? $transactionData, $label);
            $payeeId = $payee->id;
            $transactionData["payee_id"] = $payeeId;
            $transactionData["counter_account_id"] = $payee->account_id;
        } else if ($transactionData["payee_id"]) {
            $payee = Payee::find($transactionData['payee_id']);
            $transactionData["counter_account_id"] = $transactionData["counter_account_id"] ?? $payee->account_id;
        }

        $transactionData['currency_code'] = $currencyCode;
        $transactionData['payee_id'] = $payeeId;

        return $transactionData;
    } 


    static public function createTransaction($transactionData) {
        $data = self::sanitizeData($transactionData);

        $transaction = Transaction::where([
            "team_id" => $data['team_id'],
            'date' => $data['date'],
            'total' => $data['total'],
            'description' => $data['description'],
            'currency_code' => $data['currencyCode'],
            'direction' => $data['direction'],
            'payee_id' => $data['payee_id'],
        ])->first();

        if ($transaction) {
            $transaction->updateTransaction($data);
        } else {
            $items = isset($data['items']) ? $data['items'] : [];
            $transaction = Transaction::create($data);
            $transaction->createLines($items);
        }

        TransactionCreated::dispatch($transaction);
        return $transaction;
    }

    public function updateTransaction($transactionData) {
        $data = self::sanitizeData($transactionData, $this);

        $this->update($data);
        $items = isset($data['items']) ? $data['items'] : [];
        $this->createLines($items);
        return $this;
    }

    public function guessPayee($data) {
        $payeeId = $data["payee_id"];
        if ($data["payee_id"] == 'new') {
           return Payee::findOrCreateByName($data, $data['payee_label'] ?? 'General Provider');
        } else if ($data["payee_id"]) {
           return Payee::find($payeeId);
        }
    }

    public function createLines($items = []) {
        TransactionLine::query()->where('transaction_id', $this->id)->delete();
        if (!count($items)) {
            $this->lines()->create([
                "amount" => $this->total,
                "date" => $this->date,
                "concept" => $this->description,
                "index" => 0,
                "anchor" => 1,
                "type"=> $this->direction == Transaction::DIRECTION_DEBIT ? 1 : -1,
                "account_id" => $this->account_id,
                "payee_id" => $this->payee_id,
                "category_id" => $this->category_id,
                "team_id" => $this->team_id,
                "user_id" => $this->user_id
            ]);

            $this->lines()->create([
                "amount" => $this->total,
                "date" => $this->date,
                "concept" => $this->description,
                "payee_id" => $this->payee_id,
                "index" => 1,
                "type"=> $this->direction == Transaction::DIRECTION_DEBIT ? -1 : 1,
                "account_id" => $this->counter_account_id ?? $this->payee?->account_id,
                "category_id" => 0,
                "team_id" => $this->team_id,
                "user_id" => $this->user_id
            ]);

        } else if ($this->has_splits) {
            foreach ($items as $item) {
                $payee = $this->guessPayee($item);

                $this->lines()->create([
                    "date" => $this->date,
                    "index" => 0,
                    "anchor" => 1,
                    "amount" => $item['amount'],
                    "concept" => $item['concept'] ?? "",
                    "payee_id" =>  $payee->id,
                    "type"=> $this->direction == Transaction::DIRECTION_DEBIT ? 1 : -1,
                    "account_id" => $item['account_id'] ?? $items[0]['account_id'],
                    "category_id" =>  $item['category_id'],
                    "team_id" => $this->team_id,
                    "user_id" => $this->user_id,
                    "is_split" => true,
                ]);

                $this->lines()->create([
                    "type"=> $this->direction == Transaction::DIRECTION_DEBIT ? -1 : 1,
                    "index" => 1,
                    "date" => $this->date,
                    "amount" => $item['amount'],
                    "concept" => $item['concept'] ?? "",
                    "category_id" => 0,
                    "account_id" => $payee?->account_id,
                    "payee_id" =>  $payee->id,
                    "team_id" => $this->team_id,
                    "user_id" => $this->user_id
                ]);
            }
        } else {
            foreach ($items as $item) {
                $this->lines()->create([
                    "date" => $this->date,
                    "amount" => $item['amount'],
                    "concept" => $item['concept'],
                    "index" => $item['index'],
                    "type"=> $item['type'],
                    "account_id" => $item['account_id'],
                    "category_id" => $item['category_id'],
                    "payee_id" => $item['payee_id'] ?? $this->payee_id,
                    "team_id" => $this->team_id,
                    "user_id" => $this->user_id
                ]);
            }
        }

        $this->calculateTotal();
    }

    public function remove() {
        TransactionLine::query()->where('transaction_id', $this->id)->delete();
        $this->delete();
    }

    public function scopeGetByMonth($query, $startDate = null, $endDate = null, $orderByDate = true, $with = ['mainLine', 'lines', 'category', 'mainLine.account', 'counterLine.account']) {
        $query
        ->when($startDate && !$endDate, function ($query) use ($startDate) {
            $query->where("date", '=',  $startDate);
        })
        ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
            $query->where("date", '>=',  $startDate);
            $query->where("date", '<=', $endDate);
        })
        ->when($orderByDate, function ($query) {
            $query->orderBy('date', 'desc');
        })->when($with, function ($query) use ($with) {
            $query->with($with);
        });
    }

    public function scopeByCategories($query, array $displayIds, $teamId) {
            $categories = Category::where([
                'categories.team_id' => $teamId,
            ])
            ->whereIn('categories.display_id', $displayIds)
            ->join('categories as sub', 'sub.parent_id', '=', 'categories.id')
            ->pluck('sub.id');
            return $query->whereIn('category_id', $categories);
    }

    public static function parser($transaction) {
        return [
            'id' => $transaction->id,
            'date' => $transaction->date,
            'number' => $transaction->number,
            'description' => $transaction->description,
            'direction' => $transaction->direction,
            'account' => $transaction->mainLine ? $transaction->mainLine->account: null,
            'category' => $transaction->mainLine ? $transaction->category : null,
            'total' => $transaction->total,
            'lines' => $transaction->lines,
            'mainLine' => $transaction->mainLine,
        ];
    }

    public function approve() {
        $this->status = self::STATUS_VERIFIED;
        $this->save();
    }
}
