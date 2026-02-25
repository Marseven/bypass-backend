<?php

namespace App\Services;

use App\Models\Zone;
use App\Models\Equipment;
use App\Models\Sensor;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CsvImportService
{
    /**
     * Expected headers for each import type
     */
    protected array $expectedHeaders = [
        'zones' => ['name', 'description'],
        'equipment' => ['name', 'code', 'type', 'zone', 'fabricant', 'status', 'criticite', 'description'],
        'sensors' => ['name', 'code', 'type', 'unite', 'equipment_code', 'status', 'seuil_critique'],
    ];

    /**
     * Required fields for each import type
     */
    protected array $requiredFields = [
        'zones' => ['name'],
        'equipment' => ['name', 'type', 'zone'],
        'sensors' => ['name', 'type', 'equipment_code'],
    ];

    /**
     * Import zones from CSV data
     */
    public function importZones(array $data): array
    {
        return $this->processImport($data, 'zones', function ($row) {
            return Zone::create([
                'name' => $row['name'],
                'description' => $row['description'] ?? null,
                'status' => true,
            ]);
        });
    }

    /**
     * Import equipment from CSV data
     */
    public function importEquipment(array $data): array
    {
        return $this->processImport($data, 'equipment', function ($row) {
            // Find zone by name
            $zone = Zone::where('name', $row['zone'])->first();
            if (!$zone) {
                throw new \Exception("Zone '{$row['zone']}' non trouvée");
            }

            // Generate code if not provided
            $code = !empty($row['code']) ? $row['code'] : $this->generateEquipmentCode($row['name'], $zone);

            return Equipment::create([
                'name' => $row['name'],
                'code' => $code,
                'type' => $row['type'],
                'zone_id' => $zone->id,
                'fabricant' => $row['fabricant'] ?? null,
                'status' => $row['status'] ?? 'operational',
                'criticite' => $row['criticite'] ?? 'medium',
                'description' => $row['description'] ?? null,
            ]);
        });
    }

    /**
     * Import sensors from CSV data
     */
    public function importSensors(array $data): array
    {
        return $this->processImport($data, 'sensors', function ($row) {
            // Find equipment by code
            $equipment = Equipment::where('code', $row['equipment_code'])->first();
            if (!$equipment) {
                throw new \Exception("Équipement avec code '{$row['equipment_code']}' non trouvé");
            }

            // Generate code if not provided
            $code = !empty($row['code']) ? $row['code'] : $this->generateSensorCode($row['name'], $equipment);

            return Sensor::create([
                'name' => $row['name'],
                'code' => $code,
                'type' => $row['type'],
                'unite' => $row['unite'] ?? null,
                'equipment_id' => $equipment->id,
                'status' => $row['status'] ?? 'active',
                'seuil_critique' => $row['seuil_critique'] ?? null,
            ]);
        });
    }

    /**
     * Parse CSV content and return array of rows
     */
    public function parseCsv(string $content, string $type): array
    {
        $lines = array_filter(explode("\n", $content), fn($line) => trim($line) !== '');

        if (count($lines) < 2) {
            throw new \Exception('Le fichier CSV doit contenir au moins une ligne d\'en-tête et une ligne de données');
        }

        // Parse headers
        $headers = str_getcsv(array_shift($lines));
        $headers = array_map(fn($h) => strtolower(trim($h)), $headers);

        // Validate headers
        $this->validateHeaders($headers, $type);

        $rows = [];
        $lineNumber = 2; // Start from line 2 (after header)

        foreach ($lines as $line) {
            $values = str_getcsv($line);

            if (count($values) !== count($headers)) {
                Log::warning("CSV Import: Line $lineNumber has different column count, skipping");
                $lineNumber++;
                continue;
            }

            $row = array_combine($headers, $values);
            $row = array_map(fn($v) => trim($v), $row);
            $row['_line'] = $lineNumber;

            $rows[] = $row;
            $lineNumber++;
        }

        return $rows;
    }

    /**
     * Validate CSV headers
     */
    protected function validateHeaders(array $headers, string $type): void
    {
        $required = $this->requiredFields[$type] ?? [];
        $missing = array_diff($required, $headers);

        if (!empty($missing)) {
            throw new \Exception('Colonnes obligatoires manquantes: ' . implode(', ', $missing));
        }
    }

    /**
     * Validate a single row
     */
    protected function validateRow(array $row, string $type): ?string
    {
        $required = $this->requiredFields[$type] ?? [];

        foreach ($required as $field) {
            if (empty($row[$field])) {
                return "Le champ '$field' est requis";
            }
        }

        return null;
    }

    /**
     * Process import with transaction
     */
    protected function processImport(array $data, string $type, callable $createCallback): array
    {
        $result = [
            'success' => true,
            'total' => count($data),
            'imported' => 0,
            'errors' => [],
            'created_ids' => [],
        ];

        DB::beginTransaction();

        try {
            foreach ($data as $index => $row) {
                $lineNumber = $row['_line'] ?? ($index + 2);

                // Validate row
                $validationError = $this->validateRow($row, $type);
                if ($validationError) {
                    $result['errors'][] = "Ligne $lineNumber: $validationError";
                    continue;
                }

                try {
                    $model = $createCallback($row);
                    $result['imported']++;
                    $result['created_ids'][] = $model->id;

                    // Log audit
                    AuditLog::log(
                        Auth::id(),
                        'import_csv',
                        $type,
                        $model->id,
                        ['data' => $row]
                    );
                } catch (\Exception $e) {
                    $result['errors'][] = "Ligne $lineNumber: " . $e->getMessage();
                }
            }

            if ($result['imported'] > 0) {
                DB::commit();
            } else {
                DB::rollBack();
                $result['success'] = false;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $result['success'] = false;
            $result['errors'][] = 'Erreur générale: ' . $e->getMessage();
            Log::error('CSV Import Error: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Generate equipment code
     */
    protected function generateEquipmentCode(string $name, Zone $zone): string
    {
        // Get consonants from name
        $words = explode(' ', strtoupper($name));
        $prefix = '';
        foreach ($words as $word) {
            $consonants = preg_replace('/[AEIOU\s]/i', '', $word);
            if (strlen($prefix) < 2 && strlen($consonants) > 0) {
                $prefix .= substr($consonants, 0, 1);
            }
        }
        $prefix = str_pad($prefix, 2, 'X');

        // Get zone letter
        $zoneLetter = strtoupper(substr(preg_replace('/[^A-Z]/i', '', $zone->name), 0, 1)) ?: 'Z';

        // Get next sequence number
        $lastEquipment = Equipment::where('code', 'like', "$prefix-$zoneLetter-%")
            ->orderBy('code', 'desc')
            ->first();

        if ($lastEquipment) {
            $lastNumber = (int) substr($lastEquipment->code, -3);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s-%s-%03d', $prefix, $zoneLetter, $nextNumber);
    }

    /**
     * Generate sensor code
     */
    protected function generateSensorCode(string $name, Equipment $equipment): string
    {
        // Clean sensor name
        $cleanName = preg_replace('/^(SENSOR|CAPTEUR)\s*/i', '', strtoupper($name));
        $prefix = substr(preg_replace('/[^A-Z0-9]/i', '', $cleanName), 0, 5);
        $prefix = str_pad($prefix, 3, 'S');

        // Get equipment code parts
        $eqParts = explode('-', $equipment->code);
        $eqCode = $eqParts[0] ?? 'EQ';

        // Get zone letter from equipment
        $zoneLetter = $eqParts[1] ?? 'Z';

        // Get next sequence number
        $pattern = "$prefix-$eqCode-$zoneLetter-%";
        $lastSensor = Sensor::where('code', 'like', $pattern)
            ->orderBy('code', 'desc')
            ->first();

        if ($lastSensor) {
            preg_match('/-(\d{3})-?/', $lastSensor->code, $matches);
            $nextNumber = isset($matches[1]) ? ((int) $matches[1]) + 1 : 1;
        } else {
            $nextNumber = 1;
        }

        // Get suffix from name
        $suffix = substr(preg_replace('/[^A-Z0-9]/i', '', $cleanName), -2) ?: 'XX';

        return sprintf('%s-%s-%s-%03d-%s', $prefix, $eqCode, $zoneLetter, $nextNumber, $suffix);
    }

    /**
     * Generate sample CSV content
     */
    public function generateSampleCsv(string $type): string
    {
        $headers = $this->expectedHeaders[$type] ?? [];
        $csv = implode(',', $headers) . "\n";

        switch ($type) {
            case 'zones':
                $csv .= "Zone Production,Zone principale de production\n";
                $csv .= "Zone Stockage,Zone de stockage des matières premières\n";
                $csv .= "Zone Maintenance,Atelier de maintenance\n";
                break;

            case 'equipment':
                $csv .= "Compresseur A1,COMP-P-001,compresseur,Zone Production,Atlas Copco,operational,high,Compresseur principal\n";
                $csv .= "Pompe P1,,pompe,Zone Production,Grundfos,operational,medium,Pompe de circulation\n";
                $csv .= "Chaudière CH1,,chaudiere,Zone Production,Viessmann,maintenance,critical,Chaudière principale\n";
                break;

            case 'sensors':
                $csv .= "Capteur Température 1,,temperature,°C,COMP-P-001,active,80\n";
                $csv .= "Capteur Pression 1,,pressure,bar,COMP-P-001,active,10\n";
                $csv .= "Capteur Niveau 1,,level,m,PUMP-P-001,active,5\n";
                break;
        }

        return $csv;
    }

    /**
     * Get expected headers for a type
     */
    public function getExpectedHeaders(string $type): array
    {
        return $this->expectedHeaders[$type] ?? [];
    }

    /**
     * Get required fields for a type
     */
    public function getRequiredFields(string $type): array
    {
        return $this->requiredFields[$type] ?? [];
    }
}
