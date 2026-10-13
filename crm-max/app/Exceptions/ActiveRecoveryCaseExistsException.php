<?php

namespace App\Exceptions;

use Exception;

class ActiveRecoveryCaseExistsException extends Exception
{
    public function __construct()
    {
        parent::__construct(
            'You already have an active recovery case. Please continue that case or withdraw it before opening a new one.'
        );
    }
}
