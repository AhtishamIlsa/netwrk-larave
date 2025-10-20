<?php

namespace App\Http\Requests\Referrals;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequestStatusQuery extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|string|in:approved,rejected,pending',
        ];
    }
}


