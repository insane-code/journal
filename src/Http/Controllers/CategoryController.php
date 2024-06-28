<?php

namespace Insane\Journal\Http\Controllers;

use Insane\Journal\Contracts\CategoryListClientBalances;

final class CategoryController
{
  public function getWithClientBalance($uniqueField, $clientId) {
    $categoryClientBalances = app(CategoryListClientBalances::class);
    return $categoryClientBalances->list(request()->user(), $uniqueField, $clientId);
  }
}
