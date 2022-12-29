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
        $totalField = $this->getTotalField($formData);
        $amount = (double) $formData['amount'];
        $balance = (double) $amount + (double) $this->amount_paid;
        $total = (double) $this->$totalField;
        
        if ($balance <= $total || $balance <= $this->$totalField) {
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
                            $doc
                        ));
                    $payment->payable->save();
                }

                $this->save();
            });

            return $document;
        }
        $equal = $balance == $this->$totalField;
        throw new Exception("Payment of $balance exceeds document debt of {$this->$totalField} {$equal}");
    }
}
