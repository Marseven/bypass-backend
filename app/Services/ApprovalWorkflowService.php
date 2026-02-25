<?php

namespace App\Services;

use App\Models\Request;
use App\Models\RequestApproval;
use App\Models\User;

class ApprovalWorkflowService
{
    /**
     * CDC approval matrix:
     * Court terme + Process   → [chef_de_quart]
     * Court terme + Sécurité  → [chef_de_quart, responsable_hse]
     * Long terme  + Process   → [resp_exploitation]
     * Long terme  + Sécurité  → [resp_exploitation, directeur, responsable_hse]
     */
    public function getRequiredApprovals(Request $request): array
    {
        $duree = $request->duree_type ?? 'court_terme';
        $criticite = $request->criticite ?? 'process';

        if ($duree === 'court_terme' && $criticite === 'process') {
            return [
                ['role' => User::ROLE_CHEF_DE_QUART, 'level' => 1],
            ];
        }

        if ($duree === 'court_terme' && $criticite === 'securite') {
            return [
                ['role' => User::ROLE_CHEF_DE_QUART, 'level' => 1],
                ['role' => User::ROLE_RESPONSABLE_HSE, 'level' => 2],
            ];
        }

        if ($duree === 'long_terme' && $criticite === 'process') {
            return [
                ['role' => User::ROLE_RESP_EXPLOITATION, 'level' => 1],
            ];
        }

        // long_terme + securite
        return [
            ['role' => User::ROLE_RESP_EXPLOITATION, 'level' => 1],
            ['role' => User::ROLE_DIRECTEUR, 'level' => 2],
            ['role' => User::ROLE_RESPONSABLE_HSE, 'level' => 3],
        ];
    }

    public function createApprovalSteps(Request $request): void
    {
        $steps = $this->getRequiredApprovals($request);

        foreach ($steps as $step) {
            RequestApproval::create([
                'request_id' => $request->id,
                'required_role' => $step['role'],
                'level' => $step['level'],
                'status' => 'pending',
            ]);
        }
    }

    public function requiresOra(Request $request): bool
    {
        return $request->criticite === 'securite';
    }

    public function requiresMoc(Request $request): bool
    {
        return $request->duree_type === 'long_terme' && $request->criticite === 'securite';
    }

    public function canUserApprove(User $user, RequestApproval $approval): bool
    {
        return $user->isRole($approval->required_role)
            || $user->hasRole($approval->required_role)
            || $user->isAdministrateur();
    }
}
