<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommandeElementsDecor extends Model
{
    protected $table = 'commande_elements_decor';

    protected $fillable = [
        'commande_id',
        'element_decor_id',
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function elementDecor()
    {
        return $this->belongsTo(ElementDecor::class);
    }
}