<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'serial_number',
        'status',
        'notes',
    ];

    /**
     * Um asset pertence a um modelo de produto.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
