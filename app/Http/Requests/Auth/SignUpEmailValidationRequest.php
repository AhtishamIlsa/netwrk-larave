<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SignUpEmailValidationRequest extends FormRequest
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
            'email' => 'required|email|max:255|unique:users,email',
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'avatar' => 'nullable|string|url',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|string|url',
            'socials' => 'nullable|array',
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
            'email.unique' => 'User already exists with this email',
            'firstName.required' => 'First name should not be empty',
            'lastName.required' => 'Last name should not be empty',
        ];
    }
}