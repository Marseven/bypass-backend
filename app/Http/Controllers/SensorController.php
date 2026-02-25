<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSensorRequest;
use App\Http\Resources\SensorResource;
use App\Models\Sensor;
use App\Models\Equipment;
use App\Models\AuditLog;
use App\Services\CodeGenerationService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SensorController extends Controller
{
    public function __construct(
        private CodeGenerationService $codeService,
    ) {}
    #[OA\Get(
        path: "/equipment/{equipment}/sensors",
        summary: "Liste des capteurs d'un équipement",
        description: "Récupère tous les capteurs associés à un équipement spécifique",
        tags: ["Capteurs"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "equipment", in: "path", required: true, description: "ID de l'équipement", schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des capteurs",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/Sensor")
                    )
                )
            ),
            new OA\Response(response: 404, description: "Équipement non trouvé", ref: "#/components/schemas/Error"),
        ]
    )]
    public function index(Equipment $equipment)
    {
        return SensorResource::collection($equipment->sensors);
    }


    #[OA\Post(
        path: "/equipment/{equipment}/sensors",
        summary: "Créer un capteur",
        description: "Crée un nouveau capteur pour un équipement. Accessible uniquement aux administrateurs.",
        tags: ["Capteurs"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "equipment", in: "path", required: true, description: "ID de l'équipement", schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["name", "type", "unit", "criticalThreshold"],
                    properties: [
                        new OA\Property(property: "name", type: "string", example: "Capteur Température"),
                        new OA\Property(property: "type", type: "string", example: "Température"),
                        new OA\Property(property: "unit", type: "string", example: "°C"),
                        new OA\Property(property: "criticalThreshold", type: "string", example: "50"),
                        new OA\Property(property: "status", type: "string", enum: ["active", "bypassed", "maintenance", "faulty", "calibration"], example: "active"),
                        new OA\Property(property: "last_reading", type: "number", nullable: true, example: 25.5),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Capteur créé avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(ref: "#/components/schemas/Sensor")
                )
            ),
            new OA\Response(response: 403, description: "Non autorisé (administrateur requis)", ref: "#/components/schemas/Error"),
            new OA\Response(response: 422, description: "Erreur de validation", ref: "#/components/schemas/Error"),
        ]
    )]
    public function store(Request $request, Equipment $equipment)
    {
        if (!auth()->user()->isAdministrator()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $equipment->load('zone');
        $zone = $equipment->zone;

        if (!$zone) {
            return response()->json(['message' => 'Zone non trouvée pour cet équipement'], 404);
        }

        $request->validate([
            'last_reading' => 'sometimes|nullable|numeric',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'unit' => 'required|string|max:255',
            'criticalThreshold' => 'required|string|max:255',
            'Dernier_Etallonage' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:active,bypassed,maintenance,faulty,calibration',
        ]);

        $sensor = $equipment->sensors()->create([
            'name' => $request->name,
            'code' => $this->codeService->generateSensorCode($request->name, $equipment->name, $zone->name),
            'type' => $request->type,
            'equipment_id' => $equipment->id,
            'seuil_critique' => $request->criticalThreshold,
            'unite' => $request->unit,
            'Dernier_Etallonnage' => now(),
            'status' => $request->status ?? 'active',
            'last_reading_at' => now(),
        ]);

        AuditLog::log(
            'Sensor Created',
            auth()->user(),
            'Sensor',
            $sensor->id,
            ['name' => $sensor->name, 'equipment' => $equipment->name]
        );

        return (new SensorResource($sensor))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: "/sensors/{sensor}",
        summary: "Détails d'un capteur",
        description: "Récupère les détails d'un capteur spécifique avec son équipement",
        tags: ["Capteurs"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "sensor", in: "path", required: true, description: "ID du capteur", schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Détails du capteur",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(ref: "#/components/schemas/Sensor")
                )
            ),
            new OA\Response(response: 404, description: "Capteur non trouvé", ref: "#/components/schemas/Error"),
        ]
    )]
    public function show(Sensor $sensor)
    {
        return new SensorResource($sensor->load(['equipment', 'requests.requester']));
    }

    #[OA\Get(
        path: "/sensors",
        summary: "Liste de tous les capteurs",
        description: "Récupère la liste de tous les capteurs avec leurs équipements et zones",
        tags: ["Capteurs"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "page", in: "query", description: "Numéro de page", schema: new OA\Schema(type: "integer", example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des capteurs paginée",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Sensor")),
                        ]
                    )
                )
            ),
        ]
    )]
    public function showSensor()
    {
        return SensorResource::collection(Sensor::with('equipment.zone')->paginate(15));
    }

    #[OA\Put(
        path: "/sensors/{sensor}",
        summary: "Mettre à jour un capteur",
        description: "Met à jour les informations d'un capteur. Accessible uniquement aux administrateurs.",
        tags: ["Capteurs"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "sensor", in: "path", required: true, description: "ID du capteur", schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "name", type: "string", example: "Capteur Température"),
                        new OA\Property(property: "type", type: "string", example: "Température"),
                        new OA\Property(property: "unit", type: "string", example: "°C"),
                        new OA\Property(property: "criticalThreshold", type: "string", example: "50"),
                        new OA\Property(property: "status", type: "string", enum: ["active", "bypassed", "maintenance", "faulty", "calibration"], example: "active"),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Capteur mis à jour avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(ref: "#/components/schemas/Sensor")
                )
            ),
            new OA\Response(response: 403, description: "Non autorisé (administrateur requis)", ref: "#/components/schemas/Error"),
            new OA\Response(response: 404, description: "Capteur non trouvé", ref: "#/components/schemas/Error"),
        ]
    )]
    public function update(UpdateSensorRequest $request, Sensor $sensor)
    {
        $sensor->update([
            'name' => $request->name,
            'type' => $request->type,
            'equipment_id' => $request->equipmentId,
            'seuil_critique' => $request->criticalThreshold,
            'unite' => $request->unit,
            'Dernier_Etallonnage' => now(),
            'status' => $request->status,
            'last_reading_at' => now()
        ]);

        AuditLog::log(
            'Sensor Updated',
            auth()->user(),
            'Sensor',
            $sensor->id,
            ['name' => $sensor->name]
        );

        return new SensorResource($sensor);
    }

    #[OA\Delete(
        path: "/sensors/{sensor}",
        summary: "Supprimer un capteur",
        description: "Supprime un capteur. Accessible uniquement aux administrateurs.",
        tags: ["Capteurs"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "sensor", in: "path", required: true, description: "ID du capteur", schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Capteur supprimé avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "message", type: "string", example: "Capteur supprimé avec succès"),
                        ]
                    )
                )
            ),
            new OA\Response(response: 403, description: "Non autorisé (administrateur requis)", ref: "#/components/schemas/Error"),
            new OA\Response(response: 404, description: "Capteur non trouvé", ref: "#/components/schemas/Error"),
        ]
    )]
    public function destroy(Sensor $sensor)
    {
        if (!auth()->user()->isAdministrator()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        AuditLog::log(
            'Sensor Deleted',
            auth()->user(),
            'Sensor',
            $sensor->id,
            ['name' => $sensor->name]
        );

        $sensor->delete();

        return response()->json(['message' => 'Capteur supprimé avec succès']);
    }
}
