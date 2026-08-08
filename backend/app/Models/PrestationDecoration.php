<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrestationDecoration extends Model
{
    protected $table = 'prestations_decoration';

    protected $fillable = ['nom', 'description', 'photo', 'prix'];
}