<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:255',
            'detailedJustification' => 'required|string',
            'urgencyLevel' => 'required|in:low,normal,high,critical,emergency',
            'equipmentId' => 'required|exists:equipment,id',
            'sensorId' => 'required|exists:sensors,id',
            'plannedStartDate' => 'required|after_or_equal:now',
            'estimatedDuration' => 'required|integer',
            'safetyImpact' => 'required|in:very_low,low,medium,high,very_high',
            'operationalImpact' => 'required|in:very_low,low,medium,high,very_high',
            'environmentalImpact' => 'required|in:very_low,low,medium,high,very_high',
            'mitigationMeasures' => 'required',
            'contingencyPlan' => 'nullable|string|max:2000',
            // CDC fields
            'bypassType' => 'nullable|in:maintenance,operationnel,permissif',
            'isDraft' => 'nullable|boolean',
        ];
    }
}
