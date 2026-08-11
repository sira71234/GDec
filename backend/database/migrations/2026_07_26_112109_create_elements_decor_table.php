<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elements_decor', function (Blueprint $table) {
            $table->id();

            $table->string('nom')->index();
            $table->text('description')->nullable();

            $table->timestamps();

            // CORBEILLE : liste "fixe" en théorie, mais un élément peut être
            // retiré sans casser les sélections déjà enregistrées côté client
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elements_decor');
    }
};