<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'full_name',
        'role',
        'phone',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }


    public function submittedRequests()
    {
        return $this->hasMany(Request::class, 'requester_id');
    }

    public function validatedRequests()
    {
        return $this->hasMany(Request::class, 'validated_by_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Vérifie si l'utilisateur est administrateur (via Spatie ou champ role)
     */
    public function isAdministrator(): bool
    {
        return $this->hasRole('administrator') || $this->role === 'administrator';
    }

    /**
     * Vérifie si l'utilisateur est superviseur (via Spatie ou champ role)
     */
    public function isSupervisor(): bool
    {
        return $this->hasRole('supervisor') || $this->role === 'supervisor';
    }

    /**
     * Vérifie si l'utilisateur est directeur (via Spatie ou champ role)
     */
    public function isDirector(): bool
    {
        return $this->hasRole('director') || $this->role === 'director';
    }

    /**
     * Vérifie si l'utilisateur peut valider des demandes
     */
    public function canValidateRequests(): bool
    {
        return $this->hasAnyPermission(['requests.validate.level1', 'requests.validate.level2']) 
            || $this->hasAnyRole(['supervisor', 'administrator', 'director'])
            || in_array($this->role, ['supervisor', 'administrator', 'director']);
    }

    /**
     * Vérifie si l'utilisateur peut valider niveau 1
     */
    public function canValidateLevel1(): bool
    {
        return $this->hasPermissionTo('requests.validate.level1') 
            || $this->hasAnyRole(['supervisor', 'administrator', 'director'])
            || in_array($this->role, ['supervisor', 'administrator', 'director']);
    }

    /**
     * Vérifie si l'utilisateur peut valider niveau 2
     */
    public function canValidateLevel2(): bool
    {
        return $this->hasPermissionTo('requests.validate.level2') 
            || $this->hasAnyRole(['administrator', 'director'])
            || in_array($this->role, ['administrator', 'director']);
    }
}
