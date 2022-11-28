<?php
namespace Insane\Journal\Traits;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Payment;

abstract class HasPayments extends Model
{
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $statusField;
    
    protected static function booted()
    {
        static::saving(function ($payable) {
            self::calculateTotal($payable);
            self::checkPayments($payable);
        });

        static::deleting(function ($invoice) {
            Payment::where('payable_id', $invoice->id)
            ->where('payable_type', self::class)->delete();
        });
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public static function checkPayments($payable)
    {
        if ($payable && $payable->payments) {
            $totalPaid = $payable->payments()->sum('amount');
            $payable->amount_paid = $totalPaid;
            $statusField = $payable->statusField;
            $payable->$statusField = self::checkStatus($payable);
        }
    }

    public function createPayment($formData)
    {
        $formData['amount'] = $formData['amount'] > $this->debt ? $this->debt : $formData['amount'];

        return $this->payments()->create(array_merge(
            $formData,
            [
                'user_id' => $this->user_id,
                'team_id' => $this->team_id,
                'client_id' => $this->client_id
            ]
        ));
    }

    public function markAsPaid()
    {
        if ($this->debt <= 0) {
            throw new Exception("This document is already paid");
        }

        $formData = [
            "amount" => $this->debt,
            "payment_date" => date("Y-m-d"),
            "concept" => "Payment for invoice #{$this->number}",
            "account_id" => Account::guessAccount($this, ['cash_on_hand']),
            "category_id" => $this->contact_account_id,
            'user_id' => $this->user_id,
            'team_id' => $this->team_id,
            'client_id' => $this->client_id,
            'currency_code' => $this->currency_code,
            'currency_rate' => $this->currency_rate,
            'status' => 'verified'
        ];

        $this->payments()->create($formData);
        $this->save();

    }

    public function deletePayment($id)
    {
        Payment::find($id)->delete();
    }
}
