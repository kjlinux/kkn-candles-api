<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    title: 'User',
    description: 'Modèle utilisateur',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '9e5f1c2a-3b4d-5e6f-7g8h-9i0j1k2l3m4n'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
        new OA\Property(property: 'first_name', type: 'string', example: 'Jean'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Dupont'),
        new OA\Property(property: 'full_name', type: 'string', example: 'Jean Dupont'),
        new OA\Property(property: 'phone', type: 'string', example: '+225 07 00 00 00 00'),
        new OA\Property(property: 'address', type: 'string', nullable: true, example: 'Cocody, Rue des Jardins'),
        new OA\Property(property: 'city', type: 'string', nullable: true, example: 'Abidjan'),
        new OA\Property(property: 'role', type: 'string', enum: ['customer', 'admin'], example: 'customer'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Product',
    title: 'Product',
    description: 'Modèle produit',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'category_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'category', ref: '#/components/schemas/Category'),
        new OA\Property(property: 'name', type: 'string', example: 'Bougie Parfumée Vanille'),
        new OA\Property(property: 'slug', type: 'string', example: 'bougie-parfumee-vanille'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'long_description', type: 'string', nullable: true),
        new OA\Property(property: 'price', type: 'integer', example: 5000),
        new OA\Property(property: 'formatted_price', type: 'string', example: '5 000 FCFA'),
        new OA\Property(property: 'stock_quantity', type: 'integer', example: 100),
        new OA\Property(property: 'in_stock', type: 'boolean', example: true),
        new OA\Property(property: 'is_featured', type: 'boolean', example: false),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'images', type: 'array', items: new OA\Items(type: 'string', format: 'url')),
        new OA\Property(property: 'main_image', type: 'string', format: 'url', nullable: true),
        new OA\Property(property: 'specifications', type: 'object', nullable: true),
        new OA\Property(property: 'sort_order', type: 'integer', example: 0),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Category',
    title: 'Category',
    description: 'Modèle catégorie',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Bougies Parfumées'),
        new OA\Property(property: 'slug', type: 'string', example: 'bougies-parfumees'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'image_url', type: 'string', format: 'url', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'sort_order', type: 'integer', example: 0),
        new OA\Property(property: 'products_count', type: 'integer', example: 15),
    ]
)]
#[OA\Schema(
    schema: 'Cart',
    title: 'Cart',
    description: 'Modèle panier',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/CartItem')),
        new OA\Property(property: 'items_count', type: 'integer', example: 3),
        new OA\Property(property: 'total', type: 'integer', example: 15000),
        new OA\Property(property: 'formatted_total', type: 'string', example: '15 000 FCFA'),
    ]
)]
#[OA\Schema(
    schema: 'CartItem',
    title: 'CartItem',
    description: 'Article du panier',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'product', ref: '#/components/schemas/Product'),
        new OA\Property(property: 'quantity', type: 'integer', example: 2),
        new OA\Property(property: 'unit_price', type: 'integer', example: 5000),
        new OA\Property(property: 'subtotal', type: 'integer', example: 10000),
    ]
)]
#[OA\Schema(
    schema: 'Order',
    title: 'Order',
    description: 'Modèle commande',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'order_number', type: 'string', example: 'KKN-20240101-0001'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled']),
        new OA\Property(property: 'status_label', type: 'string', example: 'En attente'),
        new OA\Property(
            property: 'customer',
            type: 'object',
            properties: [
                new OA\Property(property: 'first_name', type: 'string'),
                new OA\Property(property: 'last_name', type: 'string'),
                new OA\Property(property: 'full_name', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'phone', type: 'string'),
            ]
        ),
        new OA\Property(
            property: 'shipping',
            type: 'object',
            properties: [
                new OA\Property(property: 'address', type: 'string'),
                new OA\Property(property: 'city', type: 'string'),
            ]
        ),
        new OA\Property(property: 'subtotal', type: 'integer', example: 15000),
        new OA\Property(property: 'shipping_cost', type: 'integer', example: 2000),
        new OA\Property(property: 'total', type: 'integer', example: 17000),
        new OA\Property(property: 'formatted_total', type: 'string', example: '17 000 FCFA'),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/OrderItem')),
        new OA\Property(property: 'payment', ref: '#/components/schemas/Payment'),
        new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'shipped_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'delivered_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'OrderItem',
    title: 'OrderItem',
    description: 'Article de commande',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'product_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'product_name', type: 'string'),
        new OA\Property(property: 'quantity', type: 'integer', example: 2),
        new OA\Property(property: 'unit_price', type: 'integer', example: 5000),
        new OA\Property(property: 'subtotal', type: 'integer', example: 10000),
    ]
)]
#[OA\Schema(
    schema: 'Payment',
    title: 'Payment',
    description: 'Modèle paiement',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'transaction_id', type: 'string'),
        new OA\Property(property: 'amount', type: 'integer', example: 17000),
        new OA\Property(property: 'currency', type: 'string', example: 'XOF'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'completed', 'failed', 'cancelled']),
        new OA\Property(property: 'payment_method', type: 'string', example: 'cinetpay'),
        new OA\Property(property: 'operator', type: 'string', nullable: true, example: 'ORANGE'),
        new OA\Property(property: 'completed_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Media',
    title: 'Media',
    description: 'Modèle média',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'url', type: 'string', format: 'url'),
        new OA\Property(property: 'type', type: 'string', enum: ['image', 'video']),
        new OA\Property(property: 'filename', type: 'string'),
        new OA\Property(property: 'original_filename', type: 'string'),
        new OA\Property(property: 'size', type: 'integer'),
        new OA\Property(property: 'formatted_size', type: 'string', example: '1.5 MB'),
        new OA\Property(property: 'width', type: 'integer', nullable: true),
        new OA\Property(property: 'height', type: 'integer', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    title: 'PaginationMeta',
    description: 'Métadonnées de pagination',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 10),
        new OA\Property(property: 'per_page', type: 'integer', example: 12),
        new OA\Property(property: 'total', type: 'integer', example: 120),
    ]
)]
#[OA\Schema(
    schema: 'SuccessResponse',
    title: 'SuccessResponse',
    description: 'Réponse de succès générique',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string'),
    ]
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    title: 'ErrorResponse',
    description: 'Réponse d\'erreur générique',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string'),
    ]
)]
class Schemas
{
    // Cette classe sert uniquement à contenir les schémas OpenAPI
}
