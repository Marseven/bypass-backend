<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Http\Resources\EquipmentResource;
use App\Models\Equipment;
use App\Models\AuditLog;
use App\Models\Zone;
use App\Services\CodeGenerationService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class EquipmentController extends Controller
{
    public function __construct(
        private CodeGenerationService $codeService,
    ) {}

    public function index_equipements(Zone $zone)
    {
        return response()->json($zone->equipements);
    }

    #[OA\Get(
        path: "/equipment",
        summary: "Liste des équipements",
        description: "Récupère la liste des équipements avec filtres optionnels",
        tags: ["Équipements"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "status", in: "query", description: "Filtrer par statut", schema: new OA\Schema(type: "string", enum: ["operational", "maintenance", "down", "standby"])),
            new OA\Parameter(name: "search", in: "query", description: "Rechercher dans nom ou localisation", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "page", in: "query", description: "Numéro de page", schema: new OA\Schema(type: "integer", example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des équipements paginée",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        type: "object",
                        properties: [
                            new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Equipment")),
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié", ref: "#/components/schemas/Error"),
        ]
    )]
    public function index(Request $request)
    {
        $query = Equipment::with('sensors', 'zone');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $equipment = $query->orderBy('name')->paginate(15);

        return EquipmentResource::collection($equipment);
    }

    #[OA\Post(
        path: "/equipment",
        summary: "Créer un équipement",
        description: "Crée un nouvel équipement. Accessible uniquement aux administrateurs.",
        tags: ["Équipements"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["name", "type", "criticite", "fabricant", "zone"],
                    properties: [
                        new OA\Property(property: "name", type: "string", example: "Équipement 1"),
                        new OA\Property(property: "type", type: "string", example: "Capteur de température"),
                        new OA\Property(property: "criticite", type: "string", example: "Haute"),
                        new OA\Property(property: "fabricant", type: "string", example: "Fabricant XYZ"),
                        new OA\Property(property: "description", type: "string", nullable: true, example: "Description de l'équipement"),
                        new OA\Property(property: "zone", type: "string", example: "Zone A"),
                        new OA\Property(property: "status", type: "string", enum: ["operational", "maintenance", "down", "standby"], example: "operational"),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Équipement créé avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(ref: "#/components/schemas/Equipment")
                )
            ),
            new OA\Response(response: 403, description: "Non autorisé (administrateur requis)", ref: "#/components/schemas/Error"),
            new OA\Response(response: 422, description: "Erreur de validation", ref: "#/components/schemas/Error"),
        ]
    )]
    public function store(CreateEquipmentRequest $request)
    {
        $zonei = Zone::where('name', $request->zone)->first();

        $equipment = Equipment::create([
            'name' => $request->name,
            'code' => $this->codeService->generateEquipmentCode($request->name, $zonei->name),
            'type' => $request->type,
            'type_systeme' => $request->type_systeme ?? 'process',
            'niveau_sil' => $request->niveau_sil ?? 'na',
            'fonction_securite' => $request->fonction_securite,
            'criticite' => $request->criticite,
            'fabricant' => $request->fabricant,
            'description' => $request->description,
            'zone_id' => $zonei->id,
            'status' => strtolower($request->status),
        ]);

        AuditLog::log(
            'Equipment Created',
            auth()->user(),
            'Equipment',
            $equipment->id,
            ['name' => $equipment->name]
        );

        return (new EquipmentResource($equipment))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: "/equipment/{equipment}",
        summary: "Détails d'un équipement",
        description: "Récupère les détails d'un équipement spécifique avec ses capteurs",
        tags: ["Équipements"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "equipment", in: "path", required: true, description: "ID de l'équipement", schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Détails de l'équipement",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(ref: "#/components/schemas/Equipment")
                )
            ),
            new OA\Response(response: 404, description: "Équipement non trouvé", ref: "#/components/schemas/Error"),
        ]
    )]
    public function show(Equipment $equipment)
    {
        return new EquipmentResource($equipment->load('sensors'));
    }

    #[OA\Put(
        path: "/equipment/{equipment}",
        summary: "Mettre à jour un équipement",
        description: "Met à jour les informations d'un équipement. Accessible uniquement aux administrateurs.",
        tags: ["Équipements"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "equipment", in: "path", required: true, description: "ID de l'équipement", schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "name", type: "string", example: "Équipement 1"),
                        new OA\Property(property: "type", type: "string", example: "Capteur de température"),
                        new OA\Property(property: "status", type: "string", enum: ["operational", "maintenance", "down", "standby"], example: "operational"),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Équipement mis à jour avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(ref: "#/components/schemas/Equipment")
                )
            ),
            new OA\Response(response: 403, description: "Non autorisé (administrateur requis)", ref: "#/components/schemas/Error"),
            new OA\Response(response: 404, description: "Équipement non trouvé", ref: "#/components/schemas/Error"),
        ]
    )]
    public function update(UpdateEquipmentRequest $request, Equipment $equipment)
    {
        $zonei = Zone::where('name', $request->zone)->first();

        $data = [
            'name' => $request->name,
            'type' => $request->type,
            'criticite' => $request->criticite,
            'fabricant' => $request->fabricant,
            'zone_id' => $zonei->id,
            'status' => strtolower($request->status),
        ];

        if ($request->has('type_systeme')) {
            $data['type_systeme'] = $request->type_systeme;
        }
        if ($request->has('niveau_sil')) {
            $data['niveau_sil'] = $request->niveau_sil;
        }
        if ($request->has('fonction_securite')) {
            $data['fonction_securite'] = $request->fonction_securite;
        }

        $equipment->update($data);

        AuditLog::log(
            'Equipment Updated',
            auth()->user(),
            'Equipment',
            $equipment->id,
            ['name' => $equipment->name]
        );

        return new EquipmentResource($equipment);
    }

    #[OA\Delete(
        path: "/equipment/{equipment}",
        summary: "Supprimer un équipement",
        description: "Supprime un équipement. Accessible uniquement aux administrateurs.",
        tags: ["Équipements"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "equipment", in: "path", required: true, description: "ID de l'équipement", schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Équipement supprimé avec succès",
                content: new OA\MediaType(
                    mediaType: "application/json",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "message", type: "string", example: "Équipement supprimé avec succès"),
                        ]
                    )
                )
            ),
            new OA\Response(response: 403, description: "Non autorisé (administrateur requis)", ref: "#/components/schemas/Error"),
            new OA\Response(response: 404, description: "Équipement non trouvé", ref: "#/components/schemas/Error"),
        ]
    )]
    public function destroy(Equipment $equipment)
    {
        if (!auth()->user()->isAdministrator()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        AuditLog::log(
            'Equipment Deleted',
            auth()->user(),
            'Equipment',
            $equipment->id,
            ['name' => $equipment->name]
        );

        $equipment->delete();

        return response()->json(['message' => 'Équipement supprimé avec succès']);
    }
}
