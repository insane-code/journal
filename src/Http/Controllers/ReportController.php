<?php

namespace Insane\Journal\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Insane\Journal\Helpers\CategoryHelper;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Category;
use Laravel\Jetstream\Jetstream;


class ReportController
{

    public function index(Request $request, string $category = "income") {
        $categories = [
            "financial" => [
                "label" => "Financial Statements",
                "description" => "Get a clear picture of how your business is doing. Use these core statements to better understand your financial health.",
                "reports" => [
                    "balance-sheet" => [
                        "label" => "Balance Sheet",
                        "description" => "This statement shows the current balance of your accounts. It includes all of your accounts and their balances.",
                        "url" => "/accounts/statements/balance-sheet",
                    ],
                    "income-statement" => [
                        "label" => "Income Statement",
                        "description" => "This statement shows the income and expenses of your business. It includes all of your accounts and their balances.",
                        "url" => "/accounts/statements/income-statement",
                    ],
                    "cash-flow" => [
                        "label" => "Cash Flow",
                        "description" => "This statement shows the cash flow of your business. It includes all of your accounts and their balances.",
                        "url" => "/accounts/statements/cash-flow",
                    ],
                ],
            ],
            "taxes" => [
                "label" => "Taxes",
                "description" => "Get a clear picture of how your business is doing. Use these core statements to better understand your financial health.",
                "reports" => [
                    "tax-return" => [
                        "label" => "Tax Return",
                        "description" => "This statement shows the income and expenses of your business. It includes all of your accounts and their balances.",
                        "url" => "/accounts/statements/tax-return",
                    ],
                ],
            ],
            "customers" => [
                "label" => "Customers",
                "description" => "Get a clear picture of how your business is doing. Use these core statements to better understand your financial health.",
                "reports" => [
                    "income-customers" => [
                        "label" => "Customer List",
                        "description" => "This statement shows the income and expenses of your business. It includes all of your accounts and their balances.",
                        "url" => "/accounts/statements/customer-list",
                    ],
                    "aged-receivables" => [
                        "label" => "Aged Receivables",
                        "description" => "This statement shows the income and expenses of your business. It includes all of your accounts and their balances.",
                        "url" => "/accounts/statements/aged-receivables",
                    ],
                ],
            ],
            "vendors" => [
                "label" => "Vendors",
                "description" => "Get a clear picture of how your business is doing. Use these core statements to better understand your financial health.",
                "reports" => [
                    "purchases-vendors" => [
                        "label" => "Purchases by Vendor",
                        "description" => "This statement shows the income and expenses of your business. It includes all of your accounts and their balances.",
                        "url" => "/accounts/statements/vendor-list",
                    ],
                    "aged-payables" => [
                        "label" => "Aged Payables",
                        "description" => "This statement shows the income and expenses of your business. It includes all of your accounts and their balances.",
                        "url" => "/accounts/statements/aged-payables",
                    ],
                ],
            ],
            "detailed" => [
                "label" => "Detailed Reporting",
                "description" => "Get a clear picture of how your business is doing. Use these core statements to better understand your financial health.",
                "reports" => [
                    "balances" => [
                        "label" => "Detailed Income",
                        "description" => "This statement shows the income and expenses of your business. It includes all of your accounts and their balances.",
                        "url" => "/accounts/statements/detailed-income",
                    ],
                    "trial_balance" => [
                        "label" => "Detailed Expenses",
                        "description" => "This statement shows the income and expenses of your business. It includes all of your accounts and their balances.",
                        "url" => "/accounts/statements/detailed-expenses",
                    ],
                    "transactions" => [
                        "label" => "Account Transactions",
                        "description" => "This statement shows the income and expenses of your business. It includes all of your accounts and their balances.",
                        "url" => "/accounts/statements/detailed-transactions",
                    ]
                ],
            ]
        ];

        return Jetstream::inertia()->render($request, config('journal.statements_inertia_path') . '/Index', [
            "categories" => $categories,
        ]);
    }

