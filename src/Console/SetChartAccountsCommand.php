<?php

namespace Insane\Journal\Console;

use App\Models\Team;
use Illuminate\Console\Command;
use Insane\Journal\Contracts\AccountCatalogCreates;

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
        $teamId = $this->argument('teamId');
        $team = Team::find($teamId);

        
        $accountCatalog = $this->app(AccountCatalogCreates::class);
        $accountCatalog->createChart($team);
        $accountCatalog->createCatalog($team);
    }
}
