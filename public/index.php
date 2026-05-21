<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('short_open_tag', '1');

error_reporting(E_ALL);

if (ob_get_level() > 0) {
    ob_end_clean();
}


require __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/../app/Core/Loader.php';


try {
    \App\Core\Loader::register();

    $app = new \App\Core\App();

    $app->run();
}catch (Exception $e) {
    echo '<div style="background: #f8d7da; color: #721c24; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px;">';
    echo '<h2 style="margin-top: 0;">Application Error</h2>';
    echo '<p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><strong>File:</strong> ' . $e->getFile() . ':' . $e->getLine() . '</p>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div>';

    \App\Core\Logger::error($e->getMessage());
}
