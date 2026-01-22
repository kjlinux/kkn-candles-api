<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(): JsonResponse
    {
        $cart = auth()->user()->getOrCreateCart();
        $cart->load('items.product');

        return response()->json([
            'success' => true,
            'data' => new CartResource($cart)
        ]);
    }

    public function addItem(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|uuid|exists:products,id',
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $product = Product::findOrFail($request->product_id);

        if (!$product->in_stock) {
            return response()->json([
                'success' => false,
                'message' => 'Ce produit n\'est plus en stock'
            ], 400);
        }

        $cart = auth()->user()->getOrCreateCart();

        $cartItem = $cart->items()->where('product_id', $product->id)->first();

        if ($cartItem) {
            $newQuantity = $cartItem->quantity + $request->quantity;

            if ($newQuantity > $product->stock_quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuffisant'
                ], 400);
            }

            $cartItem->update([
                'quantity' => $newQuantity,
            ]);
        } else {
            if ($request->quantity > $product->stock_quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuffisant'
                ], 400);
            }

            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'unit_price' => $product->price,
            ]);
        }

        $cart->load('items.product');

        return response()->json([
            'success' => true,
            'message' => 'Produit ajouté au panier',
            'data' => new CartResource($cart)
        ]);
    }

    public function updateItem(Request $request, CartItem $item): JsonResponse
    {
        if ($item->cart->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        if ($request->quantity > $item->product->stock_quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuffisant'
            ], 400);
        }

        $item->update([
            'quantity' => $request->quantity,
        ]);

        $cart = $item->cart->load('items.product');

        return response()->json([
            'success' => true,
            'message' => 'Quantité mise à jour',
            'data' => new CartResource($cart)
        ]);
    }

    public function removeItem(CartItem $item): JsonResponse
    {
        if ($item->cart->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        $cart = $item->cart;
        $item->delete();

        $cart->load('items.product');

        return response()->json([
            'success' => true,
            'message' => 'Produit retiré du panier',
            'data' => new CartResource($cart)
        ]);
    }

    public function clear(): JsonResponse
    {
        $cart = auth()->user()->cart;

        if ($cart) {
            $cart->items()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Panier vidé'
        ]);
    }
}
