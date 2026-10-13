<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CheckPhoneRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'phone' => 'required|numeric|min:10',
            'sms_type' => 'required',
            'country_code' => 'nullable',
        ];
    }

}
