<?php

namespace Insane\Journal\Http\Controllers;

use Illuminate\Http\Request;
use Insane\Journal\Contracts\AccountStatementLists;
use Insane\Journal\Contracts\AccountStatementShows;


final class AccountStatementController
{
    public function index(string $categoryName = "income") {
        $accountStatement = app(AccountStatementLists::class);

        return inertia(config('journal.statements_inertia_path') . '/Index', [
            "categories" => $accountStatement->list(request()->user(), $categoryName),
        ]);
    }

    public function show(Request $request, string $reportName = "income") {
        $filters = $request->query('filters');
        $accountId = $filters ? $filters['account'] : null;
        $accountStatement = app(AccountStatementShows::class);

        [
            "ledger" => $categoryAccounts,
        ] = $accountStatement->show(request()->user(), $reportName, $accountId);

        return inertia(config('journal.statements_inertia_path') . '/Category', [
            "categories" => $categoryAccounts,
            'categoryType' => $reportName
        ]);
    }
}
