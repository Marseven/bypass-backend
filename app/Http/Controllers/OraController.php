<?php

namespace App\Http\Controllers;

use App\Http\Resources\OraResource;
use App\Models\AuditLog;
use App\Models\Ora;
use App\Models\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;

class OraController extends Controller
{
    public function store(HttpRequest $httpRequest, Request $request): JsonResponse
    {
        $validated = $httpRequest->validate([
            'dangers_identifies' => 'required|string',
            'mesures_compensatoires' => 'required|array|min:1',
            'mesures_compensatoires.*' => 'string',
            'ipl_affectees' => 'nullable|string',
        ]);

        if ($request->ora) {
            return response()->json(['message' => 'ORA déjà créée pour cette demande'], 422);
        }

        $ora = Ora::create([
            'request_id' => $request->id,
            'dangers_identifies' => $validated['dangers_identifies'],
            'mesures_compensatoires' => $validated['mesures_compensatoires'],
            'ipl_affectees' => $validated['ipl_affectees'] ?? null,
        ]);

        AuditLog::log('ORA Created', auth()->user(), 'Ora', $ora->id, ['request_id' => $request->id]);

        return (new OraResource($ora))->response()->setStatusCode(201);
    }

    public function show(Request $request): OraResource|JsonResponse
    {
        $ora = $request->ora;

        if (!$ora) {
            return response()->json(['message' => 'Aucune ORA pour cette demande'], 404);
        }

        return new OraResource($ora->load('validateurPar'));
    }

    public function validate(HttpRequest $httpRequest, Ora $ora): OraResource|JsonResponse
    {
        $user = auth()->user();

        if (!$user->isResponsableHse() && !$user->isAdministrator()) {
            return response()->json(['message' => 'Non autorisé : rôle responsable_hse requis'], 403);
        }

        $validated = $httpRequest->validate([
            'statut_validation' => 'required|in:approved,rejected',
            'motif_rejet' => 'required_if:statut_validation,rejected|nullable|string',
        ]);

        $ora->update([
            'validee_par_id' => $user->id,
            'date_validation' => now(),
            'statut_validation' => $validated['statut_validation'],
            'motif_rejet' => $validated['statut_validation'] === 'rejected'
                ? $validated['motif_rejet']
                : null,
        ]);

        AuditLog::log(
            'ORA ' . ucfirst($validated['statut_validation']),
            $user,
            'Ora',
            $ora->id,
            ['request_id' => $ora->request_id, 'motif_rejet' => $validated['motif_rejet'] ?? null]
        );

        return new OraResource($ora->load('validateurPar'));
    }
}
