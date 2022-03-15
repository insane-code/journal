<?php

namespace Insane\Journal\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $fillable = ['team_id','user_id', 'client_id', 'payable_id', 'payable_type', 'payment_date','concept', 'notes', 'account_id', 'amount'];

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
        $transactionData = [
            "team_id" => $this->team_id,
            "user_id" => $this->user_id,
            "date" => $this->payment_date,
            "description" => $this->concept,
            "direction" => "DEPOSIT",
            "total" => $this->amount,
            "account_id" => $this->account_id,
            "category_id" => $this->payable->account_id
        ];

        $transaction = $this->transaction()->create($transactionData);
        $transaction->createLines([]);
    }
}
