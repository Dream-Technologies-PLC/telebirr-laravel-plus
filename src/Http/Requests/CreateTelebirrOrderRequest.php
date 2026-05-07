<?php

namespace DreamTechnologies\TelebirrLaravelPlus\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTelebirrOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:512'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'merchantOrderId' => ['sometimes', 'string', 'max:64'],
            'notifyUrl' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'redirectUrl' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'callbackInfo' => ['sometimes', 'nullable', 'string', 'max:512'],
        ];
    }
}
