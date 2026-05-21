<?php

namespace App\Console\Commands;

use App\Core\Command;

class ServeCommand extends Command
{
    protected string $name = 'serve';
    protected string $description = 'Start the PHP development server and Angular watcher';

    public function handle(array $arguments = []): int
    {
        $host = '127.0.0.1';
        $port = 8000;
        $publicDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR .'..' . DIRECTORY_SEPARATOR . 'public');
        $angularDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR .'..' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'angular');

        // Парсим аргументы
        foreach ($arguments as $arg) {
            if (strpos($arg, '--host=') === 0) {
                $host = substr($arg, 7);
            } elseif (strpos($arg, '--port=') === 0) {
                $port = (int) substr($arg, 7);
            }
        }

        $this->info("Starting development environment...");

        // Проверяем наличие Angular проекта
        if ($angularDir && file_exists($angularDir . DIRECTORY_SEPARATOR . 'angular.json')) {
            $this->line("Checking Angular CLI...");

            // Проверяем установлен ли Angular CLI (используем правильную команду)
            exec('ng version 2>&1', $output, $returnCode);

            if ($returnCode !== 0) {
                $this->error("Angular CLI is not installed or not available in PATH");
                $this->line("Please install Angular CLI globally: npm install -g @angular/cli");
            } else {
                $this->line("Starting Angular build watcher...");

                // Запускаем Angular build --watch
                $angularCommand = "cd {$angularDir} && ng build --watch && node post-build.js";

                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    // Для Windows используем start в новом окне
                    $descriptorSpec = [
                        0 => ['pipe', 'r'],
                        1 => ['pipe', 'w'],
                        2 => ['pipe', 'w']
                    ];
                    $process = proc_open("start \"Angular Build\" cmd /C \"{$angularCommand}\"", $descriptorSpec, $pipes);
                } else {
                    // Для Unix-систем
                    $process = proc_open("{$angularCommand} > /dev/null 2>&1 &", [], $pipes);
                }

                if (!is_resource($process)) {
                    $this->error("Failed to start Angular build watcher");
                } else {
                    $this->line("Angular build watcher started successfully");
                    // Закрываем дескрипторы pipes
                    foreach ($pipes as $pipe) {
                        if (is_resource($pipe)) {
                            fclose($pipe);
                        }
                    }
                }
            }
        } else {
            $this->error("Angular project not found in resources/angular/ or angular.json is missing, skipping...");
        }

        $this->line("\nStarting PHP development server...");
        $this->line("Document root: {$publicDir}");
        $this->line("URL: http://{$host}:{$port}");
        $this->line("Press Ctrl+C to stop\n");

        passthru("php -S {$host}:{$port} -t {$publicDir}");

        return 0;
    }
}