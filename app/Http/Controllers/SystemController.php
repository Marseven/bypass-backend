<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SystemController extends Controller
{
    #[OA\Get(
        path: "/admin/settings",
        summary: "Récupérer les paramètres système",
        description: "Récupère tous les paramètres système. Accessible uniquement aux administrateurs.",
        tags: ["Système"],
        security: [["sanctum" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Paramètres système",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        additionalProperties: new OA\AdditionalProperties(type: "string"),
                        example: ["setting_key" => "setting_value"]
                    )
                )
            ),
            new OA\Response(response: 403, description: "Non autorisé (administrateur requis)", ref: "#/components/schemas/Error"),
        ]
    )]
    public function getSettings()
    {
        if (!auth()->user()->isAdministrator()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $settings = SystemSetting::all()->pluck('value', 'key');

        return response()->json($settings);
    }

    #[OA\Put(
        path: "/admin/settings",
        summary: "Mettre à jour les paramètres système",
        description: "Met à jour les paramètres système. Accessible uniquement aux administrateurs.",
        tags: ["Système"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["settings"],
                    properties: [
                        new OA\Property(
                            property: "settings",
                            type: "object",
                            additionalProperties: new OA\AdditionalProperties(type: "string"),
                            example: ["setting_key" => "setting_value"]
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Paramètres mis à jour avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "message", type: "string", example: "Paramètres mis à jour avec succès"),
                        ]
                    )
                )
            ),
            new OA\Response(response: 403, description: "Non autorisé (administrateur requis)", ref: "#/components/schemas/Error"),
            new OA\Response(response: 422, description: "Erreur de validation", ref: "#/components/schemas/Error"),
        ]
    )]
    public function updateSettings(Request $request)
    {
        if (!auth()->user()->isAdministrator()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($request->settings as $key => $value) {
            SystemSetting::set($key, $value);
        }

        AuditLog::log(
            'System Settings Updated',
            auth()->user(),
            null,
            null,
            ['updated_settings' => array_keys($request->settings)]
        );

        return response()->json(['message' => 'Paramètres mis à jour avec succès']);
    }

    #[OA\Get(
        path: "/history",
        summary: "Historique des actions",
        description: "Récupère l'historique des actions système (audit log). Accessible uniquement aux administrateurs.",
        tags: ["Système"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "action", in: "query", description: "Filtrer par type d'action", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "user_id", in: "query", description: "Filtrer par utilisateur", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "from_date", in: "query", description: "Date de début (format: YYYY-MM-DD)", schema: new OA\Schema(type: "string", format: "date")),
            new OA\Parameter(name: "to_date", in: "query", description: "Date de fin (format: YYYY-MM-DD)", schema: new OA\Schema(type: "string", format: "date")),
            new OA\Parameter(name: "page", in: "query", description: "Numéro de page", schema: new OA\Schema(type: "integer", example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Historique des actions paginé",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object")),
                            new OA\Property(property: "current_page", type: "integer"),
                            new OA\Property(property: "total", type: "integer"),
                        ]
                    )
                )
            ),
            new OA\Response(response: 403, description: "Non autorisé (administrateur requis)", ref: "#/components/schemas/Error"),
        ]
    )]
    public function getHistory(Request $request)
    {
        if (!auth()->user()->isAdministrator()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $query = AuditLog::with('user');

        if ($request->has('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $history = $query->orderBy('created_at', 'desc')->paginate(50);

        return response()->json($history);
    }
}
