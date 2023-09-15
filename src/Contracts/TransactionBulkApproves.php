<?php

namespace Insane\Journal\Contracts;

use Illuminate\Foundation\Auth\User;

interface TransactionBulkApproves {
    public function validate(User $user);
    public function approveAllDrafts(User $user);
}
