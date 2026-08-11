<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Devis extends Model
{
    use HasFactory;
    // Pas de SoftDeletes : un devis est un document généré et archivé,
    // pas une donnée de référence qu'on "restaure". Le dictionnaire ne
    // le liste pas non plus parmi les tables à corbeille.

    protected $fillable = [
        'commande_id',
        'fichier_pdf',
        'montant_total',
        'date_generation',
    ];

    protected $casts = [
        'montant_total' => 'decimal:2',
        'date_generation' => 'datetime',
    ];

    // Un devis est toujours rattaché à une commande précise.
    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }
}