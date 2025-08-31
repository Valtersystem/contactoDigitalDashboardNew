<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    // Permitir a atribuição em massa destes campos
    protected $fillable = [
        'name',
        'nif',
        'business_name',
        'email',
        'phone',
        'address',
    ];

    /**
     * Define a relação de que um cliente pode ter vários alugueis.
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }
}
