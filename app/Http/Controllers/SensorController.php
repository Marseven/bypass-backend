<?php

namespace App\Http\Controllers;

use App\Models\Sensor;
use App\Models\Equipment;
use App\Models\AuditLog;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class SensorController extends Controller
{
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
        return response()->json($equipment->sensors);
    }

    public function sensorCode($sensor, $equipment, $zone, $num = 1) {
        // 5 premiers caractères capteur (sans "SENSOR")
        $prefix = substr(str_replace(['SENSOR', 'CAPTEUR'], '', 
            strtoupper(preg_replace('/[^A-Z0-9]/', '', $sensor))), 0, 5);
        
        // 2 lettres équipement  
        $eqWords = explode(' ', strtoupper($equipment));
        $eqCode = substr($eqWords[0], 0, 1) . substr($eqWords[1] ?? $eqWords[0], 0, 1);
        
        // 1 lettre zone
        preg_match('/Zone\s+([A-Z])/i', $zone, $m);
        $zoneCode = $m[1] ?? substr($zone, 0, 1);
        
        // 2 derniers caractères capteur
        $suffix = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($sensor)), -2);
        
        return sprintf('%s-%s-%s-%03d-%s', $prefix, $eqCode, $zoneCode, $num, $suffix);
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

        // Charger la zone depuis la relation de l'équipement
        $equipment->load('zone');
        $zonei = $equipment->zone;
        
        if (!$zonei) {
            return response()->json(['message' => 'Zone non trouvée pour cet équipement'], 404);
        }
        
        $nms = Sensor::all()->count() + 1;

        $request->validate([
            'last_reading' => 'sometimes|nullable|numeric',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'unit' => 'required|string|max:255',
            'criticalThreshold' => 'required|string|max:255',
            'Dernier_Etallonage' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:active,bypassed,maintenance,faulty,calibration',
        ]);

        try {
            // Vérifier que l'équipement a un nom
            if (!$equipment->name) {
                Log::error('Equipment name is missing', ['equipment_id' => $equipment->id]);
                return response()->json(['message' => 'L\'équipement n\'a pas de nom'], 400);
            }

            // Vérifier que la zone a un nom
            if (!$zonei->name) {
                Log::error('Zone name is missing', ['zone_id' => $zonei->id, 'equipment_id' => $equipment->id]);
                return response()->json(['message' => 'La zone n\'a pas de nom'], 400);
            }

            // Valeur par défaut pour le statut
            $status = $request->status ?? 'active';

            // Log des données avant création
            Log::info('Creating sensor', [
                'name' => $request->name,
                'type' => $request->type,
                'unit' => $request->unit,
                'criticalThreshold' => $request->criticalThreshold,
                'status' => $status,
                'equipment_id' => $equipment->id,
                'equipment_name' => $equipment->name,
                'zone_name' => $zonei->name
            ]);

            $sensorCode = $this->sensorCode($request->name, $equipment->name, $zonei->name, $nms);
            
            Log::info('Generated sensor code', ['code' => $sensorCode]);

            $sensor = $equipment->sensors()->create([
               'name' => $request->name,
                'code' => $sensorCode,
                'type' => $request->type,
                'equipment_id' => $equipment->id,
                'seuil_critique' => $request->criticalThreshold,
                'unite' => $request->unit,
                'Dernier_Etallonnage' => now(),
                'status' => $status,
                'last_reading_at' => now()
            ]);

            AuditLog::log(
                'Sensor Created',
                auth()->user(),
                'Sensor',
                $sensor->id,
                ['name' => $sensor->name, 'equipment' => $equipment->name]
            );

            return response()->json($sensor, 201);
        } catch (\Exception $e) {
            Log::error('Error creating sensor: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'message' => 'Erreur lors de la création du capteur',
                'error' => $e->getMessage()
            ], 500);
        }
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
        return response()->json($sensor->load('equipment'));
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
        return response()->json(Sensor::with('equipment.zone')->paginate(15));
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
    public function update(Request $request, Sensor $sensor)
    {
        if (!auth()->user()->isAdministrator()) {
            return response()->json(['message' => 'Non autorisé'], 403);
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

        if ($request->has('last_reading')) {
            $request->merge(['last_reading_at' => now()]);
        }

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

        return response()->json($sensor);
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
