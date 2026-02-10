<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Depense extends Model
{
    use HasFactory;

    protected $fillable = [
        'boutique_id',
        'description',
        'montant',
        'categorie',
        'date_depense',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_depense' => 'date',
    ];

    /**
     * Relations
     */
    public function boutique()
    {
        return $this->belongsTo(Boutique::class);
    }

    /**
     * Scopes
     */
    public function scopeDuJour($query)
    {
        return $query->whereDate('date_depense', today());
    }

    public function scopeDuMois($query)
    {
        return $query->whereMonth('date_depense', now()->month)
            ->whereYear('date_depense', now()->year);
    }

    public function scopeDeLAnnee($query)
    {
        return $query->whereYear('date_depense', now()->year);
    }

    public function scopeParDate($query, $date)
    {
        return $query->whereDate('date_depense', $date);
    }
}
