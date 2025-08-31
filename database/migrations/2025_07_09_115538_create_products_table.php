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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku', 100)->unique()->comment('Código único do MODELO');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('categories');
            $table->string('tracking_type', 20)->default('BULK')->comment('"BULK" para itens em massa, "SERIALIZED" para únicos');
            $table->unsignedInteger('stock_quantity')->default(0)->comment('Apenas para itens do tipo BULK');
            $table->decimal('replacement_value', 8, 2)->comment('Custo de reposição');
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
