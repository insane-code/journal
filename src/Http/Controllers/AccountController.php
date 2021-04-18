<?php

namespace Insane\Journal\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
            "categories" => Category::where('depth', 0)->with(['subCategories', 'subcategories.accounts', 'subcategories.accounts.lastTransactionDate'])->get(),
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
}
