<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DomainName implements ValidationRule
{

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $pattern = '/^(?!https?:\/\/)(?!.*\/)([A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?\.)+[A-Za-z]{2,}$/';

        if (! is_string($value) || ! preg_match($pattern, $value)) {
            $fail(sprintf(
                'The %s must be a valid domain (e.g. example.com) without http:// or https://.',
                $attribute
            ));
        }
    }
}
