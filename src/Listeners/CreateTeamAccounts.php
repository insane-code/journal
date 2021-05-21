<?php

namespace Insane\Journal\Listeners;

use App\Models\Setting;
use Insane\Journal\Account;
use Laravel\Jetstream\Events\TeamCreated;

class CreateTeamAccounts
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(TeamCreated $event)
    {
        $team = $event->team;
        $this->setAccountsCharts($team);
        $this->createJournalSettings($team);
    }

    /**
     * Set general chart of accounts categories
     *
     * @return void
     */
    protected function setAccountsCharts($team)
    {
        Account::where('team_id', $team->id)->delete();
        $accounts = config('journal.accounts_catalog');
        $generalInfo = [
            'team_id' => $team->id,
            'user_id' => $team->user_id,
        ];

        foreach ($accounts as $index => $account) {
            Account::create(array_merge($account, $generalInfo, ['index' => $index]));
        }
    }

    public function createJournalSettings($team) {
        $settings = [
            [
                "name" => "journal_invoice_account",
                "value" => ''
            ],
            [
                "name" => "journal_client_account",
                "value" => ''
            ],
            [
                "name" => "journal_product_account",
                "value" => ''
            ],
            [
                "name" => "journal_small_box",
                "value" => ''
            ],
        ];

        foreach ($settings as $setting) {
            Setting::create(array_merge($setting, [
                'user_id' => $team->user_id,
                'team_id' => $team->id
            ]));
        }
    }
}
