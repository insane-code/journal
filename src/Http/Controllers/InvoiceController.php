<?php

namespace Insane\Journal\Http\Controllers;

use App\Models\Client;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Insane\Journal\Contracts\PdfExporter;
use Insane\Journal\Helpers\CategoryHelper;
use Insane\Journal\Journal;
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

    public function isBill(Request $request) {
        return  $isBill = str_contains($request->url(), 'bills');
    }

    public function index(Request $request)
    {
        $type = $this->isBill($request) ? 'BILL' : 'INVOICE';
        return Jetstream::inertia()->render($request, config('journal.invoices_inertia_path') . '/Index', [
            "invoices" => Invoice::where([
                'type'=> $type,
                'team_id' => $request->user()->currentTeam->id
            ])->orderByDesc('date')->orderByDesc('number')->paginate()->through(function ($invoice) {
                return [
                    "id" => $invoice->id,
                    "concept" => $invoice->concept,
                    "date" => $invoice->date,
                    "client_name" => $invoice->client?->names,
                    "number" => $invoice->number,
                    "series" => $invoice->series,
                    "status" => $invoice->status,
                    "total" => $invoice->total,
                    "debt" => $invoice->debt
                ];
            }),
            "type" => $type
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
        $isBill = $this->isBill($request);
        $type = $isBill ? 'BILL' : 'INVOICE';
        $accountCategories =  $isBill ? ['expected_payments_vendors', 'credit_card'] : ['cash_and_bank', 'expected_payments_customers'];
        return Jetstream::inertia()->render($request, config('journal.invoices_inertia_path') . '/Edit', [
            'invoice' => null,
            'type' => $type,
            'products' => Product::where([
                'team_id' => $teamId
            ])->with(['price', 'taxes'])->get(),
            'clients' => Client::where('team_id', $teamId)->get(),
            "categories" => Category::where([
                'depth' => 0
            ])->with([
                'subCategories',
                'subcategories.accounts' => function ($query) use ($teamId) {
                    $query->where('team_id', '=', $teamId);
                },
            ])->get(),
            "accounts" => $teamId ? CategoryHelper::getAccounts($teamId, $accountCategories) : null,
            'availableTaxes' => Tax::where("team_id", $teamId)->get(),
        ]);
    }


    public function store(Request $request, Response $response)
    {
        $postData = $request->post();
        $postData['user_id'] = $request->user()->id;
        $postData['team_id'] = $request->user()->current_team_id;
        Invoice::createDocument($postData);
        return redirect("/invoices/");
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
        $isBill = $this->isBill($request);
        $type = $isBill ? 'BILL' : 'INVOICE';

        return Jetstream::inertia()->render($request, config('journal.invoices_inertia_path') . '/Show', [
            'invoice' => $invoiceData,
            'businessData' => Setting::getByTeam($teamId),
            'type' => $type,
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
        $isBill = $this->isBill($request);
        $type = $isBill ? 'BILL' : 'INVOICE';

        return Jetstream::inertia()->render($request, config('journal.invoices_inertia_path') . '/Edit', [
            'invoice' => $invoiceData,
            'products' => Product::where([
                'team_id' => $teamId
            ])->with(['price'])->get(),
            "categories" => Category::where([
                'depth' => 1
            ])->with([
                'subCategories',
                'accounts' => function ($query) use ($teamId) {
                    $query->where('team_id', '=', $teamId);
                },
                'accounts.lastTransactionDate'
            ])->get(),
            'type' => $type,
            // change this to be dinamyc
            'clients' => Journal::listClientsOf($teamId)
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
        $invoice->updateDocument($postData);
        return Redirect("/invoices/$id/edit");
    }


    public function print(Invoice $invoice) {
      $exporter = app(PdfExporter::class);
      $exporter->process($invoice);
      return $exporter->previewAs($invoice->concept);
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

        return $response->send($payment);
    }

    public function markAsPaid(Request $request, $id)
    {
        $invoice = Invoice::find($id);
        $invoice->markAsPaid();
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
    public function deletePayment(Response $response, $id, $paymentId)
    {
        $resource = Invoice::find($id);
        $resource->deletePayment($paymentId);
        $resource->save();
        return $response->send($resource);
    }
}
