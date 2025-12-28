<?php

namespace App\Exceptions;

use RuntimeException;

final class NoMatchingOrderException extends RuntimeException
{
    public function __construct(string $message = 'No matching order found', int $code = 200)
    {
        parent::__construct($message, $code);
    }
}

