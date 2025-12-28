<?php

namespace App\Exceptions;

use RuntimeException;

final class InsufficientBalanceException extends RuntimeException
{
    public function __construct(string $message = 'Insufficient balance to place order', int $code = 422)
    {
        parent::__construct($message, $code);
    }
}

