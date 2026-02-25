<?php

namespace App\Jobs;

use App\Services\CsvImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(
        private string $filePath,
        private string $type,
        private int $userId,
    ) {}

    public function handle(CsvImportService $importService): void
    {
        Log::info('Starting async CSV import', [
            'type' => $this->type,
            'file' => $this->filePath,
            'user_id' => $this->userId,
        ]);

        try {
            $content = file_get_contents($this->filePath);
            $data = $importService->parseCsv($content, $this->type);

            $result = match ($this->type) {
                'zones' => $importService->importZones($data),
                'equipment' => $importService->importEquipment($data),
                'sensors' => $importService->importSensors($data),
                default => throw new \RuntimeException("Invalid import type: {$this->type}"),
            };

            Log::info('Async CSV import completed', [
                'type' => $this->type,
                'imported' => $result['imported'],
                'errors' => count($result['errors']),
                'user_id' => $this->userId,
            ]);
        } catch (\Exception $e) {
            Log::error('Async CSV import failed', [
                'type' => $this->type,
                'error' => $e->getMessage(),
                'user_id' => $this->userId,
            ]);
            throw $e;
        } finally {
            if (file_exists($this->filePath)) {
                @unlink($this->filePath);
            }
        }
    }
}
