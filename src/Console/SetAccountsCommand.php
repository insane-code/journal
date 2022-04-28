<?php

namespace Insane\Journal\Console;

use Illuminate\Console\Command;
use Insane\Journal\Models\Core\Category;

class SetAccountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'journal:set-accounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set starting account charts';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->setAccountsCharts();
    }


    /**
     * Set general chart of accounts categories
     *
     * @return void
     */
    protected function setAccountsCharts()
    {
        Category::where('team_id', 0)->delete();
        $categories = config('journal.accounts_categories');
        $generalInfo = [
            'team_id' => 0,
            'user_id' => 0,
            'depth' => 0
        ];

        Category::saveBulk($categories, $generalInfo);
    }
}
