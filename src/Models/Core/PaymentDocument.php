<?php

namespace Insane\Journal\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'meta_data'
    ];

    protected $casts = [
      'meta_data' => 'array'
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

}
