<?php

namespace App\Exceptions;

class ExternalServiceException extends BusinessException
{
    public function __construct(string $message = 'External service unavailable', ?\Throwable $previous = null)
    {
        parent::__construct($message, 'EXTERNAL_SERVICE_ERROR', 503, $previous);
    }
}
