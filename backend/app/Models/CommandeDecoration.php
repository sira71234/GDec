<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommandeDecoration extends Model
{
        // la table a utiliser
    protected $table = 'commande_decoration';

    protected $fillable = ['commande_id', 'prestation_decoration_id', 'quantite'];

    public function prestation()
    {
        return $this->belongsTo(PrestationDecoration::class, 'prestation_decoration_id');
    }

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }
}