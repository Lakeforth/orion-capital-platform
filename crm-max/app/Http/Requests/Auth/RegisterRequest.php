<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'nullable|max:50|min:3',
            'last_name' => 'nullable|max:50|min:3',
            'phone' => 'required|min:10|unique:users',
            'country_code' => 'required',
            'country' => 'required',
            'password' => 'required',
            'email' => [
                'required',
                'max:50',
                'unique:users',
                $this->emailValidationRule(),
            ],
            'username' => 'required|max:50|unique:users|string|regex:/^\S*$/',
            'referral' => 'nullable',
            'campaign' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
        ];
    }

    protected function emailValidationRule(): string
    {
        return app()->environment(['local', 'testing'])
            ? 'email:rfc'
            : 'email:rfc,dns';
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'The username field must not contain spaces.',
        ];
    }
}
