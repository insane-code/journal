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
        'account_name',
        'number',
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

        static::creating(function ($payment) {
          self::setNumber($payment);
        });

        static::saving(function ($payment) {
          $account =$payment->account ?? Account::find($payment->account_id);
          $payment->account_name = $account?->alias ?? $account?->name;
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

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function transaction() {
       return $this->morphOne(Transaction::class, "transactionable");
    }

       //  Utils
       public static function setNumber($payment)
       {
           $isInvalidNumber = true;
   
           if ($payment->number) {
               $isInvalidNumber = Payment::where([
                   "team_id" => $payment->team_id,
                   "number" => $payment->number,
               ])->whereNot([
                   "id" => $payment->id
               ])->get();
   
               $isInvalidNumber = count($isInvalidNumber);
           }
   
           if ($isInvalidNumber) {
               $result = Payment::where([
                   "team_id" => $payment->team_id,
               ])->max('number');
               $payment->number = $result + 1;
           }
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
