<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalItem extends Model
{
    use HasFactory;

    // Nome da tabela, já que o Laravel esperaria "rental_items"
    protected $table = 'rental_items';

    protected $fillable = [
        'rental_id',
        'product_id',
        'quantity_rented',
        'asset_id',
        'quantity_returned',
        'quantity_damaged',
        'quantity_lost',
    ];

    /**
     * Um item de aluguel pertence a um aluguel.
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    /**
     * Um item de aluguel refere-se a um modelo de produto.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Um item de aluguel pode referir-se a um asset específico.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
