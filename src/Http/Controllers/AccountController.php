<?php

namespace Insane\Journal\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Events\AccountCreated;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\AccountDetailType;
use Insane\Journal\Models\Core\Category;
use Laravel\Jetstream\Jetstream;


class AccountController
{

    public function __construct()
    {
        $this->model = new Account();
        $this->searchable = ['name'];
        $this->validationRules = [];
    }

    public function index(Request $request) {
        $category = $request->query('category');
        $teamId= $request->user()->current_team_id;
        if ($category) {
            $category = Category::where('display_id', $category)->get()->first();
            $accounts = $category->getAllAccounts();
        } else {
            $accounts =  Account::where([
                    'team_id' => $teamId
            ])->orderBy('index')->get();
        }
        return Jetstream::inertia()->render($request, config('journal.accounts_inertia_path') . '/Index', [
            "accounts" => $accounts->toArray(),
            "categories" => Category::where('depth', 0)->with([
                'subCategories',
                'subcategories.accounts' => function ($query) use ($teamId) {
                    $query->where('team_id', '=', $teamId);
                } ,
                'subcategories.accounts.lastTransactionDate',
            ])->get()->toArray(),
            'accountDetailTypes' => AccountDetailType::all(),
        ]);
    }

    public function store(Request $request, Response $response) {
        $postData = $request->post();
        $postData['user_id'] = $request->user()->id;
        $postData['team_id'] = $request->user()->current_team_id;
        $account = new Account();
        $account = $account::create($postData);
        AccountCreated::dispatch($account, $postData);
        if ($request->query('json')) {
            return $response->sendContent($account);
        }
        return Redirect()->back();
    }

    public function show(Request $request, $id) {
        $teamId = $request->user()->current_team_id;
        $account = Account::where('id', $id)->with(['transactions'])->get()->first();
        if ($account->team_id != $teamId) {
            Response::redirect('/accounts');
        }
        return Jetstream::inertia()->render($request, 'Journal/Accounts/Show', [
            "account" => $account,
            "transactions" => $account->transactions,
        ]);
    }

    public function destroy(Request $request, $id) {
        $teamId = $request->user()->current_team_id;
        $account = Account::where('id', $id)->with(['transactions'])->get()->first();
        if ($account->payee && $account->team_id != $teamId) {
            $account->payee->delete();
        }
        $account->delete();
        return Redirect()->back();
    }

    public function statementsIndex(Request $request, string $category = "income") {
        $categories = [
            "financial" => [
                "label" => "Financial Statements",
                "description" => "Get a clear picture of how your business is doing. Use these core statements to better understand your financial health.",
                "reports" => [
                    "balance-sheet" => [
                        "label" => "Balance Sheet",
                        "description" => "This statement shows the current balance of your accounts. It includes all of your accounts and their balances.",
                        "url" => "/statements/balance-sheet",
                    ],
                    "income-statement" => [
                        "label" => "Income Statement",
                        "description" => "This statement shows the income and expenses of your business. It includes all of your accounts and their balances.",
                        "url" => "/statements/income-statement",
                    ],
                    "cash-flow" => [
                        "label" => "Cash Flow",
                        "description" => "This statement shows the cash flow of your business. It includes all of your accounts and their balances.",
                        "url" => "/statements/cash-flow",
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
                        "url" => "/reports/tax",
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
                        "label" => "Account Balances",
                        "description" => "This statement shows the income and expenses of your business. It includes all of your accounts and their balances.",
                        "url" => "/statements/account-balance",
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

    public function statements(Request $request, string $category = "income") {
        $categories = [
            "income" => ["incomes"],
            "expense" => ["expenses"],
            "tax" => ["liabilities"],
            "balance-sheet" => ["assets", "liabilities", "equity"],
            "account-balance" => ["assets", "liabilities", "incomes", "expenses", "equity"],
        ];

        $categoryData = Category::whereIn('display_id', $categories[$category] )->get();

        $categoryIds = $categoryData->pluck('id')->toArray();

        $accountIds = DB::table('categories')
        ->whereIn('categories.parent_id', $categoryIds)
        ->selectRaw('group_concat(accounts.id) as account_ids, group_concat(accounts.name) as account_names')
        ->joinSub(DB::table('accounts')->where('team_id', $request->user()->current_team_id), 'accounts','category_id', '=', 'categories.id')
        ->get()->pluck('account_ids')->toArray();


        $accountIds = explode(",", $accountIds[0]);
        $balance = DB::table('transaction_lines')
        ->whereIn('transaction_lines.account_id', $accountIds)
        ->selectRaw('sum(amount * transaction_lines.type)  as total, transaction_lines.account_id, accounts.id, accounts.name, accounts.display_id')
        ->join('accounts', 'accounts.id', '=', 'transaction_lines.account_id')
        ->groupBy('transaction_lines.account_id')
        ->get()->toArray();


        $categoryAccounts = Category::where([
            'depth' => 1,
            ])
            ->whereIn('parent_id', $categoryIds)
            ->with([
            'accounts' => function ($query) use ($request) {
                $query->where('team_id', '=', $request->user()->current_team_id);
            },
            'category'
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
            'categoryType' => $category
        ]);
    }
}
