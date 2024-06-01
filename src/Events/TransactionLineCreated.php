<?php
namespace Insane\Journal\Events;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class TransactionLineCreated {
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public mixed $transactionLine, public array $transactionData = []) {}
}
