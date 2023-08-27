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
            self::checkStatus($payable);
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
            $payments = $payable->paymentDocuments()->selectRaw('COALESCE(sum(amount), 0) total, count(id) count')->first();
            $payable->amount_paid = $payments->total;
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
            $document = DB::transaction(function () use($formData, $document) {
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
                              "payment_date" => $document->payment_date ?? date('Y-m-d')
                            ]
                        ));
                    $payment->payable->save();
                }
                $this->save();
                $document->fresh()->autoUpdateMetaData();
                return $document;
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
