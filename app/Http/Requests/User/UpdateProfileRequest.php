<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'firstName' => 'nullable|string|max:255',
            'lastName' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'companyName' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'location' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'bio' => 'nullable|string',
            'industries' => 'nullable|array',
            'socials' => 'nullable',
            'website' => 'nullable|string|url',
            'avatar' => 'nullable|string|url',
        ];
    }
}