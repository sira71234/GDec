<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActionHistorique extends Model
{
    use HasFactory;

    protected $fillable = [
        'commande_id',
        'admin_id',
        'action',
        'description',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
