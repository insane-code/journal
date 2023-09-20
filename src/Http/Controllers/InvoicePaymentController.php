<?php

namespace Insane\Journal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Insane\Journal\Models\Invoice\Invoice;
use Exception;
use Insane\Journal\Contracts\InvoicePaymentCreates;
use Insane\Journal\Contracts\InvoicePaymentMarkAsPaid;
use Insane\Journal\Models\Core\Payment;

class InvoicePaymentController
{

    public function addPayment(Invoice $invoice)
    {
        $createPayment = app(InvoicePaymentCreates::class);        
        try {
          return response()->send($createPayment->create(
            request()->user(), 
            $invoice, 
            request()->post()));
        } catch (Exception $e) {
            return response([
              'status' => [
                  'message' => $e->getMessage()
              ]
            ], 400);
        }
    }

    public function markAsPaid(Invoice $invoice)
    {
        $payInvoice = app(InvoicePaymentMarkAsPaid::class);  
        $payInvoice->markAsPaid(request()->user(), $invoice);
        return redirect()->back();
    }

    /**
     * delete payment from invoice.
     * POST invoices/:id/add-payment
     *
     * @param {object} ctx
     * @param {Request} ctx.request
     * @param {Response} ctx.response
     */
    public function deletePayment(Invoice $invoice, Payment $payment)
    {
        $deleteInvoicePayment = app(InvoicePaymentDeletes::class);  
        $deleteInvoicePayment->delete(request()->user(), $invoice, $payment);

        if (request()->query('json')) {
          return response()->send($payment);
        } 
        return redirect()->back();
    }
}
