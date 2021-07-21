<?php

namespace Insane\Journal\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Insane\Journal\Account;
use Insane\Journal\Category;
use Insane\Journal\Transaction;
use Laravel\Jetstream\Jetstream;


class TransactionController
{

    public function __construct()
    {
        $this->model = new Account();
        $this->searchable = ['name'];
        $this->validationRules = [];
    }

    public function index(Request $request) {
        $transactions = Transaction::where([
            'team_id' => $request->user()->current_team_id
        ])->getByDate()
        ->paginate()
        ->through(function ($transaction) {
            return Transaction::parser($transaction);
        });

        if ($request->query('json')) {
            return $response->sendContent($transaction);
        }

        $categories = Category::where('depth', 1)
        ->with('accounts', function ($query) use ($request) {
            $query->where('team_id', '=', $request->user()->current_team_id);
        })->get();

        return Jetstream::inertia()->render($request, config('journal.transactions_inertia_path') . '/Index', [
            "transactions" => $transactions,
            "categories" => $categories,
        ]);

    }

    public function store(Request $request, Response $response) {
        $postData = $request->post();
        $postData['user_id'] = $request->user()->id;
        $postData['team_id'] = $request->user()->current_team_id;
        $transaction = Transaction::create($postData);
        $transaction->createLines($postData, $postData['items'] ?? []);
        if ($request->query('json')) {
            return $response->sendContent($transaction);
        }
        return Redirect()->back();
    }
}