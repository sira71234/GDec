<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ElementDecor extends Model
{
    use HasFactory, SoftDeletes;

    // nom de table explicite requis.
    protected $table = 'elements_decor';

    protected $fillable = [
        'nom',
        'description',
    ];

    // Relation N—N simple.
    public function commandes()
    {
        return $this->belongsToMany(Commande::class, 'commande_elements_decor')
            ->withTimestamps();
    }
}