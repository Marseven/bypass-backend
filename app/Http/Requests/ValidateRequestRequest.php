<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = auth()->user();
        return $user->hasAnyPermission(['requests.validate.level1', 'requests.validate.level2']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'validation_status' => 'required|in:approved,rejected',
            'rejection_reason' => 'nullable|required_if:validation_status,rejected|string|max:1000',
        ];
    }
}
