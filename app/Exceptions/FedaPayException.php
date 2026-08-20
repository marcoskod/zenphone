<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class FedaPayException extends Exception
{
    public function __construct(string $message, protected int $statusCode = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
