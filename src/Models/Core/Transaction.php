<?php

namespace Insane\Journal\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Insane\Journal\Events\TransactionCreated;

class Transaction extends Model
{
    const DIRECTION_DEBIT = 'DEPOSIT';
    const DIRECTION_CREDIT = 'WITHDRAW';
    const DIRECTION_ENTRY = 'ENTRY';
    const STATUS_DRAFT = 'draft';
    const STATUS_VERIFIED = 'verified';
    const STATUS_CANCELED = 'canceled';

    protected $fillable = ['team_id','user_id', 'payee_id','transactionable_id', 'transactionable_type' , 'date','number', 'description', 'direction', 'notes', 'total', 'currency_code', 'status', 'transaction_category_id','category_id', 'account_id'];

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

    public function mainLine() {
        return $this->hasOne(TransactionLine::class)->where('anchor', true);
    }

    public function category() {
        return $this->hasOne(TransactionLine::class)->where('anchor', false);
    }

    public function transactionCategory() {
        return $this->belongsTo(Category::class, 'transaction_category_id');
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

    static public function createTransaction($transactionData) {
        $account = Account::find($transactionData['account_id']);
        $currencyCode = $transactionData['currency_code'] ?? $account->currency_code;
        $payeeId = $transactionData["payee_id"];
        if ($transactionData["payee_id"] == 'new') {
            $payee = Payee::findOrCreateByName($transactionData, $transactionData['payee_label'] ?? 'General Provider');
            $payeeId = $payee->id;
            $transactionData["payee_id"] = $payeeId;
        }
        $transaction = Transaction::where([
            "team_id" => $transactionData['team_id'],
            'date' => $transactionData['date'],
            'total' => $transactionData['total'],
            'description' => $transactionData['description'],
            'currency_code' => $currencyCode,
            'direction' => $transactionData['direction'],
            'payee_id' => $payeeId,
        ])->first();
        if ($transaction) {
            $transaction->updateTransaction($transactionData);
        } else {
            $transaction = Transaction::create($transactionData);
            $items = isset($transactionData['items']) ? $transactionData['items'] : [];
            $transaction->createLines($items);
        }
        
        TransactionCreated::dispatch($transaction);
        return $transaction;
    }

    public function updateTransaction($transactionData) {
        $this->update($transactionData);
        $items = isset($transactionData['items']) ? $transactionData['items'] : [];
        $this->createLines($items);
        return $this;
    }

    public function createLines($items = []) {
        TransactionLine::query()->where('transaction_id', $this->id)->delete();
        if (!count($items)) {
            $this->lines()->create([
                "amount" => $this->total,
                "concept" => $this->description,
                "index" => 0,
                "anchor" => 1,
                "type"=> $this->direction == 'DEPOSIT' ? 1 : -1,
                "account_id" => $this->account_id,
                "category_id" => 0,
                "team_id" => $this->team_id,
                "user_id" => $this->user_id
            ]);

            $this->lines()->create([
                "amount" => $this->total,
                "concept" => $this->description,
                "index" => 1,
                "type"=> $this->direction == 'DEPOSIT' ? -1 : 1,
                "account_id" => $this->category_id,
                "category_id" => 0,
                "team_id" => $this->team_id,
                "user_id" => $this->user_id
            ]);

        } else {
            foreach ($items as $item) {
                $this->lines()->create([
                    "amount" => $item['amount'],
                    "concept" => $item['concept'],
                    "index" => $item['index'],
                    "type"=> $item['type'],
                    "account_id" => $item['account_id'],
                    "category_id" => $item['category_id'],
                    "team_id" => $this->team_id,
                    "user_id" => $this->user_id
                ]);
            }
        }
    }

    public function remove() {
        TransactionLine::query()->where('transaction_id', $this->id)->delete();
        $this->delete();
    }

    public function scopeGetByMonth($query, $startDate = null, $endDate = null, $orderByDate = true) {
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
        })
        ->with(['mainLine', 'lines', 'category', 'mainLine.account', 'category.account']);
    }

    public function scopeByCategories($query, array $displayIds, $teamId) {
            $categories = Category::where([
                'categories.team_id' => $teamId,
            ])
            ->whereIn('categories.display_id', $displayIds)
            ->join('categories as sub', 'sub.parent_id', '=', 'categories.id')
            ->pluck('sub.id');
            return $query->whereIn('transaction_category_id', $categories);
    }

    public static function parser($transaction) {
        return [
            'id' => $transaction->id,
            'date' => $transaction->date,
            'number' => $transaction->number,
            'description' => $transaction->description,
            'direction' => $transaction->direction,
            'account' => $transaction->mainLine ? $transaction->mainLine->account: null,
            'category' => $transaction->mainLine ? $transaction->category->account : null,
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
