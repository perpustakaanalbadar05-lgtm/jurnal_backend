<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'is_active',
        'institution', 'phone', 'bio', 'avatar_path',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // Roles
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN = 'admin';
    const ROLE_REVIEWER = 'reviewer';
    const ROLE_AUTHOR = 'author';

    public function isSuperAdmin(): bool { return $this->role === self::ROLE_SUPER_ADMIN; }
    public function isAdmin(): bool { return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN]); }
    public function isReviewer(): bool { return $this->role === self::ROLE_REVIEWER; }
    public function isAuthor(): bool { return $this->role === self::ROLE_AUTHOR; }

    // Relationships
    public function papers()
    {
        return $this->hasMany(Paper::class, 'author_id');
    }

    public function assignedPapers()
    {
        return $this->hasMany(Paper::class, 'assigned_reviewer_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }
}
