<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    // Hérite d'Authenticatable (pas Model) : ce modèle sert à la connexion,
    // ça active tout le système d'auth de Laravel (Auth::attempt, etc.)
    // pour la route de connexion admin dédiée .
    use HasFactory;

    protected $fillable = [
        'identifiant',
        'mot_de_passe',
        'code_admin',
    ];

    protected $hidden = [
        'mot_de_passe',
        'code_admin',
    ];

    //hachage automatique à l'écriture.
    
    protected $casts = [
        'mot_de_passe' => 'hashed',
        'code_admin' => 'hashed',
    ];

    // Laravel s'attend par défaut à un champ nommé "password" pour l'auth.
    // On lui indique explicitement d'utiliser "mot_de_passe" à la place.
    public function getAuthPassword()
    {
        return $this->mot_de_passe;
    }
}