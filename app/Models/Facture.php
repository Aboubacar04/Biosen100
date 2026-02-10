<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facture extends Model
{
    use HasFactory;

    protected $fillable = [
        'commande_id',
        'numero_facture',
        'date_facture',
        'montant_total',
        'statut',  // ✅ NOUVEAU
    ];

    protected $casts = [
        'date_facture' => 'datetime',
        'montant_total' => 'decimal:2',
    ];

    /**
     * Relations
     */
    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    /**
     * 🔥 NOUVELLE MÉTHODE : Annuler la facture
     */
    public function annuler()
    {
        $this->update(['statut' => 'annulee']);
        return $this;
    }

    /**
     * Scopes
     */
    public function scopeActives($query)
    {
        return $query->where('statut', 'active');
    }

    public function scopeAnnulees($query)
    {
        return $query->where('statut', 'annulee');
    }
}
