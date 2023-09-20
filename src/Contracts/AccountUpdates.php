<?php

namespace Insane\Journal\Contracts;

use Illuminate\Foundation\Auth\User;
use Insane\Journal\Models\Core\Account;

interface AccountUpdates {
    public function validate(User $user, Account $account);
    public function update(User $user, Account $account, array $accountData): Account;
}
