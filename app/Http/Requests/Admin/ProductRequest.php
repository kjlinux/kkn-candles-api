<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'category_id' => 'required|uuid|exists:categories,id',
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($productId),
            ],
            'description' => 'nullable|string|max:1000',
            'long_description' => 'nullable|string',
            'price' => 'required|integer|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'in_stock' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'images' => 'nullable|array',
            'images.*' => 'string|url',
            'specifications' => 'nullable|array',
            'sort_order' => 'integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'La catégorie est requise',
            'category_id.exists' => 'La catégorie n\'existe pas',
            'name.required' => 'Le nom est requis',
            'slug.required' => 'Le slug est requis',
            'slug.unique' => 'Ce slug est déjà utilisé',
            'price.required' => 'Le prix est requis',
            'price.min' => 'Le prix doit être positif',
            'stock_quantity.required' => 'La quantité en stock est requise',
        ];
    }
}
