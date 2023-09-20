<?php

namespace Insane\Journal\Contracts;

use Illuminate\Foundation\Auth\User;
use Insane\Journal\Models\Core\Account;

interface AccountDeletes {
    public function validate(User $user, Account $account);
    public function delete(User $user, Account $account);
}
