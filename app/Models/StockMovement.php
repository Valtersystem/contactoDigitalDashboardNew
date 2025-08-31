<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'rental_id',
        'type',
        'quantity_change',
        'stock_after_change',
        'notes',
    ];

    // Desativa a atualização do `updated_at`
    public const UPDATED_AT = null;

    /**
     * Um movimento de estoque pertence a um produto.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
