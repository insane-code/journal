<?php

namespace Insane\Journal\Actions;

use Insane\Journal\Account;

class CreateChartAccounts
{
    /**
     * Validate and create a new team for the given user.
     *
     * @param  mixed  $user
     * @param  array  $input
     * @return mixed
     */
    public function create($team)
    {
        Account::where('team_id', $team->id)->delete();
        $accounts = config('journal.accounts_catalog');
        $generalInfo = [
            'team_id' => $team->id,
            'user_id' => $team->user_id,
        ];

        foreach ($accounts as $index => $account) {
            Account::create(array_merge($account, $generalInfo, [
                'index' => $index,
                'type' => $account['balance_type'] == 'DEBIT' ? 1 : -1
            ]));
        }
    }
}
