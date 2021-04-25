<?php

namespace Insane\Journal\Console;

use App\Models\Team;
use Illuminate\Console\Command;
use Insane\Journal\Actions\CreateChartAccounts;
use Insane\Journal\Category;

class SetChartAccountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'journal:set-chart-accounts {teamId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set chart of accounts for team ';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->setAccountsCharts($this->argument('teamId'));
    }


    /**
     * Set general chart of accounts categories
     *
     * @return void
     */
    protected function setAccountsCharts($teamId)
    {
        $team = Team::find($teamId);
        (new CreateChartAccounts())->create($team);
    }
}
