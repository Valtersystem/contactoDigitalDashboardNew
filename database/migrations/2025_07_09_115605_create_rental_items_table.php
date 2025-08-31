<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rental_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained('rentals')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->comment('Sempre preenchido, para saber o modelo');
            $table->unsignedInteger('quantity_rented')->nullable()->comment('Apenas para itens BULK');
            $table->foreignId('asset_id')->nullable()->unique()->constrained('assets')->comment('ID do item físico específico');
            $table->unsignedInteger('quantity_returned')->default(0);
            $table->unsignedInteger('quantity_damaged')->default(0);
            $table->unsignedInteger('quantity_lost')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_items');
    }
};
