<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Employe extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'nom',
        'telephone',
        'code_pin',
        'photo',
        'boutique_id',
        'actif',
    ];

    protected $hidden = [
        'code_pin',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function boutique()
    {
        return $this->belongsTo(Boutique::class);
    }

    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }

    public function scopeActifs($query)
    {
        return $query->where('actif', true);
    }

    public function isAdmin(): bool
    {
        return false;
    }
    public function isGerant(): bool
{
    return false;
}

public function isSaisisseur(): bool
{
    return false;
}
}