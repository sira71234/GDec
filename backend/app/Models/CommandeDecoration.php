<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommandeDecoration extends Model
{
    protected $table = 'commande_decoration';

    protected $fillable = [
        'commande_id',
        'prestation_decoration_id',
        'quantite',
    ];

    protected $casts = [
        'quantite' => 'integer',
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function prestationDecoration()
    {
        return $this->belongsTo(PrestationDecoration::class);
    }
}