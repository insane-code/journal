<?php
namespace Insane\Journal\Events;
use Illuminate\Queue\SerializesModels;
use Insane\Journal\Models\Core\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class TransactionCreated implements TransactionEvent {
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public mixed $transaction, public array $transactionData = []) {}
}
