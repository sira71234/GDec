<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommandeMateriel extends Model
{

    protected $table = 'commande_materiel';

    protected $fillable = [
        'commande_id',
        'materiel_id',
        'quantite',
    ];

    protected $casts = [
        'quantite' => 'integer',
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function materiel()
    {
        return $this->belongsTo(Materiel::class);
    }

    // Note d'usage : ce modèle dédié n'est pas obligatoire pour que le
    // belongsToMany() de Commande/Materiel fonctionne — il est là au cas où
    // on doit manipuler une ligne pivot directement (ex: corriger une quantité).
}