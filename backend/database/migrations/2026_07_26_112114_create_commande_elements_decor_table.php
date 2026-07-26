<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commande_elements_decor', function (Blueprint $table) {
            $table->id();

            $table->foreignId('commande_id')->constrained('commandes')->onDelete('cascade');

            $table->foreignId('element_decor_id')->constrained('elements_decor')->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commande_elements_decor');
    }
};