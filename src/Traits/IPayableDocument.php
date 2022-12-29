<?php

namespace Insane\Journal\Traits;

interface IPayableDocument
{

    public function getStatusField(): string;

    public static function calculateTotal($invoice);

    public static function checkStatus($payable);

    public function getConceptLine(): string;

    public function getTransactionDirection(): string;
    
    public function getCounterAccountId(): int;
}
