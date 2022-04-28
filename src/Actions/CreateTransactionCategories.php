<?php

namespace Insane\Journal\Actions;

use Insane\Journal\Models\Core\Category;

class CreateTransactionCategories
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


        Category::where([
            'team_id' => $team->id,
            'resource' => 'transactions'
        ])->delete();
        $categories = config('journal.categories');

        Category::saveBulk($categories, $generalInfo);
    }
}
