<?php

namespace Insane\Journal\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        $transaction =  Transaction::orderBy('date')->with(['mainLine', 'lines'])->paginate();
        return Jetstream::inertia()->render($request, config('journal.transactions_inertia_path') . '/Index', [
            "transactions" => $transaction->through(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'date' => $transaction->date,
                    'description' => $transaction->description,
                    'account' => $transaction->mainLine ? $transaction->mainLine : null,
                    'account' => $transaction->mainLine ? $transaction->mainLine : null,
                    'total' => $transaction->total,
                    'lines' => $transaction->lines,
                    'mainLine' => $transaction->mainLine,
                ];
            })
        ]);

    }

    public function store(Request $request, Response $response) {
        $postData = $request->post();
        $postData['user_id'] = $request->user()->id;
        $postData['team_id'] = $request->user()->current_team_id;
        $transaction = new Transaction();
        $transaction = $transaction::create($postData);
        return $response->sendContent($transaction);
    }
}
