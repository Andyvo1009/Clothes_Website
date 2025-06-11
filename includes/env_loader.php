<?php

/**
 * Simple Environment Variable Loader
 * Loads environment variables from .env file
 */

class EnvLoader
{
    /**
     * Load environment variables from .env file
     */
    public static function load($filePath)
    {
        if (!file_exists($filePath)) {
            throw new Exception(".env file not found at: " . $filePath);
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Split key=value
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                if (preg_match('/^".*"$/', $value) || preg_match("/^'.*'$/", $value)) {
                    $value = substr($value, 1, -1);
                }

                // Set environment variable if not already set
                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
    }

    /**
     * Get environment variable with optional default
     */
    public static function get($key, $default = null)
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}

// Auto-load .env file when this file is included
try {
    EnvLoader::load(__DIR__ . '/../.env');
} catch (Exception $e) {
    // In production, you might want to log this error
    // For now, just continue without .env file
}
