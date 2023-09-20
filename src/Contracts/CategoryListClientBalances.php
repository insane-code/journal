<?php

namespace Insane\Journal\Contracts;

use Illuminate\Foundation\Auth\User;

interface CategoryListClientBalances {
    public function validate(User $user);
    public function list(User $user, string $uniqueCategoryId, int $clientId);
}
