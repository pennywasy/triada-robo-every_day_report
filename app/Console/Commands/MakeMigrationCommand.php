<?php

namespace App\Console\Commands;

use App\Core\Command;
use App\Core\Filesystem;

class MakeMigrationCommand extends Command
{
    protected string $name = 'make:migration';
    protected string $description = 'Create a new migration file';

    public function handle(array $arguments): int
    {
        if (empty($arguments)) {
            $this->error('Please specify migration name');
            return 1;
        }

        $name = $this->normalizeInput($arguments[0]);
        $isUpdate = $this->isUpdateMigration($name);

        $className = $this->generateClassName($name, $isUpdate);
        $tableName = $this->generateTableName($name);
        $fileName = $this->generateFileName($name, $isUpdate);
        $filePath = database_path('migrations'. DIRECTORY_SEPARATOR .$fileName);

        if (!is_dir(database_path('migrations'))) {
            mkdir(database_path('migrations'), 0755, true);
        }

        $stub = $this->getStubContent($className, $tableName, $isUpdate);

        if (file_put_contents($filePath, $stub)) {
            $this->info("Migration created successfully!");
            $this->line("File: {$fileName}");
            $this->line("Class: {$className}");
            $this->line("Table: {$tableName}");
            $this->line("Type: " . ($isUpdate ? 'Update' : 'Create'));
            return 0;
        }

        $this->error("Failed to create migration");
        return 1;
    }

    protected function isUpdateMigration(string $name): bool
    {
        return str_starts_with(strtolower($name), 'update_') ||
            str_contains(strtolower($name), '_update');
    }

    protected function normalizeInput(string $input): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $input);
    }

    protected function generateClassName(string $name, bool $isUpdate): string
    {
        $cleanName = preg_replace([
            '/^create_/i', '/_table$/i',
            '/^update_/i', '/_update$/i'
        ], '', $name);

        $className = str_replace('_', '', ucwords($cleanName, '_'));

        return $isUpdate ? 'Update' . $className . 'Table' : 'Create' . $className . 'Table';
    }

    protected function generateTableName(string $name): string
    {
        $table = preg_replace([
            '/^create_/i', '/_table$/i',
            '/^update_/i', '/_update$/i'
        ], '', $name);

        return strtolower($table);
    }

    protected function generateFileName(string $name, bool $isUpdate): string
    {
        $fileName = strtolower($name);

        if ($isUpdate) {
            if (!str_starts_with($fileName, 'update_')) {
                $fileName = 'update_' . $fileName;
            }
            if (!str_ends_with($fileName, '_table') && !str_ends_with($fileName, '_update')) {
                $fileName .= '_table';
            }
        } else {
            if (!str_starts_with($fileName, 'create_')) {
                $fileName = 'create_' . $fileName;
            }
            if (!str_ends_with($fileName, '_table')) {
                $fileName .= '_table';
            }
        }

        return date('Y_m_d_His') . '_' . $fileName . '.php';
    }

    protected function getStubContent(string $className, string $tableName, bool $isUpdate): string
    {
        $stubType = $isUpdate ? 'migration.update.stub' : 'migration.create.stub';
        $stubPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . $stubType;

        if (!file_exists($stubPath)) {
            throw new \RuntimeException('Migration stub file not found at: ' . $stubPath);
        }

        $stub = file_get_contents($stubPath);

        return str_replace(
            ['{{ClassName}}', '{{TableName}}'],
            [$className, $tableName],
            $stub
        );
    }
}