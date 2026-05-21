<?php

namespace App\Core;

class Loader
{

    private static array $currentContext = [];
    private static array $contextStack = [];

    /**
     * @return void
     */
    public static function register(): void
    {
        require __DIR__ . DIRECTORY_SEPARATOR . 'helpers.php';

        self::loadEnv(__DIR__ . '/../../.env');

        spl_autoload_register(function ($class) {
            $file = self::getClassFile($class);
            if (file_exists($file)) {
                require $file;
                return true;
            }
            return false;
        });
    }

    /**
     * @param string $class
     * @return string
     */
    private static function getClassFile(string $class): string
    {
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);

        $baseDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;

        return $baseDir . $class . '.php';
    }

    /**
     * @param string $path
     * @return void
     */
    public static function loadEnv(string $path): void
    {
        if (!file_exists($path)) {
            throw new \RuntimeException(".env file not found at path: $path");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        self::$currentContext = [];
        self::$contextStack = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if (strpos($line, '#') === 0 || $line === '') {
                continue;
            }

            // Обработка начала массива
            if (preg_match('/^([a-zA-Z0-9_]+)\s*=\s*\[\s*$/', $line, $matches)) {
                self::$contextStack[] = self::$currentContext;
                self::$currentContext[] = $matches[1];
                continue;
            }

            // Обработка конца массива
            if ($line === ']') {
                if (empty(self::$contextStack)) {
                    throw new \RuntimeException("Unmatched ] in .env file");
                }
                self::$currentContext = array_pop(self::$contextStack);
                continue;
            }

            list($name, $value) = self::parseLine($line);
            self::setEnvValue($name, $value);
        }
    }

    /**
     * Устанавливает значение в $_ENV и $_SERVER с учетом текущего контекста
     *
     * @param string $name
     * @param mixed $value
     */
    private static function setEnvValue(string $name, $value): void
    {
        if (empty(self::$currentContext)) {
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
            return;
        }

        $target = &$_ENV;
        foreach (self::$currentContext as $ctx) {
            if (!isset($target[$ctx]) || !is_array($target[$ctx])) {
                $target[$ctx] = [];
            }
            $target = &$target[$ctx];
        }
        $target[$name] = $value;

        $target = &$_SERVER;
        foreach (self::$currentContext as $ctx) {
            if (!isset($target[$ctx]) || !is_array($target[$ctx])) {
                $target[$ctx] = [];
            }
            $target = &$target[$ctx];
        }
        $target[$name] = $value;
    }

    /**
     * @param string $line
     * @return array
     */
    protected static function parseLine(string $line): array
    {
        list($name, $value) = array_map('trim', explode('=', $line, 2));
        $value = self::parseValue($value);
        return [$name, $value];
    }

    /**
     * @param string $value
     * @return mixed
     */
    private static function parseValue(string $value)
    {
        if (preg_match('/^\[.*\]$/', $value) || preg_match('/^\{.*\}$/', $value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            return $matches[1];
        }
        if (preg_match("/^'(.*)'$/", $value, $matches)) {
            return $matches[1];
        }

        if (strtolower($value) === 'true') return true;
        if (strtolower($value) === 'false') return false;
        if (strtolower($value) === 'null') return null;

        return $value;
    }
}