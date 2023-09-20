<?php

namespace Insane\Journal\Contracts;

use Illuminate\Foundation\Auth\User;
use Insane\Journal\Models\Core\Transaction;

interface TransactionShows {
    public function validate(User $user, Transaction $transaction);
    public function show(User $user, Transaction $transaction);
}
