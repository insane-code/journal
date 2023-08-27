<?php

namespace Insane\Journal\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class Reconciliation extends Model
{
    protected $fillable = [
        'team_id',
        'user_id',
        'account_id',
        'date',
        'amount',
        'difference',
        'status',
    ];

 
}
