<?php
namespace Insane\Journal\Events;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Insane\Journal\Models\Core\Transaction;

class TransactionCreated {
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The transaction instance.
     *
     * @var \Insane\Journal\Models\Core\Transaction 
     */
    public $transaction;
    /**
     * The transactionData array.
     *
     * @var array
     */
    public $transactionData;

    /**
     * Create a new event instance.
     *
     * @param  \Insane\Journal\Models\Core\Transaction  $transaction
     * @return void
     */
    public function __construct(Transaction $transaction, array $transactionData = [])
    {
        $this->transaction = $transaction;
        $this->transactionData = $transactionData;
    }
}
