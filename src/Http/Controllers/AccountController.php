<?php

namespace Insane\Journal\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Contracts\DeleteAccounts;
use Insane\Journal\Events\AccountCreated;
use Insane\Journal\Events\AccountUpdated;
use Insane\Journal\Helpers\ReportHelper;
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

    public function update(Account $account) {
        $postData = request()->post();
        $postData['user_id'] = request()->user()->id;
        $postData['team_id'] = request()->user()->current_team_id;
        $updatedAccount = $account->update($postData);
        AccountUpdated::dispatch($account, $postData);
        if (request()->query('json')) {
            return response()->sendContent($updatedAccount);
        }
        return redirect()->back();
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
        $account = Account::findOrFail($id);
        $deleter = app(DeleteAccounts::class);
        $deleter->validate($request->user(), $account);
        $deleter->delete($account);
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

    public function statements(Request $request, string $reportName = "income") {
        $filters = $request->query('filters');
        $accountId = $filters ? $filters['account'] : null;

        [
            "ledger" => $ledger,
            "categoryAccounts" => $categoryAccounts
        ] = ReportHelper::getGeneralLedger(request()->user()->current_team_id, $reportName, [
            "account_id" => $accountId
        ]);

        return Jetstream::inertia()->render($request, config('journal.statements_inertia_path') . '/Category', [
            "categories" => $categoryAccounts,
            "ledger" => $ledger->groupBy('display_id'),
            'categoryType' => $reportName
        ]);
    }
}
