<?php

namespace Insane\Journal\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Insane\Journal\Contracts\TransactionApproves;
use Insane\Journal\Contracts\TransactionBulkDeletes;
use Insane\Journal\Contracts\TransactionCreates;
use Insane\Journal\Contracts\TransactionDeletes;
use Insane\Journal\Contracts\TransactionLists;
use Insane\Journal\Contracts\TransactionUpdates;
use Insane\Journal\Models\Core\Transaction;


final class TransactionController
{

    public function index() {
        $listTransaction = app(TransactionLists::class);   
        
        return inertia(config('journal.transactions_inertia_path') . '/Index', 
            $listTransaction->list(request()->user())
        );

    }

    public function show(Transaction $transaction) {
        $showTransaction = app(TransactionShows::class);   
        
        return inertia(config('journal.transactions_inertia_path') . '/Index', 
            $showTransaction->list(request()->user(), $transaction)
        );
    }

    public function store() {
        $createTransaction = app(TransactionCreates::class);   
        $transaction = $createTransaction->create(request()->user(), request()->post());
        if (request()->query('json')) {
            return response()->sendContent($transaction);
        }
        return Redirect()->back();
    }

    public function update(Transaction $transaction) {
        $updateTransaction = app(TransactionUpdates::class);   
        $updateTransaction->update(request()->user(), $transaction, request()->postData());
        if (request()->query('json')) {
            return response()->sendContent($transaction);
        }
        return Redirect()->back();
    }

    public function destroy(Transaction $transaction) {
        $deleteTransaction = app(TransactionDeletes::class);   
        $deleteTransaction->delete(request()->user(), $transaction);
        if (request()->query('json')) {
            return response()->sendContent($transaction);
        }
        return Redirect()->back();
    }

    public function approve(Request $request, Response $response, Transaction $transaction) {
        $approveTransaction = app(TransactionApproves::class);        
        $approveTransaction->approve(request()->user(), $transaction);

        if ($request->query('json')) {
            return $response->sendContent($transaction);
        }
        return Redirect()->back();
    }

    public function approveDrafts() {
        $approveTransaction = app(TransactionBulkApproves::class);   
        $approveTransaction->approveAllDrafts(request()->user());

        if (request()->query('json')) {
            return response()->sendContent(__("all  draft transactions approved"));
        }
        return Redirect()->back();
    }

    public function removeDrafts() {
        $deleteTransaction = app(TransactionBulkDeletes::class);   
        $deleteTransaction->deleteAllDrafts(request()->user());
        if (request()->query('json')) {
            return response()->sendContent("all draft transactions deleted");
        }
        return Redirect()->back();
    }
}
