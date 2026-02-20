<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produit extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'description',
        'prix_vente',
        'stock',
        'seuil_alerte',
        'image',
        'categorie_id',
        'boutique_id',
        'actif',
    ];

    protected $casts = [
        'prix_vente' => 'decimal:2',
        'actif' => 'boolean',
    ];

    /**
     * Relations
     */
    public function categorie()
    {
        return $this->belongsTo(Categorie::class);
    }

    public function boutique()
    {
        return $this->belongsTo(Boutique::class);
    }

    public function commandes()
    {
        return $this->belongsToMany(Commande::class, 'commande_produit')
            ->withPivot('quantite', 'prix_unitaire', 'sous_total')
            ->withTimestamps();
    }

    /**
     * Méthodes utiles
     */
    public function isStockFaible()
    {
        return $this->seuil_alerte && $this->stock <= $this->seuil_alerte;
    }

    public function decrementerStock($quantite)
    {
        $this->decrement('stock', $quantite);
    }

    /**
     * Scopes
     */
    public function scopeActifs($query)
    {
        return $query->where('actif', true);
    }

    public function scopeStockFaible($query)
    {
        return $query->whereNotNull('seuil_alerte')
            ->whereColumn('stock', '<=', 'seuil_alerte');
    }

    public function gammes()
{
    return $this->belongsToMany(Gamme::class, 'gamme_produit')
                ->withPivot('quantite')
                ->withTimestamps();
}
}
