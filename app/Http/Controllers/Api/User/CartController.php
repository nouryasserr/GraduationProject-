<?php

namespace App\Http\Controllers\Api\User;

use App\Models\Product;
use App\Models\Product_size;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\CartProductResource;
use Illuminate\Support\Facades\Auth;
use App\Models\Cart;

class CartController extends Controller
{
    // public function addToCart(Request $request)
    // {
    //     $user = $request->user();
    //     $productId = $request->input('product_id');

    //     $sizeId = $request->input('product_size_id');

    //     $product = Product::find($productId);
    //     if (!$product) {
    //         return response()->errors('Product not found');
    //     }

    //     $quantity = $request->input('quantity', 1);

    //     $user->cart()->syncWithoutDetaching([
    //         $productId => [
    //             'quantity' => $quantity,
    //             'product_size_id' => $sizeId,
    //         ]
    //     ]);

    //     return response()->success('Product added to cart');
    // }
    public function addToCart(Request $request)
    {
        $user = $request->user();
        $productId = $request->input('product_id');
        $quantity = (int) $request->input('quantity', 1);

        $product = Product::find($productId);
        if (!$product) {
            return response()->errors('Product not found');
        }

        // Check if product has variants
        $hasVariants = Product_size::where('product_id', $productId)->exists();

        if ($hasVariants) {
            $productSizeId = $request->input('product_size_id');

            // If product_size_id not sent, try resolving using size_id + color_id
            if (!$productSizeId) {
                $sizeId = $request->input('size_id');

                if (!$sizeId) {
                    return response()->errors('You must choose both size for this product');
                }

                $variant = Product_size::where('product_id', $productId)
                    ->where('size_id', $sizeId)
                    ->first();

                if (!$variant) {
                    return response()->errors('Invalid size combination');
                }

                $productSizeId = $variant->id;
            } else {
                $variant = Product_size::find($productSizeId);

                if (!$variant || $variant->product_id != $productId) {
                    return response()->errors('Invalid product variant');
                }
            }

            if ($variant->stock < $quantity) {
                return response()->errors('Not enough stock for this variant');
            }

            $existing = $user->cart()
                ->wherePivot('product_id', $productId)
                ->wherePivot('product_size_id', $productSizeId)
                ->first();

            if ($existing) {
                $newQty = $existing->pivot->quantity + $quantity;

                if ($newQty > $variant->stock) {
                    return response()->errors('Cannot exceed available stock');
                }

                $user->cart()->updateExistingPivot($productId, [
                    'quantity' => $newQty,
                    'product_size_id' => $productSizeId
                ]);
            } else {
                $user->cart()->attach($productId, [
                    'quantity' => $quantity,
                    'product_size_id' => $productSizeId
                ]);
            }
        } else {
            // Product has no variants
            if ($product->stock < $quantity) {
                return response()->errors('Not enough stock');
            }

            $existing = $user->cart()
                ->wherePivot('product_id', $productId)
                ->wherePivotNull('product_size_id')
                ->first();

            if ($existing) {
                $newQty = $existing->pivot->quantity + $quantity;

                if ($newQty > $product->stock) {
                    return response()->errors('Cannot exceed available stock');
                }

                $user->cart()->updateExistingPivot($productId, [
                    'quantity' => $newQty,
                    'product_size_id' => null
                ]);
            } else {
                $user->cart()->attach($productId, [
                    'quantity' => $quantity,
                    'product_size_id' => null
                ]);
            }
        }

        return response()->success('Product added to cart');
    }


    public function index(Request $request)
    {
        $user = $request->user();


        $cart = $user->cart()
            ->with(['subCategory', 'startup', 'mainImage'])
            ->get();

        // Don't forget to eager load productSize relations if used:
        $cart->each(function ($product) {
            if ($product->pivot->product_size_id) {
                $product->pivot->productSize = \App\Models\Product_size::with(['size'])->find($product->pivot->product_size_id);
            }
        });
        $data = CartProductResource::collection($cart);

        $totalPrice = $cart->sum(function ($product) {
            $price = $product->pivot->product_size_id
            ? $product->pivot->productSize->price
            : $product->price;

            $discountPercentage = $product->pivot->product_size_id
            ? $product->pivot->productSize->discount_percentage
            : $product->discount_percentage;

            $discountedPrice = $discountPercentage 
            ? $price * (1 - $discountPercentage / 100) 
            : $price;

            return $discountedPrice * $product->pivot->quantity;
        });
        

        $totalItems = $cart->sum(fn($product) => $product->pivot->quantity);

        return response()->success([
            'data' => $data,
            'totalPrice' => $totalPrice,
            'totalItems' => $totalItems,
        ]);
    }



