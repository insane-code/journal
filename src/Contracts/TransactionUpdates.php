<?php

namespace Insane\Journal\Contracts;

use Illuminate\Foundation\Auth\User;
use Insane\Journal\Models\Core\Transaction;

interface TransactionUpdates {
    public function validate(User $user, Transaction $transaction);
    public function update(User $user, Transaction $transaction, array $data);
}
