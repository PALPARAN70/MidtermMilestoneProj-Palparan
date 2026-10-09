<?php
declare(strict_types=1);

/* Loaded first by every page. */

date_default_timezone_set('Asia/Manila');

require __DIR__ . '/db.php';

// Load classes from src/ on demand (ClassName -> src/ClassName.php)
spl_autoload_register(static function (string $class): void {
    $file = __DIR__ . '/src/' . $class . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require __DIR__ . '/src/helpers.php';

Session::start();

// Friendly error page instead of a stack trace
set_exception_handler(static function (Throwable $e): void {
    error_log((string) $e);
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo '<h1>Something went wrong.</h1><p>Please try again in a moment.</p>';
});
