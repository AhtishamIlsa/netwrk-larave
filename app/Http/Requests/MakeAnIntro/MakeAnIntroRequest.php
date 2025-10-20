<?php

namespace App\Http\Requests\MakeAnIntro;

use Illuminate\Foundation\Http\FormRequest;

class MakeAnIntroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'introduce.firstName' => 'required|string',
            'introduce.lastName' => 'required|string',
            'introduce.email' => 'nullable|email',
            'introduce.id' => 'nullable|string',

            'from.firstName' => 'required|string',
            'from.lastName' => 'required|string',
            'from.email' => 'nullable|email',
            'from.id' => 'nullable|string',

            'to' => 'required|array|min:1',
            'to.*.firstName' => 'required|string',
            'to.*.lastName' => 'required|string',
            'to.*.email' => 'nullable|email',
            'to.*.id' => 'nullable|string',

            'message' => 'required|string',
        ];
    }
}


