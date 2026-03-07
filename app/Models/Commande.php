<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_commande',
        'boutique_id',
        'client_id',
        'employe_id',
        'livreur_id',
        'type_commande',
        'statut',
        'total',
        'date_commande',
        'date_validation',
        'date_annulation',        // ✅ NOUVEAU
        'raison_annulation',      // ✅ NOUVEAU
        'annulee_par',            // ✅ NOUVEAU
        'notes',
        'numero_gp',
        'paye',
        'statut_livraison',
        'date_livraison',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'date_commande' => 'date',
        'date_validation' => 'datetime',
        'date_annulation' => 'datetime', 
        'date_livraison' => 'datetime', // ✅ NOUVEAU
        'paye' => 'boolean',
    ];

    /**
     * Relations
     */
    public function boutique()
    {
        return $this->belongsTo(Boutique::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }

    public function livreur()
    {
        return $this->belongsTo(Livreur::class);
    }

    public function produits()
    {
        return $this->belongsToMany(Produit::class, 'commande_produit')
            ->withPivot('quantite', 'prix_unitaire', 'sous_total')
            ->withTimestamps();
    }

    public function facture()
    {
        return $this->hasOne(Facture::class);
    }

    // 🔥 NOUVELLE RELATION : Qui a annulé la commande
    public function annuleePar()
    {
        return $this->belongsTo(User::class, 'annulee_par');
    }

    /**
     * Scopes
     */
    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }

    public function scopeValidees($query)
    {
        return $query->where('statut', 'validee');
    }

    public function scopeAnnulees($query)
    {
        return $query->where('statut', 'annulee');
    }

    public function scopeDuJour($query)
    {
        return $query->whereDate('date_commande', today());
    }

    public function scopeDuMois($query)
    {
        return $query->whereMonth('date_commande', now()->month)
            ->whereYear('date_commande', now()->year);
    }

    public function scopeDeLAnnee($query)
    {
        return $query->whereYear('date_commande', now()->year);
    }

    /**
     * 🔥 MÉTHODE : Valider une commande
     */
    public function valider()
    {
        if ($this->statut !== 'en_cours') {
            throw new \Exception('Seules les commandes en cours peuvent être validées');
        }

        $data = [
            'statut' => 'validee',
            'date_validation' => now(),
        ];

        if ($this->type_commande === 'livraison') {
            $data['statut_livraison'] = 'en_attente';
        }

        $this->update($data);

        foreach ($this->produits as $produit) {
            $produit->decrementerStock($produit->pivot->quantite);
        }

        $this->genererFacture();

        return $this;
    }

    /**
     * 🔥 NOUVELLE MÉTHODE : Annuler une commande (même validée !)
     */
    public function annuler($raison = null, $userId = null)
    {
        // 1. Si la commande est validée, remettre le stock
        if ($this->statut === 'validee') {
            foreach ($this->produits as $produit) {
                // Remettre le stock
                $produit->increment('stock', $produit->pivot->quantite);
            }
        }

        // 2. Mettre à jour le statut
        $this->update([
            'statut' => 'annulee',
            'date_annulation' => now(),
            'raison_annulation' => $raison,
            'annulee_par' => $userId,
        ]);

        // 3. LA FACTURE RESTE EN BASE (traçabilité)
        // On ne supprime RIEN !

        return $this;
    }

   public function genererFacture()
{
    if ($this->facture) {
        return $this->facture;
    }

    $annee = now()->year;
    $prefix = 'FAC-' . $annee . '-';

    $dernierNumero = Facture::whereYear('created_at', $annee)
        ->selectRaw("MAX(CAST(SUBSTRING(numero_facture, " . (strlen($prefix) + 1) . ") AS UNSIGNED)) as max_num")
        ->value('max_num') ?? 0;

    $numeroFacture = $prefix . str_pad($dernierNumero + 1, 5, '0', STR_PAD_LEFT);

    return Facture::create([
        'commande_id' => $this->id,
        'numero_facture' => $numeroFacture,
        'date_facture' => now(),
        'montant_total' => $this->total,
    ]);
}
    /**
     * Boot
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($commande) {
            // Générer le numéro de commande
            if (!$commande->numero_commande) {
                $annee = now()->year;
             $dernierNumero = (int) Commande::whereYear('created_at', $annee)
    ->selectRaw("MAX(CAST(SUBSTRING(numero_commande, 10) AS UNSIGNED)) as max_num")
    ->value('max_num');
$dernierNumero = $dernierNumero + 1;
                $commande->numero_commande = 'CMD-' . $annee . '-' . str_pad($dernierNumero, 5, '0', STR_PAD_LEFT);
            }

            // Définir la date_commande
            if (!$commande->date_commande) {
                $commande->date_commande = today();
            }
        });
    }
}
