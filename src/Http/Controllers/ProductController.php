<?php

namespace Insane\Journal\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Category;
use Insane\Journal\Models\Invoice\Invoice;
use Insane\Journal\Models\Product\Product;
use Laravel\Jetstream\Jetstream;


class ProductController
{

    public function __construct()
    {
        $this->model = new Account();
        $this->searchable = ['name'];
        $this->validationRules = [];
    }

    public function index(Request $request) {
        return Jetstream::inertia()->render($request, config('journal.products_inertia_path') . '/Index', [
            "products" => Product::with(['images', 'price', 'images'])->paginate(),
            "categories" => Category::where('depth', 1)->with(['accounts'])->get(),
        ]);

    }

     /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        return Jetstream::inertia()->render($request, config('journal.products_inertia_path') . '/Edit', [
            'product' => null
        ]);
    }


    /**
    * Show the form for editing a resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function show(Request $request, $id)
    {
        $product = Product::with(['images', 'price', 'images', 'priceList'])->find($id);
        $teamId = $request->user()->current_team_id;

        if ($product->team_id != $teamId) {
            Response::redirect('/products');
        }

        return Jetstream::inertia()->render($request, config('journal.products_inertia_path') . '/Show', [
            'product' => $product,
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

    public function store(Request $request, Response $response) {
        $postData = $request->post();
        $postData['user_id'] = $request->user()->id;
        $postData['team_id'] = $request->user()->current_team_id;
        $product = Product::addProduct($postData);

        if ($request->file('images')) {
            $images = $request->file('images');
            $folder = 'products/'. $postData['team_id'];
            $this->saveFiles($images, $folder, $product, $postData);
        }
        return Redirect('products/');
    }

    private function saveFiles($files, $folder,$resource, $formData) {
        foreach ($files as $item) {
            foreach ($item as $image) {
                $path = $image->store($folder, 'public');
                $resource->images()->create(array_merge($formData, [
                    'url' => $path,
                    'name' => $image->getClientOriginalName(),
                    "user_id" => $resource->user_id,
                    "team_id" => $resource->team_id,
                ]));
            }
        }
    }

    public function update(Request $request) {
        $postData = $request->post();
        $product = Product::updateProduct($postData);

        if ($request->file('images')) {
            $images = $request->file('images');
            $folder = 'products/'. $product->team_id;
            $this->saveFiles($images, $folder, $product, $postData);
        }

        return Redirect::back();
    }
}
