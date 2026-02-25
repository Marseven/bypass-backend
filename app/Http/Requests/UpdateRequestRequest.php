<?php

namespace App\Http\Requests;

use App\Enums\RequestStatus;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $request = $this->route('request');

        return $user->hasPermissionTo('requests.view.all') || $request->requester_id === $user->id;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'priority' => 'sometimes|in:low,normal,high,critical,emergency',
            'equipment_id' => 'sometimes|nullable|exists:equipment,id',
            'sensor_id' => 'sometimes|nullable|exists:sensors,id',
            'start_time' => 'sometimes|nullable|date|after_or_equal:now',
            'end_time' => 'sometimes|nullable|date|after:start_time',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $request = $this->route('request');
            if ($request && $request->status !== RequestStatus::Pending->value) {
                $validator->errors()->add('status', 'Impossible de modifier une demande déjà traitée');
            }
        });
    }
}
