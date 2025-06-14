<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['startup', 'subCategory.category', 'images', 'reviews'])
            ->get()
            ->sortByDesc(function ($product) {
                return optional($product->startup)->package_id ?? PHP_INT_MIN;
            });

        return response()->paginate_resource(ProductResource::collection($products));
    }

    public function show($id)
    {
        $product = Product::with(['startup', 'subCategory.category', 'images' , 'reviews'])->find($id);

        if (!$product) {
            return response()->errors('Product not found');
        }
        $product = new ProductResource($product);

        return response()->success($product);
    }

    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->errors('Product not found');
        }

        $product->delete();

        return response()->success('Product deleted successfully');
    }
    // store 
    // public function store(Request $request)
    // {
    //     // Validate the request data
    //     $validatedData = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'description' => 'required|string',
    //         'price' => 'required|numeric',
    //         'startup_id' => 'required|exists:startups,id',
    //         'sub_category_id' => 'required|exists:sub_categories,id',
    //         'images' => 'required|array',
    //         'images.*.file' => 'required|file|mimes:jpeg,png,jpg,gif|max:2048',
    //         'images.*.is_main' => 'required|boolean',
    //         'stock' => 'required|integer|min:0',
    //     ]);

    //     // Create a new product
    //     $product = Product::create($validatedData);

    //     // Store images in the images table
    //     foreach ($validatedData['images'] as $imageData) {
    //         $file = $imageData['file'];
    //         $path = 'storage/' . $file->store('images', 'public');

    //         // $path = $file->store('images', 'public'); // Store the file in the 'public/images' directory

    //         $product->images()->create([
    //             'url' => $path,
    //             'is_main' => $imageData['is_main'],
    //         ]);
    //     }

    //     return response()->success(new ProductResource($product), 'Product created successfully');
    // }
    // function to delete reviews
    public function deleteReview($productId, $reviewId)
    {
        $product = Product::find($productId);

        if (!$product) {
            return response()->errors('Product not found');
        }

        $review = $product->reviews()->find($reviewId);

        if (!$review) {
            return response()->errors('Review not found');
        }

        $review->delete();

        return response()->success('Review deleted successfully');
    }
}
