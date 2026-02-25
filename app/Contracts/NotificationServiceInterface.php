<?php

namespace App\Contracts;

use App\Models\Request;

interface NotificationServiceInterface
{
    public function notifyRequestCreated(Request $request): void;

    public function notifyValidationResult(Request $request, string $status, ?string $rejectionReason = null, ?int $validationLevel = null): void;

    public function notifyLevel1Approved(Request $request): void;

    public function notifyRequestUpdated(Request $request): void;
}
