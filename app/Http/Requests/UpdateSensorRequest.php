<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSensorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdministrator();
    }

    public function rules(): array
    {
        return [
            'last_reading' => 'sometimes|nullable|numeric',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'unit' => 'required|string|max:255',
            'criticalThreshold' => 'required|string|max:255',
            'Dernier_Etallonage' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:active,bypassed,maintenance,faulty,calibration',
        ];
    }
}
