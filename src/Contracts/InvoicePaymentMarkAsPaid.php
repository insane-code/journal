<?php

namespace Insane\Journal\Contracts;

use Illuminate\Foundation\Auth\User;
use Insane\Journal\Models\Invoice\Invoice;

interface InvoicePaymentMarkAsPaid {
    public function validate(User $user, Invoice $invoice);
    public function markAsPaid(User $user, Invoice $invoice);
}
