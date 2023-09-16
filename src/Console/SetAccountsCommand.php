<?php

namespace Insane\Journal\Console;

use Illuminate\Console\Command;
use Insane\Journal\Contracts\AccountDetailTypesCreates;

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
        $accountDetailsType = $this->app(AccountDetailTypesCreates::class);
        $accountDetailsType->create();
    }
}
