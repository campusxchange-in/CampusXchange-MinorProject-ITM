
<?php

/* =====================================================
   CAMPUSXCHANGE ENVIRONMENT LOADER
===================================================== */

if (!defined('CX_ENV_LOADED')) {

    $envFile = dirname(__DIR__, 2) . '/credentials/.env';

    if (!is_file($envFile) || !is_readable($envFile)) {
        throw new RuntimeException(
            'CampusXchange environment file is missing or unreadable.'
        );
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES);

    if ($lines === false) {
        throw new RuntimeException(
            'Unable to read CampusXchange environment file.'
        );
    }

    foreach ($lines as $line) {

        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);

        $key = trim($key);
        $value = trim($value);

        if ($key === '') {
            continue;
        }

        // Remove matching surrounding quotes.
        if (
            strlen($value) >= 2 &&
            (
                ($value[0] === '"' && substr($value, -1) === '"') ||
                ($value[0] === "'" && substr($value, -1) === "'")
            )
        ) {
            $value = substr($value, 1, -1);
        }

        // Preserve values already supplied by the server.
        if (
            !isset($_ENV[$key]) &&
            !isset($_SERVER[$key]) &&
            getenv($key) === false
        ) {
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv("$key=$value");
        }
    }

    define('CX_ENV_LOADED', true);
}
