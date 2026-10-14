<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contenus_vitrine', function (Blueprint $table) {
            $table->id();

            // Zone de la page d'accueil : banniere, galerie ou section libre.
            $table->string('section')->index();

            // Clé logique d'un contenu de bannière (ex. "titre", "sous_titre", "image").
            $table->string('cle')->nullable();

            // Nature du contenu : texte ou image.
            $table->string('type')->default('texte');

            // Titre facultatif (légende d'image, en-tête d'une section libre).
            $table->string('titre')->nullable();

            // Texte saisi ou chemin de l'image stockée.
            $table->text('valeur')->nullable();

            // Ordre d'affichage (galerie et sections libres).
            $table->unsignedInteger('ordre')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contenus_vitrine');
    }
};
