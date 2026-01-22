<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
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
            'message' => 'Votre message a été envoyé avec succès'
        ], 201);
    }
}
