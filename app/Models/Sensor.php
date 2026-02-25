<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sensor extends Model
{
    use HasFactory;

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
