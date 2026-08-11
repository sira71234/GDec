<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrestationDecoration extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'prestations_decoration';

    protected $fillable = [
        'nom',
        'description',
        'photo',
        'prix',
    ];

    protected $casts = [
        'prix' => 'decimal:2',
    ];

    // Relation N—N vers commandes via commande_decoration, quantité facultative sur le pivot.
    public function commandes()
    {
        return $this->belongsToMany(Commande::class, 'commande_decoration')
            ->withPivot('quantite')
            ->withTimestamps();
    }
}