<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommandeBisen extends Model
{
    protected $table = 'commande_bisens';

    protected $fillable = [
        'telephone',
        'nom_client',
        'adresse',
        'commercial',
        'produits',
        'saisie_par',
    ];

    public function saisisseur()
    {
        return $this->belongsTo(User::class, 'saisie_par');
    }
}