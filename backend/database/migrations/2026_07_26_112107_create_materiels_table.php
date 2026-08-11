<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materiels', function (Blueprint $table) {
            $table->id();

            $table->string('nom')->index();
            $table->text('description')->nullable();
            $table->string('photo');
            $table->decimal('prix_unitaire', 10, 2);
            $table->unsignedInteger('quantite_stock')->default(0);

            $table->timestamps();

            // CORBEILLE : un article retiré du catalogue reste visible dans
            // les commandes passées (via commande_materiel) sans être hard-delete
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materiels');
    }
};