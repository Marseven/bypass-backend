<?php

namespace App\Exceptions;

class BypassNotFoundException extends BusinessException
{
    public function __construct(string $message = 'Bypass request not found', ?\Throwable $previous = null)
    {
        parent::__construct($message, 'BYPASS_NOT_FOUND', 404, $previous);
    }
}
