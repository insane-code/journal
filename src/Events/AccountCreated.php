<?php

namespace Insane\Journal\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Insane\Journal\Models\Core\Account;

class AccountCreated
{
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
     * @return void
     */
    public function __construct(Account $account, $formData = [])
    {
        $this->account = $account;
        $this->formData = $formData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
