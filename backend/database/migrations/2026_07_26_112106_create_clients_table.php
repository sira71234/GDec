<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // MODIFICATION : Kévin
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            // MODIFICATION 1 : Index sur le nom
            // RAISON : Permet à l'administrateur de chercher un client par son nom instantanément
            // au milieu de milliers d'autres fiches.
            $table->string('nom')->index();

            // Toujours obligatoire : c'est le moyen de contact principal
            $table->string('telephone')->index();

            // MODIFICATION 2 : email nullable + index 
            // RAISON : Le dictionnaire de données précise "email nullable" — un client
            // peut commander sans email. Le client n'a pas de compte, donc pas de contrainte
            // unique : il doit pouvoir repasser commande avec le même email sans être bloqué.
            // L'index permet à l'admin de regrouper rapidement l'historique par email.
            $table->string('email')->nullable()->index();

            // MODIFICATION 3 : adresse nullable
            // RAISON : le dictionnaire de données la marque nullable (pas toujours renseignée
            // à la commande, ex. si retrait sur place).
            $table->string('adresse')->nullable();

            $table->timestamps();

            // MODIFICATION 4 : Gestion de la corbeille (deleted_at)
            // RAISON : Si l'administrateur "supprime" un client, sa fiche est simplement masquée.
            // Cela évite de casser l'affichage de l'historique des commandes et empêche l'application
            // de planter si une commande référence encore ce client.
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};