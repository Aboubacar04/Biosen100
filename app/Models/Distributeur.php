<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Distributeur extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'nom',
        'telephone',
        'code_pin',
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