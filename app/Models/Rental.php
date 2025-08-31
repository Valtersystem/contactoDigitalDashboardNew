<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rental extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'rental_date',
        'expected_return_date',
        'actual_return_date',
        'status',
        'total_replacement_charge',
        'notes',
    ];

    protected $casts = [
        'rental_date' => 'datetime',
        'expected_return_date' => 'datetime',
        'actual_return_date' => 'datetime',
    ];

    /**
     * Um aluguel pertence a um cliente.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Um aluguel tem muitos itens de aluguel.
     */
    public function rentalItems(): HasMany
    {
        return $this->hasMany(RentalItem::class);
    }
}
