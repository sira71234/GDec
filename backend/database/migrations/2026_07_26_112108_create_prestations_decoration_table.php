<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestations_decoration', function (Blueprint $table) {
            $table->id();

            $table->string('nom')->index();
            $table->text('description')->nullable();
            $table->string('photo');
            $table->decimal('prix', 10, 2);

            $table->timestamps();

            // CORBEILLE : même logique que materiels
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestations_decoration');
    }
};