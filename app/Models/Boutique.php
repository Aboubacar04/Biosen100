<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Boutique extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'adresse',
        'telephone',
        'logo',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    /**
     * Relations
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function employes()
    {
        return $this->hasMany(Employe::class);
    }

    public function livreurs()
    {
        return $this->hasMany(Livreur::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function categories()
    {
        return $this->hasMany(Categorie::class);
    }

    public function produits()
    {
        return $this->hasMany(Produit::class);
    }

    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }

    public function depenses()
    {
        return $this->hasMany(Depense::class);
    }

    /**
     * Scopes
     */
    public function scopeActives($query)
    {
        return $query->where('actif', true);
    }
}
