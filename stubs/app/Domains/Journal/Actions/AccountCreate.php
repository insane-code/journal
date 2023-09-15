<?php

namespace App\Domains\Journal\Actions;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Contracts\AccountUpdates;
use Insane\Journal\Models\Core\Account;

class AccountCreate implements AccountUpdates
{
   
    public function update(User $user, Account $account, array $accountData): Account
    {
        $this->validate($user, $account);
        $account = new Account();
        $account = Account::create([
            ...$accountData,
            "team_id" => $user->current_team_id,
            "user_id" => $user->id,
        ]);

        return $account;
    }

    public function validate(mixed $user, mixed $account)
    {
        Gate::forUser($user)->authorize('create', Account::class);   
    }
}


