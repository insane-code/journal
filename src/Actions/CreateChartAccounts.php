<?php

namespace Insane\Journal\Actions;

use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\AccountDetailType;

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

        $generalInfo = [
            'team_id' => $team->id,
            'user_id' => $team->user_id,
        ];

        Account::where('team_id', $team->id)->delete();
        $accounts = config('journal.accounts_catalog');

        if (count($accounts)) {
            foreach ($accounts as $index => $account) {
                $detailType = isset($account['detail_type']) ? $account['detail_type'] : 'bank';
                Account::create(array_merge($account, $generalInfo, [
                    'index' => $index,
                    'type' => $account['balance_type'] == 'DEBIT' ? 1 : -1,
                    'account_detail_type_id' => AccountDetailType::where('name', $detailType)->first()->id
                ]));
            }
        }

    }
}
