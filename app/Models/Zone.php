<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $site_id
 * @property string $name
 * @property string|null $description
 * @property string|null $status
 * @property string|null $location
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read Site|null $site
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Equipment> $equipements
 */
class Zone extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'name',
        'description',
        'status',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function equipements() {
        return $this->hasMany(Equipment::class);
    }
}
