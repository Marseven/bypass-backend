<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    use HasFactory;

    protected $table = 'equipment';

    protected $fillable = [
        'code',
        'name',
        'type',
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
}
