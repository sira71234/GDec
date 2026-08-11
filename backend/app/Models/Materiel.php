<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Materiel extends Model
{
    use HasFactory, SoftDeletes;
    // SoftDeletes : si un article de location est retiré du catalogue, on le met en
    // corbeille plutôt que de le supprimer, pour ne pas casser l'historique des
    // commandes passées qui y font référence (contrainte "restrict" en migration).

    protected $fillable = [
        'nom',
        'description',
        'photo',
        'prix_unitaire',
        'quantite_stock',
    ];

    // Casts : force le typage correct en sortie .
    protected $casts = [
        'prix_unitaire' => 'decimal:2',
        'quantite_stock' => 'integer',
    ];

    // Relation N—N vers commandes via la table pivot commande_materiel,
    // avec la quantité choisie comme donnée supplémentaire sur le pivot.
    public function commandes()
    {
        return $this->belongsToMany(Commande::class, 'commande_materiel')
            ->withPivot('quantite')
            ->withTimestamps();
    }

}