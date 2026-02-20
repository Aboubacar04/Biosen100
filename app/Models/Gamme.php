<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gamme extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'description',
        'boutique_id',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function boutique()
    {
        return $this->belongsTo(Boutique::class);
    }

    public function produits()
    {
        return $this->belongsToMany(Produit::class, 'gamme_produit')
                    ->withPivot('quantite')
                    ->withTimestamps();
    }
}