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
        Schema::table('rental_items', function (Blueprint $table) {
            // Passo 1: Remover a chave estrangeira pela coluna. É mais robusto.
            $table->dropForeign(['asset_id']);

            // Passo 2: Agora que a dependência foi removida, apagar o índice 'unique'.
            $table->dropUnique('rental_items_asset_id_unique');

            // Passo 3: Recriar a chave estrangeira. O Laravel irá criar um índice simples (não-único) por padrão.
            $table->foreign('asset_id')
                  ->references('id')
                  ->on('assets')
                  ->onDelete('set null'); // Opcional, mas bom: se um asset for apagado, o aluguel não quebra.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_items', function (Blueprint $table) {
            // Desfaz as operações na ordem inversa
            $table->dropForeign(['asset_id']);
            $table->unique('asset_id'); // Adiciona o índice unique de volta
            $table->foreign('asset_id')->references('id')->on('assets');
        });
    }
};
