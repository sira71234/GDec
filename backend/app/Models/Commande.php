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
        'montant_decoration',
        'date_debut_location',
        'date_fin_location',
        'date_evenement',
        'duree_decoration',
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
        'montant_decoration' => 'decimal:2',
        'date_debut_location' => 'date',
        'date_fin_location' => 'date',
        'date_evenement' => 'date',
        'duree_decoration' => 'integer',
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

    // Journal des actions effectuées sur la commande (création, modification…).
    public function historiques()
    {
        return $this->hasMany(ActionHistorique::class)->latest();
    }

    // Dernière action, affichée directement dans la liste des demandes.
    public function derniereAction()
    {
        return $this->hasOne(ActionHistorique::class)->latestOfMany();
    }

    /**
     * Commandes encore actives : leur date de référence n'est pas dépassée
     * de plus de deux jours. Les commandes sans aucune date restent actives.
     */
    public function scopeActives($query)
    {
        $limite = now()->subDays(2)->toDateString();

        return $query->where(function ($q) use ($limite) {
            $q->where(function ($q2) use ($limite) {
                $q2->whereNotNull('date_evenement')
                    ->whereDate('date_evenement', '>=', $limite);
            })->orWhere(function ($q2) use ($limite) {
                $q2->whereNotNull('date_fin_location')
                    ->whereDate('date_fin_location', '>=', $limite);
            })->orWhere(function ($q2) {
                $q2->whereNull('date_evenement')
                    ->whereNull('date_fin_location');
            });
        });
    }

    /**
     * Commandes passées : toutes leurs dates connues sont antérieures à
     * (aujourd'hui - 2 jours). Elles basculent dans l'historique.
     */
    public function scopeHistoriques($query)
    {
        $limite = now()->subDays(2)->toDateString();

        return $query
            ->where(function ($q) {
                $q->whereNotNull('date_evenement')
                    ->orWhereNotNull('date_fin_location');
            })
            ->where(function ($q) use ($limite) {
                $q->where(function ($q2) use ($limite) {
                    $q2->whereNull('date_evenement')
                        ->orWhereDate('date_evenement', '<', $limite);
                })->where(function ($q2) use ($limite) {
                    $q2->whereNull('date_fin_location')
                        ->orWhereDate('date_fin_location', '<', $limite);
                });
            });
    }
}
