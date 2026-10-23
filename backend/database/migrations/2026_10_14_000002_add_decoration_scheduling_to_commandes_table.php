<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            // Planification d'une décoration : date de l'événement et durée.
            $table->date('date_evenement')->nullable()->after('date_fin_location');
            $table->unsignedInteger('duree_decoration')->nullable()->after('date_evenement');

            // Montant forfaitaire de la décoration saisi par l'administrateur
            // avant la génération du devis. S'il est nul, le montant est calculé
            // à partir des prestations sélectionnées.
            $table->decimal('montant_decoration', 10, 2)->nullable()->after('montant_caution');
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropColumn(['date_evenement', 'duree_decoration', 'montant_decoration']);
        });
    }
};
