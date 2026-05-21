<?php

namespace App\Core;

use Exception;

class View
{
    /**
     * @param string $templatePath
     * @param array $data
     * @return string
     * @throws Exception
     */
    public function render(string $templatePath, array $data = []): string
    {
        $fullPath = dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR
            . 'resources'
            . DIRECTORY_SEPARATOR
            . 'views'
            . DIRECTORY_SEPARATOR
            . ltrim($templatePath, '/');

        // Добавляем .php, если расширение не указано
        if (!str_ends_with($fullPath, '.php')) {
            $fullPath .= '.php';
        }

        if (!file_exists($fullPath)) {
            throw new Exception("Template not found: {$templatePath}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $fullPath;
        return ob_get_clean();
    }

    /**
     * @param string $path
     * @return string
     */
    public static function asset(string $path): string
    {
        // Для Angular-ассетов
        if (strpos($path, '/assets/') === 0) {
            $publicPath = dirname(__DIR__, 2) . '/public';
            $filePattern = preg_replace('/\.[a-f0-9]{8}\./', '.*.', $path);
            $files = glob($publicPath . $filePattern);

            if (!empty($files)) {
                $path = str_replace($publicPath, '', $files[0]);
            }
        }

        $fullPath = dirname(__DIR__, 2) . '/public/' . ltrim($path, '/');

        if (!file_exists($fullPath)) {
            return $path;
        }

        $version = self::getFileVersion($fullPath);
        return $path . (strpos($path, '?') === false ? '?' : '&') . 'v=' . $version;
    }

    private static function getFileVersion(string $filePath): string
    {
        if (function_exists('md5_file')) {
            return substr(md5_file($filePath), 0, 8);
        }

        return filemtime($filePath);
    }

    public static function renderAngular(): string
    {
        $angularAppPath = dirname(__DIR__, 2)
            . '/resources/views/angular-app.php';

        if (!file_exists($angularAppPath)) {
            throw new Exception("Angular template not found");
        }

        ob_start();
        include $angularAppPath;
        return ob_get_clean();
    }
}