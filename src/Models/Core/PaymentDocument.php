<?php

namespace Insane\Journal\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'notes'
    ];

    /**
     * Get all of the posts that are assigned this tag.
     */
    public function payable()
    {
        return $this->morphTo();
    }
    
    /**
     * Get all of the posts that are assigned this tag.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

  

}
