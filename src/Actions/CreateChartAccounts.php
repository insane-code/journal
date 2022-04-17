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
        $detailTypes = [
            [
                'name' => 'bank',
                'label' => 'Bank',
                'description' => "Use Bank accounts to track all your current activity, including debit card transactions.
                Each current account your company has at a bank or other financial institution should have its own Bank type account in QuickBooks Online Simple Start.",
                'config' => [],
            ],
            [
                'name' => 'cash',
                'label' => 'Cash and cash equivalents',
                'description' => "Use Cash and Cash Equivalents to track cash or assets that can be converted into cash immediately. For example, marketable securities and Treasury bills.",
                'config' => [],
            ],
            [
                'name' => 'cash_on_hand',
                'label' => 'Cash on hand',
                'description' => "Use a Cash on hand account to track cash your company keeps for occasional expenses, also called petty cash.
                To track cash from sales that have not been deposited yet, use a pre-created account called Undeposited funds, instead.",
                'config' => [],
            ],
            [
                'name' => 'client_trust_account',
                'label' => 'Client trust account',
                'description' => "Use a Cash on hand account to track cash your company keeps for occasional expenses, also called petty cash.
                To track cash from sales that have not been deposited yet, use a pre-created account called Undeposited funds, instead.",
                "config" => [],
            ],
            [
                'name' => 'money_market',
                'label' => 'Money market',
                'description' => "Use Money market to track amounts in money market accounts.
                For investments, see Current Assets, instead.",
                'config' => []
            ],
            [
                'name' => 'rent_held_in_trust',
                'label' => 'Rent held in trust',
                'description' => "Use Rents held in trust to track deposits and rent held on behalf of the property owners.
                Typically only property managers use this type of account.",
                'config' => []
            ],
            [
                'name' => 'savings',
                'label' => 'Savings',
                'description' => "Use Savings accounts to track your savings and CD activity.
                Each savings account your company has at a bank or other financial institution should have its own Savings type account.
                
                For investments, see Current Assets, instead.",
                'config' => []
            ],
        ];

        $generalInfo = [
            'team_id' => $team->id,
            'user_id' => $team->user_id,
        ];

        foreach ($detailTypes as $detailType) {
            AccountDetailType::create(
                array_merge(
                    $detailType,
                    [
                        'config' => json_encode($detailType['config']),
                    ]

            ));
        }

        Account::where('team_id', $team->id)->delete();
        $accounts = config('journal.accounts_catalog');

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
