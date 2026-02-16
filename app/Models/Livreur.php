<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Livreur extends Model
{
    use HasFactory;

  protected $fillable = [
    'nom',
    'telephone',
    'photo',
    'disponible',
    'boutique_id',
    'actif',
];

    protected $casts = [
        'disponible' => 'boolean',
        'actif' => 'boolean',
    ];

    /**
     * Relations
     */
    public function boutique()
    {
        return $this->belongsTo(Boutique::class);
    }

    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }

    /**
     * Scopes
     */
    public function scopeDisponibles($query)
    {
        return $query->where('disponible', true)->where('actif', true);
    }

    public function scopeActifs($query)
    {
        return $query->where('actif', true);
    }
}
