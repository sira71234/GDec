<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('action_historiques', function (Blueprint $table) {
            $table->id();

            // nullOnDelete : l'historique survit à la suppression définitive
            // d'une commande (on conserve la trace de l'action).
            $table->foreignId('commande_id')->nullable()->constrained('commandes')->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();

            $table->string('action')->index();
            $table->text('description');
            $table->json('details')->nullable();

            $table->timestamps();
            $table->index(['commande_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_historiques');
    }
};
