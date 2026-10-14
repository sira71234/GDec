<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Correctif : les migrations d'origine de ces tables ont reçu la colonne
 * softDeletes après avoir déjà été exécutées en production/développement.
 * On ajoute donc `deleted_at` de façon idempotente pour les bases existantes.
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private array $tables = [
        'clients',
        'materiels',
        'prestations_decoration',
        'elements_decor',
        'commandes',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropSoftDeletes();
                });
            }
        }
    }
};
