<?php

namespace Insane\Journal\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        'document_date',
        'payment_method_id',
        'payment_method',
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

    public function scopeByPayable($query, $payableClass)
    {
        return $query->where('payable_type', $payableClass);
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
      $transactionData = $this->payable->createPaymentTransaction($this);

      $data = array_merge($transactionData, [
        "team_id" => $this->payable->team_id,
        "user_id" => $this->payable->user_id,
        "client_id" => $this->payable->client_id,
        "payee_id" => $this->payable->client_id,
        'status' => 'verified',
        'date' => $this->payment_date,
      ]);


      if ($transaction = $this->transaction) {
        $transaction->update($data);
      } else {
        $transaction = $this->transaction()->create($data);
      }
      $transaction->createLines($data['items']);
    }
}
