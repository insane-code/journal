<?php

namespace Insane\Journal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Insane\Journal\Models\Core\Category;
use Insane\Journal\Models\Core\Tax;
use Insane\Journal\Models\Product\Product;

class ProductController
{
    public function index() {
        return inertia(config('journal.products_inertia_path') . '/Index', [
            "products" => Product::with(['images', 'price', 'images'])->paginate(),
            "categories" => Category::where('depth', 1)->with(['accounts'])->get(),
        ]);

    }

    public function create(Request $request)
    {
        $teamId = $request->user()->current_team_id;
        return inertia(config('journal.products_inertia_path') . '/Edit', [
            'product' => null,
            'availableTaxes' => Tax::where("team_id", $teamId)->get(),
        ]);
    }

    public function show(Request $request, $id)
    {
        $product = Product::with(['images', 'price', 'images', 'priceList', 'taxes'])->find($id);
        $teamId = $request->user()->current_team_id;

        if ($product->team_id != $teamId) {
            Response::redirect('/products');
        }

        return inertia(config('journal.products_inertia_path') . '/Show', [
            'product' => $product,
            'availableTaxes' => Tax::where("team_id", $teamId)->get(),
        ]);
    }

    public function store(Request $request) {
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
