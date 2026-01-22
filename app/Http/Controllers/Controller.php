<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'KKN Candles API',
    description: 'API pour la boutique de bougies KKN Candles',
    contact: new OA\Contact(
        name: 'Support KKN Candles',
        email: 'support@kkncandles.com'
    )
)]
#[OA\Server(
    url: '/api',
    description: 'Serveur API'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Authentification JWT Bearer Token'
)]
#[OA\Tag(name: 'Auth', description: 'Authentification et gestion du compte')]
#[OA\Tag(name: 'Products', description: 'Catalogue de produits')]
#[OA\Tag(name: 'Categories', description: 'Catégories de produits')]
#[OA\Tag(name: 'Cart', description: 'Gestion du panier')]
#[OA\Tag(name: 'Orders', description: 'Gestion des commandes')]
#[OA\Tag(name: 'Payments', description: 'Paiements CinetPay')]
#[OA\Tag(name: 'Contact', description: 'Formulaire de contact')]
#[OA\Tag(name: 'Admin - Dashboard', description: 'Tableau de bord administrateur')]
#[OA\Tag(name: 'Admin - Categories', description: 'Gestion des catégories (Admin)')]
#[OA\Tag(name: 'Admin - Products', description: 'Gestion des produits (Admin)')]
#[OA\Tag(name: 'Admin - Orders', description: 'Gestion des commandes (Admin)')]
#[OA\Tag(name: 'Admin - Media', description: 'Gestion des médias (Admin)')]
abstract class Controller
{
    //
}
