<?php

namespace Insane\Journal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $fillable = ['team_id','user_id', 'client_id', 'payable_id', 'payable_type', 'payment_date','concept', 'notes', 'account_id', 'amount'];
}
