<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateZoneRequest;
use App\Models\AuditLog;
use App\Models\Zone;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
// use Illuminate\Http\Resources\Json\JsonResource;

class ZoneController extends Controller
{
    #[OA\Get(
        path: "/zones",
        summary: "Liste des zones",
        description: "Récupère la liste des zones avec leurs équipements",
        tags: ["Zones"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "status", in: "query", description: "Filtrer par statut", schema: new OA\Schema(type: "string", enum: ["active", "inactive", "maintenance"])),
            new OA\Parameter(name: "search", in: "query", description: "Rechercher dans le nom", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "page", in: "query", description: "Numéro de page", schema: new OA\Schema(type: "integer", example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des zones paginée",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Zone")),
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié", ref: "#/components/schemas/Error"),
        ]
    )]
    public function index(Request $request)
    {
        $query = Zone::with('equipements');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $zone = $query->orderBy('name')->paginate(15);

        return response()->json($zone);
    }

    #[OA\Post(
        path: "/zones",
        summary: "Créer une zone",
        description: "Crée une nouvelle zone. Accessible uniquement aux administrateurs.",
        tags: ["Zones"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["name"],
                    properties: [
                        new OA\Property(property: "name", type: "string", example: "Zone A"),
                        new OA\Property(property: "description", type: "string", nullable: true, example: "Description de la zone"),
                        new OA\Property(property: "location", type: "string", nullable: true, example: "Bâtiment A"),
                        new OA\Property(property: "status", type: "string", enum: ["active", "inactive", "maintenance"], example: "active"),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Zone créée avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(ref: "#/components/schemas/Zone")
                )
            ),
            new OA\Response(response: 403, description: "Non autorisé (administrateur requis)", ref: "#/components/schemas/Error"),
            new OA\Response(response: 422, description: "Erreur de validation", ref: "#/components/schemas/Error"),
        ]
    )]
    public function store(CreateZoneRequest $request)
    {
        $zone = Zone::create($request->validated());

        AuditLog::log(
            'Zone Created',
            auth()->user(),
            'Zone',
            $zone->id,
            ['name' => $zone->name]
        );

        return response()->json($zone, 201);
    }

    #[OA\Get(
        path: "/zones/{zone}",
        summary: "Détails d'une zone",
        description: "Récupère les détails d'une zone avec ses équipements",
        tags: ["Zones"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "zone", in: "path", required: true, description: "ID de la zone", schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Détails de la zone",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(ref: "#/components/schemas/Zone")
                )
            ),
            new OA\Response(response: 404, description: "Zone non trouvée", ref: "#/components/schemas/Error"),
        ]
    )]
    public function show(Zone $zone)
    {
        return response()->json($zone->load('equipements'));
    }

    #[OA\Put(
        path: "/zones/{zone}",
        summary: "Mettre à jour une zone",
        description: "Met à jour les informations d'une zone. Accessible uniquement aux administrateurs.",
        tags: ["Zones"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "zone", in: "path", required: true, description: "ID de la zone", schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "name", type: "string", example: "Zone A"),
                        new OA\Property(property: "description", type: "string", nullable: true),
                        new OA\Property(property: "location", type: "string", nullable: true),
                        new OA\Property(property: "status", type: "string", enum: ["active", "inactive", "maintenance"], example: "active"),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Zone mise à jour avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(ref: "#/components/schemas/Zone")
                )
            ),
            new OA\Response(response: 403, description: "Non autorisé (administrateur requis)", ref: "#/components/schemas/Error"),
            new OA\Response(response: 404, description: "Zone non trouvée", ref: "#/components/schemas/Error"),
        ]
    )]
    public function update(Request $request, Zone $zone)
    {
        if (!auth()->user()->isAdministrator()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'location' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:active,inactive,maintenance',
        ]);

        $zone->update($request->only(['name', 'description', 'location', 'status']));

        AuditLog::log(
            'Equipment Updated',
            auth()->user(),
            'Equipment',
            $zone->id,
            ['name' => $zone->name]
        );

        return response()->json($zone);
    }

    #[OA\Delete(
        path: "/zones/{zone}",
        summary: "Supprimer une zone",
        description: "Supprime une zone. Accessible uniquement aux administrateurs.",
        tags: ["Zones"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "zone", in: "path", required: true, description: "ID de la zone", schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Zone supprimée avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "message", type: "string", example: "Zone supprimée avec succès"),
                        ]
                    )
                )
            ),
            new OA\Response(response: 403, description: "Non autorisé (administrateur requis)", ref: "#/components/schemas/Error"),
            new OA\Response(response: 404, description: "Zone non trouvée", ref: "#/components/schemas/Error"),
        ]
    )]
    public function destroy(Zone $zone)
    {
        if (!auth()->user()->isAdministrator()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        AuditLog::log(
            'Equipment Deleted',
            auth()->user(),
            'Equipment',
            $zone->id,
            ['name' => $zone->name]
        );

        $zone->delete();

        return response()->json(['message' => 'Zone supprimée avec succès']);
    }
}
