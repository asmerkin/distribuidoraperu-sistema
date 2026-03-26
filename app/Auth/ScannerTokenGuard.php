<?php

namespace App\Auth;

use App\Models\ScannerDevice;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class ScannerTokenGuard implements Guard
{
    protected ?ScannerDevice $device = null;

    protected bool $resolved = false;

    public function __construct(protected Request $request) {}

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return ! $this->check();
    }

    public function user(): ?Authenticatable
    {
        if ($this->resolved) {
            return $this->device;
        }

        $this->resolved = true;

        $token = $this->request->bearerToken();

        if (! $token) {
            return null;
        }

        $hash = hash('sha256', $token);

        $this->device = ScannerDevice::where('token', $hash)
            ->where('is_active', true)
            ->first();

        if ($this->device) {
            $this->updateLastUsed();
        }

        return $this->device;
    }

    public function id(): ?string
    {
        return $this->user()?->getAuthIdentifier();
    }

    public function validate(array $credentials = []): bool
    {
        if (empty($credentials['token'])) {
            return false;
        }

        $hash = hash('sha256', $credentials['token']);

        return ScannerDevice::where('token', $hash)
            ->where('is_active', true)
            ->exists();
    }

    public function hasUser(): bool
    {
        return $this->device !== null;
    }

    public function setUser(Authenticatable $user): static
    {
        $this->device = $user;
        $this->resolved = true;

        return $this;
    }

    protected function updateLastUsed(): void
    {
        if (! $this->device->last_used_at || $this->device->last_used_at->diffInMinutes(now()) >= 1) {
            $this->device->forceFill(['last_used_at' => now()])->saveQuietly();
        }
    }
}
