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
            'merchantOrderId' => config('telebirr.allow_client_merchant_order_id', false)
                ? ['sometimes', 'string', 'max:64', 'regex:/^[A-Za-z0-9_]+$/']
                : ['prohibited'],
            'notifyUrl' => config('telebirr.allow_client_notify_url', false)
                ? ['sometimes', 'nullable', 'url', 'max:2048']
                : ['prohibited'],
            'redirectUrl' => config('telebirr.allow_client_redirect_url', false)
                ? ['sometimes', 'nullable', 'url', 'max:2048']
                : ['prohibited'],
            'callbackInfo' => config('telebirr.allow_client_callback_info', false)
                ? ['sometimes', 'nullable', 'string', 'max:512']
                : ['prohibited'],
        ];
    }
}
