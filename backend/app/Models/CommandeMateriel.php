<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommandeMateriel extends Model
{

// la table a utiliser

    protected $table = 'commande_materiel';

    protected $fillable = ['commande_id', 'materiel_id', 'quantite'];

    public function materiel()
    {
        return $this->belongsTo(Materiel::class);
    }

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }
}