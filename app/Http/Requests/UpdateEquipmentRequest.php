<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdministrator();
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:operational,maintenance,down,standby',
            'type' => 'sometimes|string|max:255',
            'criticite' => 'sometimes|string|max:255',
            'fabricant' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'zone' => 'sometimes|string|max:255',
        ];
    }
}
