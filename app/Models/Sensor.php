<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $equipment_id
 * @property string $code
 * @property string $name
 * @property string $type
 * @property string $unite
 * @property string $seuil_critique
 * @property string $Dernier_Etallonnage
 * @property string $status
 * @property float|null $last_reading
 * @property \Carbon\Carbon|null $last_reading_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read Equipment $equipment
 */
class Sensor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'equipment_id',
        'name',
        'type',
        'unite',
        'seuil_critique',
        'code',
        'Dernier_Etallonnage',
        'status',
        'last_reading',
        'last_reading_at',
    ];

    protected $casts = [
        'last_reading' => 'decimal:2',
        'last_reading_at' => 'datetime',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    public function scopeOnline($query)
    {
        return $query->where('status', 'active');
    }
}
