<?php
/**
 * Configuration loader (Tier-2 hardening).
 *
 * Secrets live in a .env file OUTSIDE the webroot (or in environment variables
 * supplied by the host). config.php reads them via getenv() and falls back to
 * safe placeholders ONLY when the values are missing, so the app still boots
 * in development. In production you MUST provide real values through .env.
 *
 * Never commit real secrets. .env is git-ignored.
 */

// Load .env if present (silent if missing).
$__envFile = __DIR__ . '/.env';
if (is_readable($__envFile)) {
    $__lines = @file($__envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($__lines) {
        foreach ($__lines as $__line) {
            $__line = trim($__line);
            if ($__line === '' || str_starts_with($__line, '#')) {
                continue;
            }
            if (!str_contains($__line, '=')) {
                continue;
            }
            [$__k, $__v] = explode('=', $__line, 2);
            $__k = trim($__k);
            $__v = trim($__v);
            // strip surrounding quotes
            if ((str_starts_with($__v, '"') && str_ends_with($__v, '"')) ||
                (str_starts_with($__v, "'") && str_ends_with($__v, "'"))) {
                $__v = substr($__v, 1, -1);
            }
            if (!array_key_exists($__k, $_ENV) && getenv($__k) === false) {
                putenv("{$__k}={$__v}");
                $_ENV[$__k] = $__v;
            }
        }
    }
}

function env_or(string $key, string $default = ''): string
{
    $v = getenv($key);
    if ($v === false || $v === '') {
        return $default;
    }
    return $v;
}

// Allowed Host header values (defense against Host-header injection).
// Leave empty to allow the configured server name only (recommended).
define('ALLOWED_HOSTS', env_or('ALLOWED_HOSTS', ''));

define('DB_SERVER', env_or('DB_SERVER', 'localhost'));
define('DB_PORT', env_or('DB_PORT', '3306'));
define('DB_USER', env_or('DB_USER', 'ismsgateway_pro'));
define('DB_PASS', env_or('DB_PASS', ''));
define('DB_NAME', env_or('DB_NAME', 'ismsgateway_pro'));
define('TIMEZONE', env_or('TIMEZONE', 'UTC'));
define('APP_SECRET_KEY', env_or('APP_SECRET_KEY', ''));
define('APP_SESSION_NAME', env_or('APP_SESSION_NAME', 'SMS_GATEWAY'));

// Force HTTPS links even if the proxy doesn't set forwarded headers correctly.
define('FORCE_HTTPS', filter_var(env_or('FORCE_HTTPS', 'false'), FILTER_VALIDATE_BOOLEAN));

// Comma-separated list of trusted proxy IPs allowed to set X-Forwarded-* headers.
define('TRUSTED_PROXIES', env_or('TRUSTED_PROXIES', ''));

// Login brute-force protection.
define('LOGIN_MAX_ATTEMPTS', (int) env_or('LOGIN_MAX_ATTEMPTS', '5'));
define('LOGIN_LOCKOUT_SECONDS', (int) env_or('LOGIN_LOCKOUT_SECONDS', '900'));

// Security: APP_SECRET_KEY must be set in .env for production
if (APP_SECRET_KEY === '' && (defined('APP_ENV') && APP_ENV === 'production')) {
    trigger_error('APP_SECRET_KEY is not set in .env — required for production', E_USER_WARNING);
}
