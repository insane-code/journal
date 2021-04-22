<?php

namespace Insane\Journal\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Insane\Journal\Account;
use Insane\Journal\Category;
use Insane\Journal\Product;
use Insane\Journal\Transaction;
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

    public function store(Request $request, Response $response) {
        $postData = $request->post();
        $postData['user_id'] = $request->user()->id;
        $postData['team_id'] = $request->user()->current_team_id;
        $product = Product::addProduct($postData);

        if ($request->file('images')) {
            $images = $request->file('images');
            foreach ($images as $item) {
                foreach ($item as $image) {
                    $path = $image->store('products/'. $postData['team_id']);
                    $product->images()->create(array_merge($postData, [
                        'url' => $path,
                        'name' => $image->getClientOriginalName()
                    ]));
                }
            }
        }
        return Redirect('products/');
    }
}
