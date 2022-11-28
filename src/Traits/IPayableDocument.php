<?php

namespace Insane\Journal\Traits;

interface IPayableDocument
{
    public static function calculateTotal($invoice);

    public static function checkStatus($payable);

    public function getConceptLine(): string;
}
