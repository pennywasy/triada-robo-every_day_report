<?php

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return dirname(__DIR__, 2) . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('database_path')) {
    function database_path(string $path = ''): string
    {
        return base_path('database' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
    }
}

if (!function_exists('app_path')) {
    function app_path(string $path = ''): string
    {
        return base_path('app' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
    }
}

use Illuminate\Support\Carbon;

if (!function_exists('now')) {
    function now(): Carbon
    {
        return Carbon::now();
    }
}
