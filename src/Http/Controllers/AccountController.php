<?php

namespace Insane\Journal\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Category;
use Insane\Journal\Contracts\AccountCreates;
use Insane\Journal\Contracts\AccountDeletes;
use Insane\Journal\Contracts\AccountUpdates;
use Insane\Journal\Models\Core\AccountDetailType;

final class AccountController
{
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
        return inertia(config('journal.accounts_inertia_path') . '/Index', [
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

    public function show(Request $request, $id) {
        $teamId = $request->user()->current_team_id;
        $account = Account::where('id', $id)->with(['transactions'])->get()->first();
        if ($account->team_id != $teamId) {
            Response::redirect('/accounts');
        }
        return inertia('Journal/Accounts/Show', [
            "account" => $account,
            "transactions" => $account->transactions,
        ]);
    }

    public function store(Request $request, Response $response) {
        $accountCreate = app(AccountCreates::class);
        $account = $accountCreate->create(request()->user(), request()->post());
        if ($request->query('json')) {
            return $response->sendContent($account);
        }
        return Redirect()->back();
    }

    public function update(Account $account) {
        $accountUpdate = app(AccountUpdates::class);
        $updatedAccount = $accountUpdate->update(request()->user(), $account, request()->post());
        if (request()->query('json')) {
            return response()->sendContent($updatedAccount);
        }
        return redirect()->back();
    }

    public function destroy(Request $request, Account $account) {
        $accountDelete = app(AccountDeletes::class);
        $accountDelete->delete(request()->user(), $account);
        return Redirect()->back();
    }
}
