<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EthereumAddress implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^0x[a-fA-F0-9]{40}$/', $value)) {
            $fail('The :attribute must be a valid Ethereum wallet address.');
        }
    }
}
