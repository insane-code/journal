<?php

namespace Insane\Journal\Services;

use Exception;
use Insane\Journal\Models\Invoice\Invoice;

class InvoiceValidatorService
{  
    public function validateUpdate(Invoice $invoice, $postData) {
      if ($postData['total'] < $invoice->payments()->sum('amount')) {
        throw new Exception(__('The payment entered is more than the total amount due for this invoice. Please check and retry'));
      }
    }
}
