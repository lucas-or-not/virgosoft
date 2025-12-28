<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;

final class OrderNotFoundException extends ModelNotFoundException
{
    public function __construct(string $message = 'Order not found', int $code = 404)
    {
        parent::__construct($message, $code);
    }
}

