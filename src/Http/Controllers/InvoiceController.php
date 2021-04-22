<?php

namespace Insane\Journal\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response as FacadesResponse;
use Insane\Journal\Category;
use Insane\Journal\Invoice;
use Insane\Journal\Product;
use Laravel\Jetstream\Jetstream;

class InvoiceController
{
    public function __construct()
    {
        $this->model = new Invoice();
        $this->searchable = ['name'];
        $this->validationRules = [];
    }

    public function index(Request $request)
    {
        return Jetstream::inertia()->render($request, config('journal.invoices_inertia_path') . '/Index', [
            "invoices" => Invoice::orderByDesc('date')->orderByDesc('number')->paginate(),
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
        return $response->sendContent($invoice);
    }

    /**
    * Show the form for editing a resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function edit(Request $request, $id)
    {
        $invoice = Invoice::find($id);

        if ($invoice->team_id != $request->user()->current_team_id) {
            Response::redirect('/invoices');
        }
        $invoiceData = $invoice->toArray();
        $invoiceData['client'] = $invoice->client;
        $invoiceData['lines'] = $invoice->lines->toArray();
        $invoiceData['payments'] = $invoice->payments->toArray();

        return Jetstream::inertia()->render($request, config('journal.invoices_inertia_path') . '/Edit', [
            'invoice' => $invoiceData,
            'products' => Product::where([
                'team_id' => $request->user()->current_team_id
            ])->with(['price'])->get(),
            "categories" => Category::where([
                'depth' => 1,
                'team_id' => $request->user()->current_team_id
            ])->with(['accounts'])->get(),
            // change this to be dinamyc
            'clients' => Client::all()
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
        Redirect("/invoices/$id/edit");
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
            $error = "This invoice is already paid";
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
    public function deletePayment(Response $response, $id, $paymentId)
    {
        $resource = Invoice::find($id);
        $resource->deletePayment($paymentId);
        $resource->save();
        return $response->send($resource);
    }
}
