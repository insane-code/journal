<?php

namespace Insane\Journal\Contracts;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Models\Core\Transaction;

class TransactionShow implements TransactionShows {
    public function validate(User $user, Transaction $transaction) {
        Gate::forUser($user)->authorize('show', Transaction::class);
    }

    public function show(User $user, Transaction $transaction) {
        $this->validate($user, $transaction);

        return [
            ...$transaction->toArray(),
            ["lines" => $transaction->lines]
        ];
    }
}
