<?php

namespace Insane\Journal\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Account;
use Insane\Journal\Category;
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
        return Jetstream::inertia()->render($request, config('journal.accounts_inertia_path') . '/Index', [
            "accounts" => Account::orderBy('index')->get(),
            "categories" => Category::where('depth', 0)->with([
                'subCategories',
                'subcategories.accounts' => function ($query) use ($request) {
                    $query->where('team_id', '=', $request->user()->current_team_id);
                } ,
                'subcategories.accounts.lastTransactionDate'
            ])->get(),
        ]);
    }

    public function store(Request $request, Response $response) {
        $postData = $request->post();
        $postData['user_id'] = $request->user()->id;
        $postData['team_id'] = $request->user()->current_team_id;
        $account = new Account();
        $account = $account::create($postData);
        return $response->sendContent($account);
    }

    public function show(Request $request) {
        return Jetstream::inertia()->render($request, 'Journal/Accounts/Show', [
            "accounts" => Account::orderBy('index')->get(),
        ]);
    }

    public function statements(Request $request, string $category = "income") {
        $categories = [
            "income" => "incomes",
            "expense" => "expenses",
            "tax" => "taxes"
        ];

        $categoryData = Category::where('display_id',$categories[$category] )->first();

        $accounts = DB::table('categories')
        ->where('parent_id', $categoryData->id)
        ->selectRaw('group_concat(accounts.id) as account_ids, group_concat(accounts.name) as account_names')
        ->joinSub(DB::table('accounts')->where('team_id', $request->user()->current_team_id), 'accounts','category_id', '=', 'categories.id')
        ->get();

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

        return Jetstream::inertia()->render($request, config('journal.statements_inertia_path') . '/Index', [
            "categories" => $categoryAccounts,
            'categoryType' => $category
        ]);
    }
}