    public function removeFromCart(Request $request)
    {
        $user = $request->user();
        $productId = $request->input('product_id');
        // $productSizeId = $request->input('product_size_id');

        $product = $user->cart()
            ->wherePivot('product_id', $productId)
            // ->wherePivot('product_size_id', $productSizeId)
            ->first();


        if (!$product) {
            return response()->errors('Product not found in cart', 404);
        }

        $currentQty = $product->pivot->quantity;

        if ($currentQty > 1) {
            $user->cart()->updateExistingPivot($productId, [
                'quantity' => $currentQty - 1
            ]);
        } else {
            $user->cart()->detach($productId);
        }

        return response()->success('Product quantity updated in cart');
    }

    public function addToCartQuantity(Request $request)
    {
        $user = $request->user();
        $productId = $request->input('product_id');
        // $productSizeId = $request->input('product_size_id');

        $product = $user->cart()
            ->wherePivot('product_id', $productId)
            ->first();

        if (!$product) {
            return response()->errors('Product not found in cart', 404);
        }

        $currentQty = $product->pivot->quantity;

        $user->cart()->updateExistingPivot($productId, [
            'quantity' => $currentQty + 1,
            // 'product_size_id' => $productSizeId 
        ]);

        // Fetch the updated product with the newest quantity
        $updatedProduct = $user->cart()
            ->wherePivot('product_id', $productId)
            // ->wherePivot('product_size_id', $productSizeId)
            ->first();

        return response()->success([
            'message' => 'Product quantity increased in cart',
            'product' => $updatedProduct
        ]);
    }

    public function clearCart(Request $request)
    {
        $user = $request->user();
        $user->cart()->detach();

        return response()->success('Cart cleared');
    }
    // i wantt function to delete one product from the cart

    public function removeProductFromCart(Request $request , $id)
    {
        $user = $request->user();
        $productId = $id;
        $productSizeId = $request->input('product_size_id'); // nullable
    
        // Check if the product exists in the cart
        $productInCart = \DB::table('cart_product')
            ->where('user_id', $user->id)
            ->where('product_id', $productId)
            ->when($productSizeId !== null, function ($query) use ($productSizeId) {
                return $query->where('product_size_id', $productSizeId);
            }, function ($query) {
                return $query->whereNull('product_size_id');
            })
            ->first();
    
        if (!$productInCart) {
            return response()->errors('Product not found in cart', 404);
        }
    
        // Delete the entry from cart_products
        $deleted = \DB::table('cart_product')
            ->where('user_id', $user->id)
            ->where('product_id', $productId)
            ->when($productSizeId !== null, function ($query) use ($productSizeId) {
                return $query->where('product_size_id', $productSizeId);
            }, function ($query) {
                return $query->whereNull('product_size_id');
            })
            ->delete();
    
        if ($deleted) {
            return response()->success('Product deleted from the cart successfully');
        } else {
            return response()->errors('Failed to delete product from cart', 500);
        }
    }
    
    


    //msh mehtagh ahalian 3shan el remove by3ml minus one lel product
    // public function updateQuantity(Request $request)
    // {
    //     $user = $request->user();
    //     $productId = $request->input('product_id');
    //     $quantity = $request->input('quantity');

    //     if ($user->cart()->where('product_id', $productId)->exists()) {
    //         $user->cart()->updateExistingPivot($productId, ['quantity' => $quantity]);
    //         return response()->json(['message' => 'Quantity updated']);
    //     }

    //     return response()->json(['message' => 'Product not found in cart'], 404);
    // }

}
