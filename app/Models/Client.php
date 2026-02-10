<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom_complet',
        'telephone',
        'adresse',
        'boutique_id',
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
     * Méthodes utiles
     */
    public function totalCommandes()
    {
        return $this->commandes()->where('statut', 'validee')->sum('total');
    }

    public function nombreCommandes()
    {
        return $this->commandes()->where('statut', 'validee')->count();
    }
}
