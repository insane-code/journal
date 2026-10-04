<?php
namespace Insane\Journal\Events;
use Illuminate\Queue\SerializesModels;
use Insane\Journal\Models\Quote\Quote;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class QuoteCreated {
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Quote  $quote, public array $quoteData = []) {}
}
