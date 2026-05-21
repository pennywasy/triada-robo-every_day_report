<?php

namespace App\Console\Commands;

use App\Core\Command;

class MakeModelCommand extends Command
{
    protected string $name = 'make:model';
    protected string $description = 'Create a new Eloquent model class';

    public function handle(array $arguments): int
    {
        if (empty($arguments)) {
            $this->error('Please specify model name');
            return 1;
        }

        $className = $this->normalizeName($arguments[0]);
        $modelName = $this->normalizeModelName($arguments[0]);
        $tableName = $this->guessTableName($modelName);
        $directory = app_path('Models');
        $filePath = $directory . DIRECTORY_SEPARATOR . "{$modelName}.php";

        // Создаем директорию Models если ее нет
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            $this->error("Failed to create Models directory");
            return 1;
        }

        if (file_exists($filePath)) {
            $this->error("Model {$modelName} already exists!");
            return 1;
        }

        $stub = $this->getStubContent($className, $modelName, $tableName);

        if (file_put_contents($filePath, $stub)) {
            $this->info("Model created successfully!");
            $this->line("Path: {$filePath}");
            $this->line("Table name: {$tableName}");
            return 0;
        }

        $this->error("Failed to create model");
        return 1;
    }

    protected function normalizeModelName(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9]/', '', $name);
        return ucfirst($name);
    }

    protected function guessTableName(string $modelName): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $modelName)) . 's';
    }

    protected function normalizeName(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9]/', '', $name);
        return ucfirst($name);
    }
    protected function getStubContent(string $className, string $modelName, string $tableName): string
    {
        $stubPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'model.stub';

        if (!file_exists($stubPath)) {
            throw new \RuntimeException('Model stub file not found at: ' . $stubPath);
        }

        $stub = file_get_contents($stubPath);
        return str_replace(
            ['{{ClassName}}', '{{ModelName}}', '{{table_name}}'],
            [$className, $modelName, $tableName],
            $stub
        );
    }
}