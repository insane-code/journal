<?php

namespace Insane\Journal\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Insane\Journal\Models\Core\Account;

class AccountUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Account $account, public $formData = []) {}
}
