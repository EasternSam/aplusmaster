<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_name',
        'license_key',
        'registered_domain',
        'is_active',
        'expires_at',
        'package_id',
        'custom_features',
        'academic_mode', // Nuevo: Permite elegir entre 'courses', 'careers' o 'both'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'date',
        'custom_features' => 'array',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}