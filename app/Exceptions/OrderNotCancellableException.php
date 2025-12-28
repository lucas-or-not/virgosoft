<?php

namespace App\Exceptions;

use RuntimeException;

final class OrderNotCancellableException extends RuntimeException
{
    public function __construct(string $message = 'Order cannot be cancelled (already filled or cancelled)', int $code = 422)
    {
        parent::__construct($message, $code);
    }
}

