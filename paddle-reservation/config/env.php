<?php
// Lightweight environment loader with fallback to vlucas/phpdotenv if available.
// Loads variables into getenv()/$_ENV/$_SERVER.

$composer = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composer)) {
    require_once $composer;
}

if (class_exists('\\Dotenv\\Dotenv')) {
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->safeLoad();
    } catch (Exception $e) {
        // ignore and fall back
    }
} else {
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') === false) continue;
            list($name, $val) = explode('=', $line, 2);
            $name = trim($name);
            $val = trim($val);
            if ((strlen($val) >= 2) && (($val[0] === '"' && $val[strlen($val)-1] === '"') || ($val[0] === "'" && $val[strlen($val)-1] === "'"))) {
                $val = substr($val, 1, -1);
            }
            putenv("{$name}={$val}");
            $_ENV[$name] = $val;
            $_SERVER[$name] = $val;
        }
    }
}
