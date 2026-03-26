<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ScannerDevice extends Model implements AuthenticatableContract
{
    use Authenticatable, HasUlids;

    protected $fillable = [
        'name',
        'token',
        'otp',
        'otp_expires_at',
        'location_id',
        'is_active',
        'last_used_at',
        'user_agent',
    ];

    public function deviceLabel(): ?string
    {
        if (! $this->user_agent) {
            return null;
        }

        $ua = $this->user_agent;
        $parts = [];

        // Device model
        if (preg_match('/;\s*([^;)]+?)\s*Build\//', $ua, $m)) {
            $parts[] = trim($m[1]);
        } elseif (str_contains($ua, 'iPad')) {
            $parts[] = 'iPad';
        } elseif (str_contains($ua, 'iPhone')) {
            $parts[] = 'iPhone';
        } elseif (str_contains($ua, 'Macintosh')) {
            $parts[] = 'Mac';
        } elseif (str_contains($ua, 'Windows')) {
            $parts[] = 'Windows PC';
        } elseif (str_contains($ua, 'Linux')) {
            $parts[] = 'Linux';
        }

        // Browser
        if (str_contains($ua, 'Firefox/')) {
            $parts[] = 'Firefox';
        } elseif (str_contains($ua, 'Edg/')) {
            $parts[] = 'Edge';
        } elseif (str_contains($ua, 'Chrome/') && ! str_contains($ua, 'Chromium/')) {
            $parts[] = 'Chrome';
        } elseif (str_contains($ua, 'Safari/') && ! str_contains($ua, 'Chrome/')) {
            $parts[] = 'Safari';
        }

        return $parts ? implode(' · ', $parts) : 'Navegador desconocido';
    }

    protected function casts(): array
    {
        return [
            'otp_expires_at' => 'datetime',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }
}
