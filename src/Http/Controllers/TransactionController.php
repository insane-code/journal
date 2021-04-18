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
        return Jetstream::inertia()->render($request, config('journal.transactions_inertia_path') . '/Index', [
            "transactions" => Transaction::orderByDesc('date')->orderByDesc('number')->with(['mainLine', 'lines', 'mainLine.category', 'mainLine.account'])->paginate()->through(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'date' => $transaction->date,
                    'number' => $transaction->number,
                    'description' => $transaction->description,
                    'account' => $transaction->mainLine ? $transaction->mainLine->account: null,
                    'category' => $transaction->mainLine ? $transaction->mainLine->category : null,
                    'total' => $transaction->total,
                    'lines' => $transaction->lines,
                    'mainLine' => $transaction->mainLine,
                ];
            }),
            "categories" => Category::where('depth', 1)->with(['accounts'])->get(),
        ]);

    }

    public function store(Request $request, Response $response) {
        $postData = $request->post();
        $postData['user_id'] = $request->user()->id;
        $postData['team_id'] = $request->user()->current_team_id;
        $transaction = new Transaction();
        $transaction = $transaction::create($postData);
        $transaction->createLines($postData, $postData['items'] ?? []);
        return $response->sendContent($transaction);
    }
}
