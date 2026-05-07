<?php

namespace DreamTechnologies\TelebirrLaravelPlus\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QueryTelebirrOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'merchantOrderId' => ['required', 'string', 'max:64'],
        ];
    }
}
