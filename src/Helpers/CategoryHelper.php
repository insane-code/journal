<?php

namespace Insane\Journal\Helpers;

use Illuminate\Support\Arr;
use Insane\Journal\Models\Core\Category;

class CategoryHelper
{
    public static function getSubcategories(string $teamId, array $displayIds = null, $depth = 0, $params = [])
    {
        $categoriesQuery = Category::where(array_merge([
            'depth' => $depth
        ], $params));
        if ($displayIds) {
            $categoriesQuery->whereIn('display_id', $displayIds);
        }
        $categories = $categoriesQuery->with([
            'subCategories',
            'subcategories.accounts' => function ($query) use ($teamId) {
                $query->where('team_id', '=', $teamId);
            }
        ])->get();

        return Arr::collapse(Arr::pluck($categories, 'subcategories'));
    }

    public static function getAccounts(string $teamId, array $displayIds, $depth = 1) {
        return Category::where([
            'depth' => $depth,
        ])
        ->whereIn('display_id', $displayIds)
        ->with([
            'accounts' => function ($query) use ($teamId) {
                $query->where('team_id', '=', $teamId);
            }
        ])->get();
    }
}
