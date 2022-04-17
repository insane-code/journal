<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Insane\Payment\Category;

class Accounting extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Category::factory()
            ->count(3)
            ->create(
                [[
                    "name" => 'Assets',
                    "resourceType" => 'ACCOUNT',
                ],
                [
                    "name" => 'Income',
                    "resourceType" => 'ACCOUNT'
                ],
                [
                    "name" => 'Expense',
                    "resourceType" => 'ACCOUNT'
                ]]
            );

        
    }
}
