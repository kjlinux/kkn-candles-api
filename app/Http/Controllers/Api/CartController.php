<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CartController extends Controller
{
    #[OA\Get(
        path: '/cart',
        summary: 'Afficher le panier',
        description: 'Récupérer le contenu du panier de l\'utilisateur connecté',
        tags: ['Cart'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Contenu du panier',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Cart'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    public function index(): JsonResponse
    {
        $cart = auth()->user()->getOrCreateCart();
        $cart->load('items.product');

        return response()->json([
            'success' => true,
            'data' => new CartResource($cart)
        ]);
    }

    #[OA\Post(
        path: '/cart/items',
        summary: 'Ajouter au panier',
        description: 'Ajouter un produit au panier',
        tags: ['Cart'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['product_id', 'quantity'],
                properties: [
                    new OA\Property(property: 'product_id', type: 'string', format: 'uuid', description: 'ID du produit'),
                    new OA\Property(property: 'quantity', type: 'integer', minimum: 1, maximum: 100, example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Produit ajouté au panier',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Produit ajouté au panier'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Cart'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Stock insuffisant ou produit non disponible'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 422, description: 'Erreur de validation'),
        ]
    )]
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

    #[OA\Put(
        path: '/cart/items/{item}',
        summary: 'Modifier la quantité',
        description: 'Modifier la quantité d\'un article dans le panier',
        tags: ['Cart'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'item', in: 'path', required: true, description: 'ID de l\'article du panier', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['quantity'],
                properties: [
                    new OA\Property(property: 'quantity', type: 'integer', minimum: 1, maximum: 100, example: 2),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Quantité mise à jour',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Quantité mise à jour'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Cart'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Stock insuffisant'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Non autorisé'),
            new OA\Response(response: 422, description: 'Erreur de validation'),
        ]
    )]
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

    #[OA\Delete(
        path: '/cart/items/{item}',
        summary: 'Retirer du panier',
        description: 'Retirer un article du panier',
        tags: ['Cart'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'item', in: 'path', required: true, description: 'ID de l\'article du panier', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Article retiré du panier',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Produit retiré du panier'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Cart'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Non autorisé'),
        ]
    )]
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

    #[OA\Delete(
        path: '/cart',
        summary: 'Vider le panier',
        description: 'Supprimer tous les articles du panier',
        tags: ['Cart'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Panier vidé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Panier vidé'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
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
