<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class CreateSecondaryProfileRequest extends FormRequest
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
            'email' => 'required|email|max:255|unique:user_profiles,email',
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'avatar' => 'nullable|string|url',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|string|url',
            'location' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'socials' => 'nullable',
            'position' => 'nullable|string|max:255',
            'companyName' => 'nullable|string|max:255',
            'industries' => 'nullable|array',
            'bio' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email is required',
            'email.email' => 'Email format is not correct',
            'email.unique' => 'Email already exists in secondary profiles',
            'firstName.required' => 'First name should not be empty',
            'lastName.required' => 'Last name should not be empty',
        ];
    }
}