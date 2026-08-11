<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;
    // SoftDeletes : un client supprimé passe en corbeille (deleted_at rempli)
    // au lieu d'être effacé définitivement. 

    // Champs autorisés à être remplis via create()/update() (mass assignment).
    // Correspond exactement aux colonnes du dictionnaire de données, hors id/timestamps.
    protected $fillable = [
        'nom',
        'telephone',
        'email',
        'adresse',
    ];

    // Un client peut soumettre plusieurs commandes.
    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }
}