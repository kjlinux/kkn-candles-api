<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'long_description',
        'price',
        'stock_quantity',
        'in_stock',
        'is_featured',
        'is_active',
        'images',
        'specifications',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock_quantity' => 'integer',
            'in_stock' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'images' => 'array',
            'specifications' => 'array',
            'sort_order' => 'integer',
        ];
    }

    // Accessors
    public function getMainImageAttribute(): ?string
    {
        return $this->images[0] ?? null;
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 0, ',', ' ') . ' FCFA';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('in_stock', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Methods
    public function decrementStock(int $quantity): void
    {
        $this->decrement('stock_quantity', $quantity);

        if ($this->stock_quantity <= 0) {
            $this->update(['in_stock' => false]);
        }
    }

    public function incrementStock(int $quantity): void
    {
        $this->increment('stock_quantity', $quantity);

        if ($this->stock_quantity > 0 && !$this->in_stock) {
            $this->update(['in_stock' => true]);
        }
    }

    // Relations
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
