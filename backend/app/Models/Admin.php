<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    // Hérite d'Authenticatable (pas Model) : ce modèle sert à la connexion,
    // ça active tout le système d'auth de Laravel (Auth::attempt, etc.)
    // pour la route de connexion admin dédiée .
    // HasApiTokens : permet de générer des jetons Sanctum pour l'API admin.
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'identifiant',
        'mot_de_passe',
        'code_admin',
        'role',
    ];

    // Rôles disponibles. Un super administrateur peut tout faire dans
    // l'espace admin et gère en plus les comptes administrateurs.
    public const ROLE_ADMIN = 'admin';

    public const ROLE_SUPER_ADMIN = 'super_admin';

    protected $hidden = [
        'mot_de_passe',
        'code_admin',
    ];

    // hachage automatique à l'écriture.

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

    /** Indique si le compte peut gérer les autres administrateurs. */
    public function estSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }
}
