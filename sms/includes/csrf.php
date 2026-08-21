<?php
/**
 * CSRF protection helpers (Tier-2 hardening).
 *
 * Generates and validates a per-session synchronizer token. Web pages embed
 * the token in every state-changing form; ajax_protect.php validates it on
 * every POST. The previous X_REQUESTED_WITH header check was spoofable and is
 * NOT sufficient CSRF protection.
 */

if (!function_exists('csrf_token')) {
    /**
     * Returns the current CSRF token, generating one if needed.
     */
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Emits a hidden input field carrying the CSRF token.
     */
    function csrf_field(): void
    {
        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
    }
}

if (!function_exists('csrf_meta')) {
    /**
     * Emits a <meta> tag + JS global so XHR/Fetch calls can attach the token.
     */
    function csrf_meta(): void
    {
        echo '<meta name="csrf-token" content="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
    }
}

if (!function_exists('csrf_validate')) {
    /**
     * Validates the submitted CSRF token. Returns true only when the posted
     * token is present and matches the session token in a timing-safe way.
     */
    function csrf_validate(): bool
    {
        if (empty($_SESSION['csrf_token'])) {
            return false;
        }
        $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!is_string($sent) || $sent === '') {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $sent);
    }
}
