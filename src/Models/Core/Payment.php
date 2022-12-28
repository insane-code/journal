<?php

namespace Insane\Journal\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'client_id',
        'payment_document_id',
        'payable_id',
        'payable_type',
        'payment_date',
        'concept',
        'notes',
        'account_id',
        'amount',
        'documents'
    ];

    protected $casts = [
        'documents' => 'array'
    ];

    protected static function booted()
    {
        static::created(function ($payment) {
           $payment->createTransaction();
        });

        static::deleting(function ($payment) {
            Transaction::where([
                'transactionable_id' => $payment->id,
                'transactionable_type' => Payment::class
            ])->delete();
        });
    }

    /**
     * Get all of the posts that are assigned this tag.
     */
    public function payable()
    {
        return $this->morphTo();
    }

    public function transaction() {
       return $this->morphOne(Transaction::class, "transactionable");
    }


    public function createTransaction() {
        $direction = $this->payable->getTransactionDirection() ?? Transaction::DIRECTION_DEBIT;
        $counterAccountId = $this->payable->getCounterAccountId();

        $accounts = [
          Transaction::DIRECTION_DEBIT => [
            "account_id" => $this->account_id,
            "counter_account_id" => $counterAccountId
          ],
          Transaction::DIRECTION_CREDIT => [
            "account_id" => $counterAccountId,
            "counter_account_id" => $this->account_id
          ]
        ];

        $transactionData = [
            "team_id" => $this->team_id,
            "user_id" => $this->user_id,
            "date" => $this->payment_date,
            "description" => $this->concept,
            "direction" => $direction,
            "total" => $this->amount,
            "account_id" => $accounts[$direction]['account_id'],
            "counter_account_id" => $accounts[$direction]['counter_account_id']
        ];

        $transaction = $this->transaction()->create($transactionData);
        $transaction->createLines([]);
    }
}
