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
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->enum('type_prestation', ['location', 'decoration', 'les_deux']);
            $table->enum('statut', ['en_attente', 'valide', 'refuse'])->default('en_attente');
            $table->decimal('montant_caution', 10, 2)->nullable();

            // Champs liés à la location
            $table->date('date_debut_location')->nullable();
            $table->date('date_fin_location')->nullable();
            $table->string('piece_identite_type')->nullable();
            $table->string('piece_identite_photo')->nullable();

            // Champs liés à la décoration
            $table->string('type_evenement')->nullable();
            $table->text('theme')->nullable();
            $table->string('couleurs')->nullable();
            $table->integer('nombre_personnes')->nullable();

            $table->text('complement')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};