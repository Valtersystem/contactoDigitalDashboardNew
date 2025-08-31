<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'description',
        'category_id',
        'tracking_type',
        'stock_quantity',
        'replacement_value',
        'image_url',
        'is_active',
    ];

    /**
     * Um produto pertence a uma categoria.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Um produto pode ter vários itens físicos (assets) se for serializado.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
