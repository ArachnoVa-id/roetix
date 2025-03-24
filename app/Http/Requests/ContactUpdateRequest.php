<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone_number' => ['string', 'max:20', 'regex:/^\+?[0-9\s\-]{8,20}$/'],
            'email' => ['string', 'email', 'max:255', 'unique:users,email'],
            'whatsapp_number' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s\-]{8,20}$/'],
            'instagram' => ['nullable', 'string', 'max:30', 'regex:/^(?!.*\.\.)(?!.*\.$)[^\W][\w.]{0,29}$/'],

        ];
    }
}
