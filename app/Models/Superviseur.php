<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Superviseur extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'superviseurs';

    protected $fillable = [
        'nom', 'telephone', 'code_pin', 'boutique_id', 'actif',
    ];

    protected $hidden = ['code_pin'];

    protected $casts = ['actif' => 'boolean'];

    public function boutique()
    {
        return $this->belongsTo(Boutique::class);
    }

    public function isAdmin(): bool
    {
        return false;
    }
}