<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class GetUserDashboardLocationRequest extends FormRequest
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
            'swLat' => 'required|numeric',
            'swLng' => 'required|numeric',
            'neLat' => 'required|numeric',
            'neLng' => 'required|numeric',
            'city' => 'nullable|string|max:255',
            'industries' => 'nullable|string',
            'tags' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'swLat.required' => 'South-West latitude is required',
            'swLat.numeric' => 'South-West latitude must be a number',
            'swLng.required' => 'South-West longitude is required',
            'swLng.numeric' => 'South-West longitude must be a number',
            'neLat.required' => 'North-East latitude is required',
            'neLat.numeric' => 'North-East latitude must be a number',
            'neLng.required' => 'North-East longitude is required',
            'neLng.numeric' => 'North-East longitude must be a number',
            'page.integer' => 'Page must be an integer',
            'page.min' => 'Page must be at least 1',
            'limit.integer' => 'Limit must be an integer',
            'limit.min' => 'Limit must be at least 1',
            'limit.max' => 'Limit cannot exceed 100',
        ];
    }
}