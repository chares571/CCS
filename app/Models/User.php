<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'full_name',
        'username',
        'email',
        'password',
        'role',
        'is_active',
        'force_password_change',
        'profile_photo_path',
        'account_deletion_requested_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'force_password_change' => 'boolean',
            'account_deletion_requested_at' => 'datetime',
        ];
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function displayName(): string
    {
        $name = trim((string) ($this->full_name ?? ''));
        if ($name !== '') {
            return $name;
        }

        $fallback = (string) ($this->username ?? $this->email ?? 'User');
        return trim($fallback) !== '' ? $fallback : 'User';
    }

    public function initials(): string
    {
        $name = $this->displayName();
        $parts = preg_split('/\s+/', $name) ?: [];
        $letters = collect($parts)
            ->filter()
            ->take(2)
            ->map(fn ($part) => Str::upper(Str::substr((string) $part, 0, 1)))
            ->join('');

        return $letters !== '' ? $letters : 'U';
    }

    public function roleLabel(): string
    {
        $role = (string) ($this->role ?? 'user');

        return match ($role) {
            'super_admin' => 'Super Administrator',
            'admin' => 'Administrator',
            'parent' => 'Parent',
            'student' => 'Student',
            default => Str::title(str_replace('_', ' ', $role)),
        };
    }

    public function profilePhotoUrl(): ?string
    {
        $path = trim((string) ($this->profile_photo_path ?? ''));
        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, 'uploads/')) {
            return asset($path);
        }

        return asset('storage/'.ltrim($path, '/'));
    }
}
