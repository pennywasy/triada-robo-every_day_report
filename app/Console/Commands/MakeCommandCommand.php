<?php

namespace App\Console\Commands;

use App\Core\Command;
use App\Core\Filesystem;

class MakeCommandCommand extends Command
{
    protected string $name = 'make:command';
    protected string $description = 'Create a new command class';

    public function handle(array $arguments): int
    {
        if (empty($arguments)) {
            $this->error('Please specify command name');
            return 1;
        }

        $commandName = $arguments[0];
        $className = $this->getClassName($commandName);
        $commandFile = $this->getCommandPath($className);
        $commandNameKebab = $this->getCommandName($commandName);

        if (file_exists($commandFile)) {
            $this->error("Command {$className} already exists!");
            return 1;
        }

        $this->createCommandFile($className, $commandNameKebab, $commandFile);
        $this->registerCommand($className);

        $this->info("Command [{$commandFile}] created successfully");
        $this->line("Now you can use it with: php console {$commandNameKebab}");

        return 0;
    }

    private function getClassName(string $name): string
    {
        return ucfirst($name) . 'Command';
    }

    private function getCommandName(string $name): string
    {
        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^-])([A-Z][a-z])/'], '$1:$2', $name));
    }

    private function getCommandPath(string $className): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR ."{$className}.php";
    }

    private function getConfigPath(): string
    {
        return base_path('config' . DIRECTORY_SEPARATOR . 'commands.php');
    }

    private function getStubContent(string $className, string $commandName): string
    {
        $stub = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR .'stubs' . DIRECTORY_SEPARATOR . 'command.stub');
        return str_replace(
            ['{{ClassName}}', '{{command_name}}', '{{description}}'],
            [$className, $commandName, 'Command description'],
            $stub
        );
    }

    private function createCommandFile(string $className, string $commandName, string $path): void
    {
        $content = $this->getStubContent($className, $commandName);
        file_put_contents($path, $content);
    }

    private function registerCommand(string $className): void
    {
        $configPath = $this->getConfigPath();
        $config = include $configPath;

        $fullClassName = "App\\Console\\Commands\\{$className}";

        if (!in_array($fullClassName, $config['commands'])) {
            $config['commands'][] = $fullClassName;

            $exported = var_export($config, true);
            $content = "<?php\n\nreturn {$exported};";

            file_put_contents($configPath, $content);
        }
    }
}