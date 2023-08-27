<?php

namespace Insane\Journal\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class ReconciliationEntry extends Model
{
    protected $fillable = [
        'team_id',
        'user_id',
        'transaction_id',
        'transaction_line_id',
        'reconciliation_id',
        'matched',
    ];

 
}
