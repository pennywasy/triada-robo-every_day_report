<?php

return [
    'default' => 'sqlite',

    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => ($_ENV['DB_DATABASE'] ?? '') !== ''
                ? __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . $_ENV['DB_DATABASE']
                : __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'database.sqlite',
            'prefix' => '',
            'foreign_key_constraints' => $_ENV['DB_FOREIGN_KEYS'] ?? false,
        ],
        'mysql' => [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_NAME'] ?? 'forge',
            'username' => $_ENV['DB_USERNAME'] ?? 'forge',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port' => $_ENV['DB_PORT'] ?? '5432',
            'database' => $_ENV['DB_NAME'] ?? 'forge',
            'username' => $_ENV['DB_USERNAME'] ?? 'forge',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],
    ],

    'migrations' => 'migrations',
];