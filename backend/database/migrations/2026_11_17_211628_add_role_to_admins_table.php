<?php

use App\Models\Admin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            // Rôle du compte : "admin" (espace administrateur) ou
            // "super_admin" (gestion des comptes, en plus de l'espace admin).
            $table->string('role')->default(Admin::ROLE_ADMIN)->after('code_admin');
        });

        // Ne jamais laisser la base sans super administrateur : le plus ancien
        // compte existant est promu une seule fois.
        if (DB::table('admins')->where('role', Admin::ROLE_SUPER_ADMIN)->doesntExist()) {
            DB::table('admins')
                ->orderBy('id')
                ->limit(1)
                ->update(['role' => Admin::ROLE_SUPER_ADMIN]);
        }
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
