<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = ['nom', 'telephone', 'email', 'adresse'];

    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }
}