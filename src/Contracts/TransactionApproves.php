<?php

namespace Insane\Journal\Contracts;

use Illuminate\Foundation\Auth\User;
use Insane\Journal\Models\Core\Transaction;

interface TransactionApproves {
    public function validate(User $user, Transaction $transaction);
    public function approve(User $user, Transaction $transaction);
}
