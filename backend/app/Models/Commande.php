<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    protected $fillable = [
        'client_id', 'type_prestation', 'statut', 'montant_caution',
        'date_debut_location', 'date_fin_location',
        'piece_identite_type', 'piece_identite_photo',
        'type_evenement', 'theme', 'couleurs', 'nombre_personnes', 'complement',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function materiels()
    {
        return $this->hasMany(CommandeMateriel::class);
    }

    public function decorations()
    {
        return $this->hasMany(CommandeDecoration::class);
    }
}