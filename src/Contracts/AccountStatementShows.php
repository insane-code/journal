<?php

namespace Insane\Journal\Contracts;

use Illuminate\Foundation\Auth\User;

interface AccountStatementShows {
    public function validate(User $user);
    public function show(User $user, string $reportName, ?int $accountId): array;
}
