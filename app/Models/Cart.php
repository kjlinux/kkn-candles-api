<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
    ];

    // Accessors
    public function getTotalAttribute(): int
    {
        return $this->items->sum(fn($item) => $item->quantity * $item->unit_price);
    }

    public function getItemsCountAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Items avec leurs produits existants uniquement (filtre les produits supprimés)
     */
    public function validItems(): HasMany
    {
        return $this->hasMany(CartItem::class)->whereHas('product');
    }

    /**
     * Supprime les items dont le produit n'existe plus
     */
    public function cleanOrphanItems(): int
    {
        return $this->items()
            ->whereDoesntHave('product')
            ->delete();
    }
}
