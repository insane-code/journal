<?php
namespace Insane\Journal\Events;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class QuoteSaving {
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public array $quoteData = []) {}
}
