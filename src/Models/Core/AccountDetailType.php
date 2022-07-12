<?php

namespace Insane\Journal\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountDetailType extends Model
{
    use HasFactory;
    protected $fillable = ['team_id', 'name', 'description', 'label', 'config'];

    const CASH = 'cash';
    const BANK = 'bank'; 
    const CASH_ON_HAND = 'cash_on_hand';
    const SAVINGS =  'savings';
    const CREDIT_CARD = 'credit_card';

    const ALL = [self::CASH, self::BANK, self::CASH_ON_HAND, self::SAVINGS, self::CREDIT_CARD];
}
