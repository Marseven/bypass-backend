<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $username
 * @property string $email
 * @property \Carbon\Carbon|null $email_verified_at
 * @property string $password
 * @property string $full_name
 * @property string $role
 * @property string|null $phone
 * @property bool $is_active
 * @property string|null $remember_token
 * @property string|null $two_fa_secret
 * @property bool $two_fa_enabled
 * @property string|null $two_fa_backup_codes
 * @property \Carbon\Carbon|null $two_fa_verified_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    // CDC roles
    public const ROLE_OPERATEUR = 'operateur';
    public const ROLE_TECHNICIEN = 'technicien';
    public const ROLE_INSTRUMENTISTE = 'instrumentiste';
    public const ROLE_CHEF_DE_QUART = 'chef_de_quart';
    public const ROLE_RESPONSABLE_HSE = 'responsable_hse';
    public const ROLE_RESP_EXPLOITATION = 'resp_exploitation';
    public const ROLE_DIRECTEUR = 'directeur';
    public const ROLE_ADMINISTRATEUR = 'administrateur';

    public const CDC_ROLES = [
        self::ROLE_OPERATEUR,
        self::ROLE_TECHNICIEN,
        self::ROLE_INSTRUMENTISTE,
        self::ROLE_CHEF_DE_QUART,
        self::ROLE_RESPONSABLE_HSE,
        self::ROLE_RESP_EXPLOITATION,
        self::ROLE_DIRECTEUR,
        self::ROLE_ADMINISTRATEUR,
    ];

    protected $fillable = [
        'username',
        'email',
        'password',
        'full_name',
        'role',
        'phone',
        'is_active',
        'two_fa_secret',
        'two_fa_enabled',
        'two_fa_backup_codes',
        'two_fa_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_fa_secret',
        'two_fa_backup_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_fa_enabled' => 'boolean',
            'two_fa_verified_at' => 'datetime',
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

    // ── Role checks ──────────────────────────────────────────────

    public function isRole(string $role): bool
    {
        return $this->role === $role || $this->hasRole($role);
    }

    public function isAdministrateur(): bool
    {
        return $this->isRole(self::ROLE_ADMINISTRATEUR);
    }

    // Legacy alias
    public function isAdministrator(): bool
    {
        return $this->isAdministrateur()
            || $this->role === 'administrator'
            || $this->hasRole('administrator');
    }

    public function isSupervisor(): bool
    {
        return $this->isRole(self::ROLE_CHEF_DE_QUART)
            || $this->role === 'supervisor'
            || $this->hasRole('supervisor');
    }

    public function isDirector(): bool
    {
        return $this->isRole(self::ROLE_DIRECTEUR)
            || $this->role === 'director'
            || $this->hasRole('director');
    }

    public function isResponsableHse(): bool
    {
        return $this->isRole(self::ROLE_RESPONSABLE_HSE);
    }

    public function isRespExploitation(): bool
    {
        return $this->isRole(self::ROLE_RESP_EXPLOITATION);
    }

    public function isInstrumentiste(): bool
    {
        return $this->isRole(self::ROLE_INSTRUMENTISTE);
    }

    public function isTechnicien(): bool
    {
        return $this->isRole(self::ROLE_TECHNICIEN);
    }

    // ── Validation permissions ───────────────────────────────────

    public function canValidateRequests(): bool
    {
        return $this->hasAnyPermission(['requests.validate.level1', 'requests.validate.level2'])
            || $this->hasAnyRole([
                self::ROLE_CHEF_DE_QUART, self::ROLE_RESPONSABLE_HSE,
                self::ROLE_RESP_EXPLOITATION, self::ROLE_DIRECTEUR, self::ROLE_ADMINISTRATEUR,
                'supervisor', 'administrator', 'director', // legacy
            ])
            || in_array($this->role, [
                self::ROLE_CHEF_DE_QUART, self::ROLE_RESPONSABLE_HSE,
                self::ROLE_RESP_EXPLOITATION, self::ROLE_DIRECTEUR, self::ROLE_ADMINISTRATEUR,
                'supervisor', 'administrator', 'director', // legacy
            ]);
    }

    public function canValidateLevel1(): bool
    {
        return $this->hasPermissionTo('requests.validate.level1')
            || $this->hasAnyRole([
                self::ROLE_CHEF_DE_QUART, self::ROLE_RESP_EXPLOITATION,
                self::ROLE_ADMINISTRATEUR,
                'supervisor', 'administrator', 'director',
            ])
            || in_array($this->role, [
                self::ROLE_CHEF_DE_QUART, self::ROLE_RESP_EXPLOITATION,
                self::ROLE_ADMINISTRATEUR,
                'supervisor', 'administrator', 'director',
            ]);
    }

    public function canValidateLevel2(): bool
    {
        return $this->hasPermissionTo('requests.validate.level2')
            || $this->hasAnyRole([
                self::ROLE_DIRECTEUR, self::ROLE_ADMINISTRATEUR,
                'administrator', 'director',
            ])
            || in_array($this->role, [
                self::ROLE_DIRECTEUR, self::ROLE_ADMINISTRATEUR,
                'administrator', 'director',
            ]);
    }
}
