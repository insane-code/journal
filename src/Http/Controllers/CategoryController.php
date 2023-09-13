<?php

namespace Insane\Journal\Http\Controllers;

use App\Domains\Journal\Actions\CategoryList;

final class CategoryController
{
  public function getWithClientBalance($uniqueField, $clientId) {
    $categoryClientBalances = app(CategoryList::class);
    return $categoryClientBalances->list(request()->user(), $uniqueField, $clientId);
  }
}
