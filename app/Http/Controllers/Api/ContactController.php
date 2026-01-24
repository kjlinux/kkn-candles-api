<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ContactController extends Controller
{
    #[OA\Post(
        path: '/contact',
        operationId: 'sendContactMessage',
        summary: 'Envoyer un message de contact',
        description: 'Envoyer un message via le formulaire de contact',
        tags: ['Contact'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'subject', 'message'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 100, example: 'Jean Dupont'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'jean@example.com'),
                    new OA\Property(property: 'phone', type: 'string', maxLength: 20, nullable: true, example: '+225 07 00 00 00 00'),
                    new OA\Property(property: 'subject', type: 'string', maxLength: 200, example: 'Question sur une commande'),
                    new OA\Property(property: 'message', type: 'string', maxLength: 2000, example: 'Bonjour, je souhaite avoir des informations sur...'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Message envoyé avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Votre message a été envoyé avec succès'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Erreur de validation'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:200',
            'message' => 'required|string|max:2000',
        ], [
            'name.required' => 'Le nom est requis',
            'email.required' => 'L\'email est requis',
            'email.email' => 'L\'email doit être valide',
            'subject.required' => 'Le sujet est requis',
            'message.required' => 'Le message est requis',
        ]);

        ContactMessage::create($request->only([
            'name',
            'email',
            'phone',
            'subject',
            'message',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Votre message a été envoyé avec succès',
        ], 201);
    }
}
