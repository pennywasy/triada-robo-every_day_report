<?php

namespace App\Console\Commands;

use App\Core\Command;

class MakeControllerCommand extends Command
{
    protected string $name = 'make:controller';
    protected string $description = 'Создать новый контроллер';

    public function handle(array $arguments): int
    {
        if (empty($arguments)) {
            $this->error('Укажите имя контроллера');
            return 1;
        }

        $controllerName = $arguments[0];
        $stub = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'controller.stub');
        $content = str_replace('{{ControllerName}}', $controllerName, $stub);

        $directory = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Controllers';
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = $directory . DIRECTORY_SEPARATOR . $controllerName . '.php';

        if (file_exists($filename)) {
            $this->error("Контроллер {$controllerName} уже существует!");
            return 1;
        }

        file_put_contents($filename, $content);
        $this->info("Контроллер {$controllerName} успешно создан!");
        return 0;
    }
}