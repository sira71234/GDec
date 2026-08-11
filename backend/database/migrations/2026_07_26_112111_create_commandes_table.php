<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();

            // restrict : on ne peut pas hard-delete un client qui a des
            // commandes ; il faut le mettre à la corbeille (soft delete) à la place
            $table->foreignId('client_id')->constrained('clients')->onDelete('restrict');

            $table->enum('type_prestation', ['location', 'decoration', 'les_deux']);
            $table->enum('statut', ['en_attente', 'valide', 'refuse'])->default('en_attente')->index(); // filtre fréquent côté admin dashboard

            $table->decimal('montant_caution', 10, 2)->nullable();

            $table->date('date_debut_location')->nullable();
            $table->date('date_fin_location')->nullable();

            $table->string('piece_identite_type')->nullable();
            $table->string('piece_identite_photo')->nullable();

            $table->string('type_evenement')->nullable();
            $table->text('theme')->nullable();
            $table->string('couleurs')->nullable();
            $table->unsignedInteger('nombre_personnes')->nullable();
            $table->text('complement')->nullable();

            $table->timestamps();

            // CORBEILLE : une commande annulée/refusée reste archivée,
            // consultable pour les stats, mais hors des listes actives
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};