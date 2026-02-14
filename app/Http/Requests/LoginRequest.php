<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class LoginRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:255'
            ],
            'remember' => 'boolean'
        ];
    }

    public function messages()
    {
        return [
            'email.email' => 'Please provide a valid email address.',
            'email.regex' => 'Email format is invalid.',
            'password.min' => 'Password must be at least 8 characters long.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'email' => strtolower(trim($this->email)),
            'remember' => $this->boolean('remember'),
        ]);
    }
}