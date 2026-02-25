<?php

namespace App\Contracts;

use App\Models\Request;
use App\Models\User;

interface RequestServiceInterface
{
    public function create(array $validatedData, User $requester): Request;

    public function submitDraft(Request $request, User $user): Request;

    public function activateApprovedBypass(Request $request, User $user): Request;

    public function closeBypass(Request $request, User $user): Request;

    public function validateRequest(Request $request, array $data, User $validator): Request;

    public function notifyUpdate(Request $request): void;
}
