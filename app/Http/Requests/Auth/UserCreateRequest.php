<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UserCreateRequest extends FormRequest
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
            'password' => 'required|string|min:8|max:20|regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>-]).*$/',
            'confirmPassword' => 'required|string|same:password',
            'email' => 'required|email|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'password.required' => 'Password should not be empty',
            'password.min' => 'Password must be between 8 and 20 characters long',
            'password.max' => 'Password must be between 8 and 20 characters long',
            'password.regex' => 'Password must contain at least one uppercase letter, one number, and one special character',
            'confirmPassword.required' => 'Confirm password is required',
            'confirmPassword.same' => 'Passwords do not match',
            'email.required' => 'Email is required',
            'email.email' => 'Email format is not correct',
        ];
    }
}