<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $request_id
 * @property string $dangers_identifies
 * @property array $mesures_compensatoires
 * @property string|null $ipl_affectees
 * @property int|null $validee_par_id
 * @property \Carbon\Carbon|null $date_validation
 * @property string $statut_validation
 * @property string|null $motif_rejet
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read Request $request
 * @property-read User|null $validateurPar
 */
class Ora extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'dangers_identifies',
        'mesures_compensatoires',
        'ipl_affectees',
        'validee_par_id',
        'date_validation',
        'statut_validation',
        'motif_rejet',
    ];

    protected $casts = [
        'mesures_compensatoires' => 'array',
        'date_validation' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(Request::class);
    }

    public function validateurPar()
    {
        return $this->belongsTo(User::class, 'validee_par_id');
    }
}
