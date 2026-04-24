<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

final class DomainException extends RuntimeException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
