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
     * The transaction instance.
     *
     * @var array
     */
    public $transactionLines;

    /**
     * Create a new event instance.
     *
     * @param  \Insane\Journal\Models\Core\Transaction  $transaction
     * @return void
     */
    public function __construct(Transaction $transaction, array $transactionData, array $lines = [])
    {
        $this->transaction = $transaction;
        $this->transactionData = $transactionData;
        $this->lines = $lines;
    }
}
