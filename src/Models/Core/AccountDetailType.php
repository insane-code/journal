<?php

namespace Insane\Journal\Models\Core;

use Illuminate\Database\Eloquent\Model;

class AccountDetailType extends Model
{
    protected $fillable = ['team_id', 'name', 'description', 'label', 'config'];

    const CASH = 'cash';
    const BANK = 'bank'; 
    const CASH_ON_HAND = 'cash_on_hand';
    const SAVINGS =  'savings';
    const CREDIT_CARD = 'credit_card';
    const CLIENT_TRUST = 'client_trust_account';
    const MONEY_MARKET = 'money_market';
    const RENT_HELD_IN_TRUST = 'rent_held_in_trust';
    const EXPENSE = 'expense';

    const ALL = [self::CASH, self::BANK, self::CASH_ON_HAND, self::SAVINGS, self::CREDIT_CARD];
    const ALL_CASH = [self::CASH, self::BANK, self::CASH_ON_HAND, self::SAVINGS];

    protected $casts = [
        'config' => 'array'
    ];
}
