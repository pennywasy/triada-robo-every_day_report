<?php
// define('C_REST_CLIENT_ID','local.5c8bb1b0891cf2.87252039');//Application ID
// define('C_REST_CLIENT_SECRET','SakeVG5mbRdcQet45UUrt6q72AMTo7fkwXSO7Y5LYFYNCRsA6f');//Application key
// or
if (!defined('C_REST_WEB_HOOK_URL')) {
    define('C_REST_WEB_HOOK_URL', $_ENV['BITRIX_WEBHOOK_URL']); //url on creat Webhook
}

// CRITICAL: must be uppercase 'UTF-8' — iconv() is case-sensitive on some systems (Alpine/musl).
// Lowercase 'utf-8' causes iconv() to return false, corrupting API request/response data.
if (!defined('C_REST_CURRENT_ENCODING')) {
    define('C_REST_CURRENT_ENCODING', 'UTF-8');
}

if (!defined('C_REST_IGNORE_SSL')) {
    define('C_REST_IGNORE_SSL', true); //turn off validate ssl by curl
}

if (!defined('C_REST_LOG_TYPE_DUMP')) {
    define('C_REST_LOG_TYPE_DUMP', true); //logs save var_export for viewing convenience
}

// Enable CRest debug logging (set to true in production to disable)
if (!defined('C_REST_BLOCK_LOG')) {
    define('C_REST_BLOCK_LOG', false);
}

// Write CRest logs to project-root logs/crrest/ directory
if (!defined('C_REST_LOGS_DIR')) {
    define('C_REST_LOGS_DIR', __DIR__ . '/../logs/crrest/');
}