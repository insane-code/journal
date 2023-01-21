<?php
namespace Insane\Journal\Traits;

use Exception;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Payment;
use Insane\Journal\Models\Core\Transaction;

trait HasPayments
{
    protected static function boot()
    {
        parent::boot();
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
            $payable->amount_due = $payable->amount - $totalPaid ;
            $statusField = $payable->getStatusField();
            $payable->$statusField = self::checkStatus($payable);
        }
    }

    public function createPayment($formData)
    {
        $formData['amount'] = $formData['amount'] > $this->debt ? $this->debt : $formData['amount'];
        return $this->payments()->create(array_merge(
            $formData,
            [
                'user_id' => $formData['user_id'] ?? $this->user_id,
                'team_id' => $formData['team_id'] ?? $this->team_id,
                'client_id' => $formData['client_id'] ?? $this->client_id,
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

    protected function createPaymentTransaction(Payment $payment) {
      $direction = $this->getTransactionDirection() ?? Transaction::DIRECTION_DEBIT;
      $counterAccountId = $this->getCounterAccountId();

      $transactionData = [
          "team_id" => $payment->team_id,
          "user_id" => $payment->user_id,
          "date" => $payment->payment_date,
          "description" => $payment->concept,
          "direction" => $direction,
          "total" => $payment->amount,
          "account_id" => $payment->account_id,
          "counter_account_id" => $counterAccountId
      ];

      $transaction = $payment->transaction()->create($transactionData);
      $transaction->createLines([]);
    }
}
