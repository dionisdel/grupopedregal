<?php

namespace App\Console\Commands;

use App\Services\ExcelImportService;
use Illuminate\Console\Command;

class ImportProductsCommand extends Command
{
    protected $signature = 'import:products {file : Path to the Excel file (.xlsx or .xls)}';

    protected $description = 'Import products from an Excel file into the database';

    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return self::FAILURE;
        }

        $this->info("Importing products from: {$filePath}");
        $this->newLine();

        $service = new ExcelImportService();

        try {
            $result = $service->import($filePath);
        } catch (\Throwable $e) {
            $this->error("Import failed: {$e->getMessage()}");
            return self::FAILURE;
        }

        $this->info("Import complete!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Created', $result['created']],
                ['Updated', $result['updated']],
                ['Errors', count($result['errors'])],
                ['Total processed', $result['created'] + $result['updated'] + count($result['errors'])],
            ]
        );

        if (!empty($result['errors'])) {
            $this->newLine();
            $this->warn('Errors:');
            $this->table(
                ['Row', 'Message'],
                array_map(fn ($e) => [$e['row'], $e['message']], $result['errors'])
            );
        }

        return self::SUCCESS;
    }
}
