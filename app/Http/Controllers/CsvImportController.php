<?php

namespace App\Http\Controllers;

use App\Services\CsvImportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'CSV Import', description: 'Import CSV pour zones, équipements et capteurs')]
class CsvImportController extends Controller
{
    protected CsvImportService $importService;

    public function __construct(CsvImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Import zones from CSV file
     */
    #[OA\Post(
        path: '/api/import/zones',
        summary: 'Import zones from CSV',
        tags: ['CSV Import'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'CSV file')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Import successful'),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 403, description: 'Unauthorized')
        ]
    )]
    public function importZones(Request $request): JsonResponse
    {
        return $this->handleImport($request, 'zones');
    }

    /**
     * Import equipment from CSV file
     */
    #[OA\Post(
        path: '/api/import/equipment',
        summary: 'Import equipment from CSV',
        tags: ['CSV Import'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'CSV file')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Import successful'),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 403, description: 'Unauthorized')
        ]
    )]
    public function importEquipment(Request $request): JsonResponse
    {
        return $this->handleImport($request, 'equipment');
    }

    /**
     * Import sensors from CSV file
     */
    #[OA\Post(
        path: '/api/import/sensors',
        summary: 'Import sensors from CSV',
        tags: ['CSV Import'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'CSV file')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Import successful'),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 403, description: 'Unauthorized')
        ]
    )]
    public function importSensors(Request $request): JsonResponse
    {
        return $this->handleImport($request, 'sensors');
    }

    /**
     * Download sample CSV template
     */
    #[OA\Get(
        path: '/api/import/template/{type}',
        summary: 'Download sample CSV template',
        tags: ['CSV Import'],
        parameters: [
            new OA\Parameter(name: 'type', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['zones', 'equipment', 'sensors']))
        ],
        responses: [
            new OA\Response(response: 200, description: 'CSV template file'),
            new OA\Response(response: 400, description: 'Invalid type')
        ]
    )]
    public function downloadTemplate(string $type): JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $validTypes = ['zones', 'equipment', 'sensors'];

        if (!in_array($type, $validTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Type invalide. Types valides: ' . implode(', ', $validTypes)
            ], 400);
        }

        $csv = $this->importService->generateSampleCsv($type);
        $filename = "template_{$type}.csv";

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    /**
     * Get import information (headers, required fields)
     */
    #[OA\Get(
        path: '/api/import/info/{type}',
        summary: 'Get import information',
        tags: ['CSV Import'],
        parameters: [
            new OA\Parameter(name: 'type', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['zones', 'equipment', 'sensors']))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Import information')
        ]
    )]
    public function getImportInfo(string $type): JsonResponse
    {
        $validTypes = ['zones', 'equipment', 'sensors'];

        if (!in_array($type, $validTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Type invalide'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $type,
                'headers' => $this->importService->getExpectedHeaders($type),
                'required_fields' => $this->importService->getRequiredFields($type),
                'description' => $this->getTypeDescription($type),
            ]
        ]);
    }

    /**
     * Handle the import process
     */
    protected function handleImport(Request $request, string $type): JsonResponse
    {
        // Validate file
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120', // 5MB max
        ]);

        try {
            $file = $request->file('file');
            $content = file_get_contents($file->getRealPath());

            // Parse CSV
            $data = $this->importService->parseCsv($content, $type);

            if (empty($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune donnée valide trouvée dans le fichier CSV'
                ], 400);
            }

            // Process import based on type
            $result = match ($type) {
                'zones' => $this->importService->importZones($data),
                'equipment' => $this->importService->importEquipment($data),
                'sensors' => $this->importService->importSensors($data),
                default => throw new \Exception('Type invalide')
            };

            $statusCode = $result['success'] ? 200 : 400;

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success']
                    ? "{$result['imported']} élément(s) importé(s) avec succès"
                    : "Échec de l'import",
                'data' => [
                    'total' => $result['total'],
                    'imported' => $result['imported'],
                    'errors_count' => count($result['errors']),
                    'errors' => array_slice($result['errors'], 0, 10), // Limit to first 10 errors
                    'created_ids' => $result['created_ids'],
                ]
            ], $statusCode);

        } catch (\Exception $e) {
            Log::error("CSV Import Error ($type): " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get description for import type
     */
    protected function getTypeDescription(string $type): array
    {
        return match ($type) {
            'zones' => [
                'title' => 'Import des Zones',
                'description' => 'Importer des zones depuis un fichier CSV',
                'fields' => [
                    ['name' => 'name', 'description' => 'Nom de la zone', 'required' => true],
                    ['name' => 'description', 'description' => 'Description de la zone', 'required' => false],
                ]
            ],
            'equipment' => [
                'title' => 'Import des Équipements',
                'description' => 'Importer des équipements depuis un fichier CSV. Les zones doivent exister.',
                'fields' => [
                    ['name' => 'name', 'description' => 'Nom de l\'équipement', 'required' => true],
                    ['name' => 'code', 'description' => 'Code unique (auto-généré si vide)', 'required' => false],
                    ['name' => 'type', 'description' => 'Type d\'équipement', 'required' => true],
                    ['name' => 'zone', 'description' => 'Nom de la zone (doit exister)', 'required' => true],
                    ['name' => 'fabricant', 'description' => 'Fabricant', 'required' => false],
                    ['name' => 'status', 'description' => 'Statut (operational, maintenance, down, standby)', 'required' => false],
                    ['name' => 'criticite', 'description' => 'Criticité (low, medium, high, critical)', 'required' => false],
                    ['name' => 'description', 'description' => 'Description', 'required' => false],
                ]
            ],
            'sensors' => [
                'title' => 'Import des Capteurs',
                'description' => 'Importer des capteurs depuis un fichier CSV. Les équipements doivent exister.',
                'fields' => [
                    ['name' => 'name', 'description' => 'Nom du capteur', 'required' => true],
                    ['name' => 'code', 'description' => 'Code unique (auto-généré si vide)', 'required' => false],
                    ['name' => 'type', 'description' => 'Type de capteur', 'required' => true],
                    ['name' => 'unite', 'description' => 'Unité de mesure', 'required' => false],
                    ['name' => 'equipment_code', 'description' => 'Code de l\'équipement (doit exister)', 'required' => true],
                    ['name' => 'status', 'description' => 'Statut (active, bypassed, maintenance, faulty, calibration)', 'required' => false],
                    ['name' => 'seuil_critique', 'description' => 'Seuil critique', 'required' => false],
                ]
            ],
            default => []
        };
    }
}
