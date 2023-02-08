<?php
namespace Insane\Journal\Traits;

use Exception;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Payment;
use Insane\Journal\Models\Core\PaymentDocument;

trait HasPaymentDocuments
{
    protected static function boot()
    {
        parent::boot();
        static::saving(function ($payable) {
            self::calculateTotal($payable);
            self::checkPayments($payable);
        });
    }

    public function getTotalField() {
        return 'amount';
    }

    public function getTotal() {
      $totalField = $this->getTotalField();
      return $this->$totalField;
    }

    public function paymentDocuments()
    {
        return $this->morphMany(PaymentDocument::class, 'resource');
    }

    public static function checkPayments($payable)
    {
        if ($payable && $payable->paymentDocuments) {
            $totalPaid = $payable->paymentDocuments()->sum('amount');
            $statusField = $payable->getStatusField();
            $payable->amount_paid = $totalPaid;
            $payable->$statusField = self::checkStatus($payable);
        }
    }

    public function createPayment($formData)
    {
        $totalPaid = $this->paymentDocuments()->sum('amount');
        $amount = (double) $formData['amount'];
        $balance = (double) $amount + (double) $totalPaid;
        $total = (double) $this->getTotal();
        $left = abs($balance - $total) > 0.0001;
        if ($balance <= $total || $balance <= $this->$total || $left) {
            $document = null;

            DB::transaction(function () use($formData, $document) {
                $document = $this->paymentDocuments()->create(array_merge(
                    $formData,
                    [
                        'user_id' => $formData['user_id'] ?? $this->user_id,
                        'team_id' => $formData['team_id'] ?? $this->team_id,
                        'client_id' => $formData['client_id'] ?? $this->client_id,
                    ]
                ));

                Payment::query()->where('payment_document_id', $this->id)->delete();
                foreach ($formData['documents'] as $doc) {
                    $payment = $document->payments()->create(
                        array_merge(
                            $formData,
                            $doc,
                            [
                              "payment_date" => $formData['date'] ?? date('Y-m-d')
                            ]
                        ));
                    $payment->payable->save();
                }

                $this->save();
            });

            return $document;
        }
        throw new Exception(__("Payment of :balance exceeds document debt of :debt", [
          "balance" => $balance,
          "debt" => $total,
        ]));
    }

    public function prePaymentMeta($paymentData) {
      return  [];
    }

    public function postPaymentMeta($paymentDoc) {
      return  [];
    }
}
