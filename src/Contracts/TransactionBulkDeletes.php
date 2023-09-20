<?php

namespace Insane\Journal\Contracts;

use Illuminate\Foundation\Auth\User;

interface TransactionBulkDeletes {
    public function validate(User $user);
    public function deleteAllDrafts(User $user);
}
