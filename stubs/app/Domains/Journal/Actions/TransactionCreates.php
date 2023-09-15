<?php

namespace Insane\Journal\Contracts;

use Illuminate\Foundation\Auth\User;

interface TransactionCreates {
    public function validate(User $user);
    public function create(User $user, array $accountData);
}
