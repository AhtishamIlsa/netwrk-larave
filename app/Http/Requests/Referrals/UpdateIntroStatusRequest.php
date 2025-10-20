<?php

namespace App\Http\Requests\Referrals;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIntroStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|string|in:pending,connected,decline',
        ];
    }
}


