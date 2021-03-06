<?php

namespace Insane\Journal\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Category;
use Insane\Journal\Models\Core\Transaction;
use Laravel\Jetstream\Jetstream;


class TransactionController
{

    public function __construct()
    {
        $this->model = new Account();
        $this->searchable = ['name'];
        $this->validationRules = [];
    }

    public function index(Request $request, Response $response) {
        $transactions = Transaction::where([
            'team_id' => $request->user()->current_team_id
        ])
        ->orderBy('date', 'desc')
        ->orderBy('created_at', 'desc')
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
        $transaction = Transaction::createTransaction($postData);
        if ($request->query('json')) {
            return $response->sendContent($transaction);
        }
        return Redirect()->back();
    }

    public function update(Request $request, Response $response, $id) {
        $transaction = Transaction::find($id);
        $postData = $request->post();
        $transaction->updateTransaction($postData);
        if ($request->query('json')) {
            return $response->sendContent($transaction);
        }
        return Redirect()->back();
    }

    public function destroy(Request $request, Response $response, $id) {
        $postData = $request->post();
        $postData['user_id'] = $request->user()->id;
        $postData['team_id'] = $request->user()->current_team_id;
        $transaction = Transaction::where([
            'user_id'=> $request->user()->id,
            'id' => $id
        ])->get()->first();
        $transaction->remove();
        if ($request->query('json')) {
            return $response->sendContent($transaction);
        }
        return Redirect()->back();
    }

    public function approve(Request $request, Response $response, int $id) {
        $request->user()->id;
        $transaction = Transaction::find($id);
        $transaction->approve();
        if ($request->query('json')) {
            return $response->sendContent($transaction);
        }
        return Redirect()->back();
    }

    public function approveDrafts(Request $request, Response $response) {
        $request->user()->id;
        $transactions = Transaction::where([
            'user_id'=> $request->user()->id,
            'status' => 'draft'
        ])->get();
        foreach ($transactions as $transaction) {
            $transaction->approve();
        }
        if ($request->query('json')) {
            return $response->sendContent($transaction);
        }
        return Redirect()->back();
    }

    public function removeDrafts(Request $request, Response $response) {
        $request->user()->id;
        $transactions = Transaction::where([
            'user_id'=> $request->user()->id,
            'status' => 'draft'
        ])->get();
        foreach ($transactions as $transaction) {
            $transaction->remove();
        }
        if ($request->query('json')) {
            return $response->sendContent($transaction);
        }
        return Redirect()->back();
    }
}
