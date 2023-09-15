<?php

namespace Insane\Journal\Contracts;

use Illuminate\Foundation\Auth\User;
use Insane\Journal\Models\Core\Transaction;

interface TransactionDeletes {
    public function validate(User $user, Transaction $transaction);
    public function delete(User $user, Transaction $transaction);
}
