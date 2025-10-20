<?php

namespace App\Http\Requests\Referrals;

use Illuminate\Foundation\Http\FormRequest;

class SendReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => 'required|string',
        ];
    }
}


