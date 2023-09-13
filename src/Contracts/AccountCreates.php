<?php

namespace Insane\Journal\Contracts;

use Illuminate\Foundation\Auth\User;

interface AccountCreates {
    public function validate(User $user);
    public function create(User $user, array $accountData);
}
