<?php

namespace Insane\Journal\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Observers\PaymentDocumentObserver;

class PaymentDocument extends Model
{
    use HasFactory;
    protected $fillable = [
        'team_id',
        'user_id',
        'client_id',
        'account_id',
        'reference',
        'resource_id',
        'resource_type',
        'payment_date',
        'concept',
        'amount',
        'notes',
        'documents',
        'meta_data'
    ];

    protected $casts = [
      'meta_data' => 'array',
      'documents' => 'array'
    ];

    protected static function booted()
    {
        static::observe(PaymentDocumentObserver::class);
    }

    /**
     * Get all of the posts that are assigned this tag.
     */
    public function payable()
    {
        return $this->morphTo('resource');
    }

    /**
     * Get all of the posts that are assigned this tag.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function updateDocument($formData = [])
    {
        $totalPaid = $this->payable->paymentDocuments()->sum('amount') - $this->total;
        $amount = (double) isset($formData['amount']) ? $formData['amount'] : $this->total;
        $balance = (double) $amount + (double) $totalPaid;

        // if ($balance <= $this->total || $balance <= $this->total) {
            $document = null;

            DB::transaction(function () use($formData, $document) {
                $this->update(array_merge(
                    $formData,
                    [
                        'user_id' => $this->user_id,
                        'team_id' => $this->team_id,
                        'client_id' => $formData['client_id'] ?? $this->client_id,
                    ]
                ));

                Payment::query()->where('payment_document_id', $this->id)->delete();
                $documents = $formData['documents'] ?? $this->documents;
                foreach ($documents as $doc) {
                  $payment = $this->payments()->create(
                  array_merge(
                      $formData,
                      $doc,
                      [
                        "team_id" => $this->team_id,
                        "user_id" => $this->user_id,
                        "client_id" => $this->client_id,
                        "description" => $this->description,
                        "payment_date" => $formData['date'] ?? $this->payment_date ?? date('Y-m-d')
                      ]
                  ));
                    $payment->payable->save();
                }

                $this->save();
            });

            return $document;
        // }
    }

}
