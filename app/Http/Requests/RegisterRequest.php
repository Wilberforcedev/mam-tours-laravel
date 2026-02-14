<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-Z\s\-\'\.]+$/'
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                'unique:users,email',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ],
            'phone' => [
                'nullable',
                'string',
                'regex:/^\+?[1-9]\d{1,14}$/',
                'max:20'
            ],
            'terms' => 'required|accepted'
        ];
    }

    public function messages()
    {
        return [
            'name.regex' => 'Name can only contain letters, spaces, hyphens, apostrophes, and periods.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'phone.regex' => 'Please provide a valid phone number.',
            'terms.accepted' => 'You must accept the terms and conditions.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'name' => trim($this->name),
            'email' => strtolower(trim($this->email)),
            'phone' => $this->phone ? preg_replace('/[^\d+]/', '', $this->phone) : null,
            'terms' => $this->boolean('terms'),
        ]);
    }
}