    public function category(Request $request, string $category = "income") {
        $startDate = $request->query("startDate");
        $endDate = $request->query("endDate");
        $reportType = $request->query("reportType");
        $teamId = $request->user()->currentTeam->id;

        $categories = [
            "tax" => "sales_taxes",
        ];

        $categoryData = Category::where('display_id', $categories[$category])->with('accounts')->first();
        $isTopLevel = $categoryData->parent_id == null;
        if ($isTopLevel) {
            $accounts = DB::table('categories')
            ->where('parent_id', $categoryData->id)
            ->selectRaw('group_concat(accounts.id) as account_ids, group_concat(accounts.name) as account_names')
            ->joinSub(DB::table('accounts')->where('team_id', $request->user()->current_team_id), 'accounts','category_id', '=', 'categories.id')
            ->get();


        } else {
            $accountIds =  $categoryData->accounts()->pluck('accounts.id')->toArray();
        }

        $balance = DB::table('transaction_lines')
        ->whereIn('transaction_lines.account_id', $accountIds)
        ->selectRaw('sum(amount * transaction_lines.type * accounts.type)  as total, transaction_lines.account_id, accounts.id, accounts.name, accounts.display_id')
        ->join('accounts', 'accounts.id', '=', 'transaction_lines.account_id')
        ->groupBy('transaction_lines.account_id')
        ->get()->toArray();


        $identifier = $isTopLevel ? 'parent_id' : 'id';
        $categoryAccounts = Category::where([
                'depth' => 1,
                "$identifier" => $categoryData->id,
            ])->with([
            'accounts' => function ($query) use ($request) {
                $query->where('team_id', '=', $request->user()->current_team_id);
            }
        ])->get()->toArray();


        $categoryAccounts = array_map(function ($subCategory) use($balance) {
            $total = [];
            if (isset($subCategory['accounts'])) {
                foreach ($subCategory['accounts'] as $accountIndex => $account) {
                    $index = array_search($account['id'], array_column($balance, 'id'));
                    if ($index !== false ) {
                        $subCategory['accounts'][$accountIndex]['balance'] = $balance[$index]->total;
                        $total[] = $balance[$index]->total;
                    }
                }
            }
            $subCategory['total'] = array_sum($total);
            return $subCategory;
        }, $categoryAccounts);

        return Jetstream::inertia()->render($request, config('journal.statements_inertia_path') . '/Category', [
            "categories" => $categoryAccounts,
            "accounts" => CategoryHelper::getAccounts($teamId, ['cash_and_bank']),
        ]);
    }

    public function statements(Request $request, string $category = "income") {
        $categories = [
            "income" => "incomes",
            "expense" => "expenses",
            "tax" => "liabilities"
        ];

        $categoryData = Category::where('display_id', $categories[$category] )->first();

        $accounts = DB::table('categories')
        ->where('parent_id', $categoryData->id)
        ->selectRaw('group_concat(accounts.id) as account_ids, group_concat(accounts.name) as account_names')
        ->joinSub(DB::table('accounts')->where('team_id', $request->user()->current_team_id), 'accounts','category_id', '=', 'categories.id')
        ->get();

        // dd($categoryData);

        $balance = DB::table('transaction_lines')
        ->whereIn('transaction_lines.account_id', explode(',', $accounts[0]->account_ids))
        ->selectRaw('sum(amount * transaction_lines.type * accounts.type)  as total, transaction_lines.account_id, accounts.id, accounts.name, accounts.display_id')
        ->join('accounts', 'accounts.id', '=', 'transaction_lines.account_id')
        ->groupBy('transaction_lines.account_id')
        ->get()->toArray();

        $categoryAccounts = Category::where([
                'depth' => 1,
                "parent_id" => $categoryData->id,
            ])->with([
            'accounts' => function ($query) use ($request) {
                $query->where('team_id', '=', $request->user()->current_team_id);
            }
        ])->get()->toArray();

        $categoryAccounts = array_map(function ($subCategory) use($balance) {
            $total = [];
            if (isset($subCategory['accounts'])) {
                foreach ($subCategory['accounts'] as $accountIndex => $account) {
                    $index = array_search($account['id'], array_column($balance, 'id'));
                    if ($index !== false ) {
                        $subCategory['accounts'][$accountIndex]['balance'] = $balance[$index];
                        $total[] = $balance[$index]->total;
                    }
                }
            }
            $subCategory['total'] = array_sum($total);
            return $subCategory;
        }, $categoryAccounts);

        return Jetstream::inertia()->render($request, config('journal.statements_inertia_path') . '/Category', [
            "categories" => $categoryAccounts,
            'categoryType' => $category
        ]);
    }
}
