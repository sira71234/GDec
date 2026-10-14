<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContenuVitrine extends Model
{
    use HasFactory;

    protected $table = 'contenus_vitrine';

    protected $fillable = [
        'section',
        'cle',
        'type',
        'titre',
        'valeur',
        'ordre',
    ];

    protected $casts = [
        'ordre' => 'integer',
    ];

    // Sections gérées par la vitrine.
    public const SECTION_BANNIERE = 'banniere';

    public const SECTION_GALERIE = 'galerie';

    public const SECTION_LIBRE = 'section';

    public const TYPE_TEXTE = 'texte';

    public const TYPE_IMAGE = 'image';
}
