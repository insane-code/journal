<?php

namespace Insane\Journal\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Jetstream\Jetstream;
use Insane\Journal\Models\Core\Payment;
use Insane\Journal\Models\Core\Category;
use Insane\Journal\Models\Product\Product;

class PaymentsController
{

    public function index(Request $request)
    {
        return Jetstream::inertia()->render($request, config('journal.payments_inertia_path') . '/Index', [
            "invoices" => Payment::orderByDesc('payment_date')->paginate()->through(function ($invoice) {
                return [
                    "id" => $invoice->id,
                    "concept" => $invoice->concept,
                    "date" => $invoice->payment_date,
                    "number" => $invoice->number,
                    "series" => $invoice->series,
                    "status" => $invoice->status,
                    "total" => $invoice->total,
                    "debt" => $invoice->debt
                ];
            }),
        ]);
    }

    /**
    * Show the form for creating a new resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function create(Request $request)
    {
        return Jetstream::inertia()->render($request, config('journal.invoices_inertia_path') . '/Edit', [
            'invoice' => null,
            'products' => Product::where([
                'team_id' => $request->user()->current_team_id
            ])->with(['price'])->get(),
            // change this to be dinamyc
            'clients' => Client::all(),
            "categories" => Category::where([
                'depth' => 0,
                'team_id' => $request->user()->current_team_id
            ])->with(['subCategories', 'subcategories.accounts', 'subcategories.accounts.lastTransactionDate'])->get(),
        ]);
    }


    public function store(Request $request, Response $response)
    {
        $postData = $request->post();
        $postData['user_id'] = $request->user()->id;
        $postData['team_id'] = $request->user()->current_team_id;
        $invoice = Invoice::create($postData);
        $invoice->createLines($postData['items'] ?? []);
        return Redirect("/invoices/$invoice->id/edit");
    }

    /**
    * Show the form for editing a resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function edit(Request $request, $id)
    {
        $payment = Payment::find($id);

        if ($payment->team_id != $request->user()->current_team_id) {
            Response::redirect('/payments');
        }
        $paymentData = $payment->toArray();

        return Jetstream::inertia()->render($request, config('journal.payments_inertia_path') . '/Edit', [
            'payment' => $paymentData
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $invoice = Invoice::find($id);
        $postData = $request->post();
        $invoice->update($postData);
        $invoice->createLines($postData['items']);
        return Redirect("/invoices/$id/edit");
    }

    /**
   * add payment to invoice.
   * POST invoices/:id/add-payment
   *
   * @param {object} ctx
   * @param {Request} ctx.request
   * @param {Response} ctx.response
   */
    public function addPayment(Request $request, Response $response, $id)
    {
        $invoice = Invoice::find($id);
        $postData = $request->post();
        $error = "";

        if (!$invoice) {
            $error = "resource not found";
        }

        if ($invoice && $invoice->debt <= 0) {
            $error = __("This invoice is already paid");
        }

        if ($error) {
            return Response::setStatus(400)->setContent([
                'status' => [
                    'message' => $error
                ]
            ]);
        }


        $payment = $invoice->createPayment($postData);
        $invoice->save();
        if ($invoice->invoiceable_type == 'CONTRACT') {
            // const contract = await Contract.find(resource.resource_id);
            // contractActions.checkInvoicesStatus(contract)
        }

        return $response->send($payment);
    }


    /**
     * delete payment from invoice.
     * POST invoices/:id/add-payment
     *
     * @param {object} ctx
     * @param {Request} ctx.request
     * @param {Response} ctx.response
     */
    public function destroy(Response $response, Payment $payment)
    {
        $payment->payable->deletePayment($payment->id);
        return $response->send($payment);
    }
}
