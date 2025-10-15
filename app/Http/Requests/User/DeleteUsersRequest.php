<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class DeleteUsersRequest extends FormRequest
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
            'recordIds' => 'required|array|min:1',
            'recordIds.*' => 'string|uuid|exists:users,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'recordIds.required' => 'Record IDs are required',
            'recordIds.array' => 'Record IDs must be an array',
            'recordIds.min' => 'At least one record ID is required',
            'recordIds.*.uuid' => 'Each record ID must be a valid UUID',
            'recordIds.*.exists' => 'One or more record IDs do not exist',
        ];
    }
}