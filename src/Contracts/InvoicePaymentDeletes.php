<?php

namespace Insane\Journal\Contracts;

use Illuminate\Foundation\Auth\User;
use Insane\Journal\Models\Core\Payment;
use Insane\Journal\Models\Invoice\Invoice;

interface InvoicePaymentDeletes {
    public function validate(User $user, Invoice $invoice);
    public function delete(User $user, Invoice $invoice, Payment $payment);
}
