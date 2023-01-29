<?php

namespace Insane\Journal\Http\Controllers;

use Insane\Journal\Models\Core\Category;

class CategoryController
{
    public function getWithClientBalance($uniqueField, $clientId) {
      $category = Category::byUniqueField($uniqueField, request()->user()->current_team_id);
      return $category->transactionBalance($clientId);
    }
}
