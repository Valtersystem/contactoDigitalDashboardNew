<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'product_id',
        'status',
        'start_date',
        'next_billing_date',
        'cancellation_date',
        'license_key',
    ];

    protected $casts = [
        'start_date' => 'date',
        'next_billing_date' => 'date',
        'cancellation_date' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
