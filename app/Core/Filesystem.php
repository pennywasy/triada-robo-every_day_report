<?php

namespace App\Core;

class Filesystem
{
    public static function ensureDirectoryExists(string $path): bool
    {
        if (!is_dir($path)) {
            return mkdir($path, 0755, true);
        }
        return true;
    }

    public static function put(string $path, string $contents): bool
    {
        return file_put_contents($path, $contents) !== false;
    }
}