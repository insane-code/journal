<?php

namespace Insane\Journal\Contracts;

use Illuminate\Foundation\Auth\User;

interface TransactionLists {
    public function validate(User $user);
    public function list(User $user);
}
