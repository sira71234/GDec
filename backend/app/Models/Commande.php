<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Commande extends Model
{
    use HasFactory, SoftDeletes;
    // SoftDeletes : une commande annulée/supprimée reste consultable en corbeille
    // par l'admin (utile pour l'historique et les litiges éventuels).

    // Tous les champs métiers du dictionnaire. Certains sont nullable en base
    // selon que le client a choisi "location", "decoration" ou "les_deux"
    protected $fillable = [
        'client_id',
        'type_prestation',      // enum: location / decoration / les_deux
        'statut',                // enum: en_attente / valide / refuse
        'montant_caution',
        'date_debut_location',
        'date_fin_location',
        'piece_identite_type',
        'piece_identite_photo',
        'type_evenement',
        'theme',
        'couleurs',
        'nombre_personnes',
        'complement',
    ];

    protected $casts = [
        'montant_caution' => 'decimal:2',
        'date_debut_location' => 'date',
        'date_fin_location' => 'date',
        'nombre_personnes' => 'integer',
    ];

    // Une commande appartient à un seul client.
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // Matériel loué pour cette commande (avec quantité par article).
    public function materiels()
    {
        return $this->belongsToMany(Materiel::class, 'commande_materiel')
            ->withPivot('quantite')
            ->withTimestamps();
    }

    // Prestations de décoration choisies.
    // Clés précisées explicitement car le nom auto-deviné par Laravel
    // ne correspondrait pas à "prestation_decoration_id" en base.
    public function prestationsDecoration()
    {
        return $this->belongsToMany(
            PrestationDecoration::class,
            'commande_decoration',
            'commande_id',
            'prestation_decoration_id'
        )->withPivot('quantite')->withTimestamps();
    }

    // Éléments à décorer sélectionnés .
    public function elementsDecor()
    {
        return $this->belongsToMany(
            ElementDecor::class,
            'commande_elements_decor',
            'commande_id',
            'element_decor_id'
        )->withTimestamps();
    }

    // Une commande validée peut donner lieu à un ou plusieurs devis.
    public function devis()
    {
        return $this->hasMany(Devis::class);
    }
}