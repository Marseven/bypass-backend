<?php

namespace App\Exceptions;

class InvalidWorkflowStateException extends BusinessException
{
    public function __construct(string $message = 'Invalid workflow state', ?\Throwable $previous = null)
    {
        parent::__construct($message, 'INVALID_WORKFLOW_STATE', 422, $previous);
    }
}
