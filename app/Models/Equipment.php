<?php

namespace App\Models;

use App\Enums\SilLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $type
 * @property string $type_systeme
 * @property string $niveau_sil
 * @property string|null $fonction_securite
 * @property string $criticite
 * @property string $fabricant
 * @property string|null $description
 * @property int $zone_id
 * @property string $status
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read Zone $zone
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Sensor> $sensors
 */
class Equipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'equipment';

    protected $fillable = [
        'code',
        'name',
        'type',
        'type_systeme',
        'niveau_sil',
        'fonction_securite',
        'criticite',
        'fabricant',
        'description',
        'zone_id',
        'status',
    ];

    public function sensors()
    {
        return $this->hasMany(Sensor::class);
    }

    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'operational');
    }

    public function zone() {
        return $this->belongsTo(Zone::class);
    }

    public function isSecurityEquipment(): bool
    {
        $sil = SilLevel::tryFrom($this->niveau_sil);
        return $sil !== null && $sil->isSecurityRelevant();
    }

    public function getBypassCriticality(): string
    {
        return $this->isSecurityEquipment() ? 'securite' : 'process';
    }
}
