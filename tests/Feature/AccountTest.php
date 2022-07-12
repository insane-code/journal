<?php

namespace Insane\Journal\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Tests\TestCase;

class AccountTest extends TestCase
{
    use DatabaseMigrations;

    public function create_account($args = [], $num = null)
    {
        return Account::create([
            
        ]);
    }
}
