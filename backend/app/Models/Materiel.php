<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Materiel extends Model
{
    protected $fillable = ['nom', 'description', 'photo', 'prix_unitaire', 'quantite_stock'];
}