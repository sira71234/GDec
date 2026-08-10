<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commande_materiel', function (Blueprint $table) {
            $table->id();

            // cascade : si une commande est hard-deleted (rare, cas admin),
            // ses lignes de détail n'ont plus de raison d'exister
            $table->foreignId('commande_id')->constrained('commandes')->onDelete('cascade');

            // restrict : protège l'intégrité si quelqu'un tente un hard delete
            // du matériel malgré la corbeille
            $table->foreignId('materiel_id')->constrained('materiels')->onDelete('restrict');

            $table->unsignedInteger('quantite');

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commande_materiel');
    }
};