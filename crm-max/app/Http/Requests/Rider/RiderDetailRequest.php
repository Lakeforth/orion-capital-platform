<?php

namespace App\Http\Requests\Rider;

use Illuminate\Foundation\Http\FormRequest;

class RiderDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'next_of_kin' => 'required|string|max:255',
            'next_of_kin_phone' => 'required|string|max:20',
            'next_of_kin_relationship' => 'required|string|max:255',
            'guarantor_1_name' => 'required|string|max:255',
            'guarantor_1_phone' => 'required|string|max:20',
            'guarantor_1_relationship' => 'required|string|max:255',
            'guarantor_2_name' => 'required|string|max:255',
            'guarantor_2_phone' => 'required|string|max:20',
            'guarantor_2_relationship' => 'required|string|max:255',
            'experience' => 'required|string|max:500',
        ];
    }
}
