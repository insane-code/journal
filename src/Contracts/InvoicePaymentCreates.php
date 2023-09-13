<?php

namespace Insane\Journal\Contracts;

use Illuminate\Foundation\Auth\User;
use Insane\Journal\Models\Invoice\Invoice;

interface InvoicePaymentCreates {
    public function validate(User $user, Invoice $invoice);
    public function create(User $user, Invoice $invoice, array $paymentData);
}
