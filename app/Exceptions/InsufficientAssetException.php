<?php

namespace App\Exceptions;

use RuntimeException;

final class InsufficientAssetException extends RuntimeException
{
    public function __construct(string $message = 'Insufficient asset amount to place sell order', int $code = 422)
    {
        parent::__construct($message, $code);
    }
}

