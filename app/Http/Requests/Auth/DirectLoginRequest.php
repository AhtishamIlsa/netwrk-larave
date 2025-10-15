<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class DirectLoginRequest extends FormRequest
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
            'email' => 'required|email|max:255',
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'groupId' => 'nullable|string|exists:groups,id',
            'sharedContactId' => 'nullable|string|exists:contacts,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email must be a valid email.',
            'email.email' => 'Email must be a valid email.',
            'firstName.required' => 'First name is required',
            'lastName.required' => 'Last name is required',
        ];
    }
}