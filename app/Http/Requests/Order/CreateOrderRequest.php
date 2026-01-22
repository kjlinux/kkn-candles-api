<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Le prénom est requis',
            'last_name.required' => 'Le nom est requis',
            'email.required' => 'L\'email est requis',
            'email.email' => 'L\'email doit être valide',
            'phone.required' => 'Le téléphone est requis',
            'address.required' => 'L\'adresse est requise',
            'city.required' => 'La ville est requise',
            'items.required' => 'La commande doit contenir au moins un article',
            'items.*.product_id.exists' => 'Un des produits n\'existe pas',
            'items.*.quantity.min' => 'La quantité minimum est de 1',
        ];
    }
}
