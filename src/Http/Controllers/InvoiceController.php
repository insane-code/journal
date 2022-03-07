<?php

namespace Insane\Journal\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Insane\Journal\Models\Core\Category;
use Insane\Journal\Models\Core\Tax;
use Insane\Journal\Models\Invoice\Invoice;
use Insane\Journal\Models\Product\Product;
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
            "invoices" => Invoice::orderByDesc('date')->orderByDesc('number')->paginate()->through(function ($invoice) {
                return [
                    "id" => $invoice->id,
                    "concept" => $invoice->concept,
                    "date" => $invoice->date,
                    "client_name" => $invoice->client->names,
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
        $teamId = $request->user()->current_team_id;
        return Jetstream::inertia()->render($request, config('journal.invoices_inertia_path') . '/Edit', [
            'invoice' => null,
            'products' => Product::where([
                'team_id' => $teamId
            ])->with(['price'])->get(),
            // change this to be dinamyc
            'clients' => Client::where('team_id', $teamId)->get(),
            "categories" => Category::where([
                'depth' => 0
            ])->with([
                'subCategories',
                'subcategories.accounts' => function ($query) use ($teamId) {
                    $query->where('team_id', '=', $teamId);
                },
                'subcategories.accounts.lastTransactionDate'
            ])->get(),
            'availableTaxes' => Tax::where("team_id", $teamId)->get(),
        ]);
    }


    public function store(Request $request, Response $response)
    {
        $postData = $request->post();
        $postData['user_id'] = $request->user()->id;
        $postData['team_id'] = $request->user()->current_team_id;
        $invoice = Invoice::createDocument($postData);
        return redirect("/invoices/$invoice->id/edit");
    }

    /**
    * Show the form for editing a resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function show(Request $request, $id)
    {
        $invoice = Invoice::find($id);
        $teamId = $request->user()->current_team_id;

        if ($invoice->team_id != $teamId) {
            Response::redirect('/invoices');
        }
        $invoiceData = $invoice->toArray();
        $invoiceData['client'] = $invoice->client;
        $invoiceData['lines'] = $invoice->lines->toArray();
        $invoiceData['payments'] = $invoice->payments()->with(['transaction'])->get()->toArray();

        return Jetstream::inertia()->render($request, config('journal.invoices_inertia_path') . '/Show', [
            'invoice' => $invoiceData,
            'products' => Product::where([
                'team_id' => $teamId
            ])->with(['price'])->get(),
            "categories" => Category::where([
                'depth' => 1,
                'team_id' => $teamId
            ])->with(['accounts'])->get(),
            // change this to be dinamyc
            'clients' => Client::where('team_id', $teamId)->get()
        ]);
    }
    /**
    * Show the form for editing a resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function edit(Request $request, $id)
    {
        $invoice = Invoice::find($id);
        $teamId = $request->user()->current_team_id;

        if ($invoice->team_id != $teamId) {
            Response::redirect('/invoices');
        }
        $invoiceData = $invoice->toArray();
        $invoiceData['client'] = $invoice->client;
        $invoiceData['lines'] = $invoice->lines->toArray();
        $invoiceData['payments'] = $invoice->payments()->with(['transaction'])->get()->toArray();

        return Jetstream::inertia()->render($request, config('journal.invoices_inertia_path') . '/Edit', [
            'invoice' => $invoiceData,
            'products' => Product::where([
                'team_id' => $teamId
            ])->with(['price'])->get(),
            "categories" => Category::where([
                'depth' => 1,
                'team_id' => $teamId
            ])->with(['accounts'])->get(),
            // change this to be dinamyc
            'clients' => Client::where('team_id', $teamId)->get()
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
