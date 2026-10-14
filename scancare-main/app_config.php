<?php

if (!function_exists('loadEnvironmentFile')) {
    function loadEnvironmentFile($path) {
        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
                continue;
            }

            if (getenv($key) !== false || array_key_exists($key, $_ENV)) {
                continue;
            }

            $value = trim($parts[1]);
            $length = strlen($value);
            if ($length >= 2) {
                $first = $value[0];
                $last = $value[$length - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                    if ($first === '"') {
                        $value = stripcslashes($value);
                    }
                }
            }

            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

loadEnvironmentFile(__DIR__ . '/.env');

if (!function_exists('getEnvironmentValue')) {
    function getEnvironmentValue($key, $default = '') {
        if (array_key_exists($key, $_ENV) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }

        $value = getenv($key);
        return $value === false || $value === '' ? $default : $value;
    }
}

if (!function_exists('getEnvironmentBoolean')) {
    function getEnvironmentBoolean($key, $default = false): bool {
        $value = getEnvironmentValue($key, $default ? 'true' : 'false');
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }
}

if (!function_exists('isProductionEnvironment')) {
    function isProductionEnvironment() {
        return strtolower(getEnvironmentValue('APP_ENV', 'production')) === 'production';
    }
}

if (!function_exists('appUrl')) {
    function appUrl($path = '') {
        $baseUrl = rtrim(getEnvironmentValue('APP_URL', 'https://scancare.it.com'), '/');
        if ($path === '') {
            return $baseUrl;
        }

        return $baseUrl . '/' . ltrim($path, '/');
    }
}

if (!function_exists('appPath')) {
    function appPath($path = '') {
        $basePath = parse_url(appUrl(), PHP_URL_PATH) ?: '';
        $basePath = rtrim($basePath, '/');
        if ($path === '') {
            return $basePath === '' ? '/' : $basePath . '/';
        }

        return $basePath . '/' . ltrim($path, '/');
    }
}

if (!function_exists('isHttpsRequest')) {
    function isHttpsRequest(): bool {
        if (PHP_SAPI === 'cli') {
            return false;
        }

        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));

        return ($https !== '' && $https !== 'off' && $https !== '0')
            || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
    }
}

$appDebug = getEnvironmentBoolean('APP_DEBUG', false);
ini_set('display_errors', $appDebug ? '1' : '0');
ini_set('display_startup_errors', $appDebug ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Session ini options can only be changed before PHP starts a session. Some
// XAMPP installations enable session.auto_start, so guard this configuration
// to prevent warnings from being emitted into otherwise valid pages.
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', isHttpsRequest() ? '1' : '0');
}

if (!function_exists('startAppSession')) {
    function startAppSession(): void {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
