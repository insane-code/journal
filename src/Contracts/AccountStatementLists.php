<?php

namespace Insane\Journal\Contracts;

use Illuminate\Foundation\Auth\User;

interface AccountStatementLists {
    public function validate(User $user);
    public function list(User $user, string $categoryName): array;
}
