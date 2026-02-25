<?php

namespace App\Exceptions;

class BusinessException extends \RuntimeException
{
    public function __construct(
        string $message,
        protected string $errorCode = 'BUSINESS_ERROR',
        protected int $httpStatus = 400,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }
}
