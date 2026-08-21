<?php
/*
 * Copyright © 2018-2026 RBSoft (Ravi Patel). All rights reserved.
 *
 * Tier-2 hardening: session cookie now sets Secure + HttpOnly + SameSite and
 * enforces an idle timeout. APP_SECRET_KEY comes from environment (config.php).
 */

if (count(get_included_files()) == 1) {
    http_response_code(403);
    die("HTTP Error 403 - Forbidden");
}

$timeout = 86400;
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../vendor/autoload.php";
date_default_timezone_set(TIMEZONE);
session_cache_limiter('nocache');

$isHttps = (defined("FORCE_HTTPS") && FORCE_HTTPS) || (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on');
session_set_cookie_params([
    'lifetime' => $timeout,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax'
]);
$JWTSession = new ravibpatel\JWTSession\JWTSession($timeout, APP_SECRET_KEY, false, APP_SESSION_NAME);
$JWTSession->setSessionHandler(true);

require_once __DIR__ . "/initialize.php";

// Initialize CSRF token for the session.
require_once __DIR__ . "/csrf.php";
if (session_status() === PHP_SESSION_ACTIVE) {
    csrf_token();
}

// Enforce idle timeout (30 minutes).
$idleLimit = 1800;
if (isset($_SESSION['userID'])) {
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - (int) $_SESSION['LAST_ACTIVITY']) > $idleLimit) {
        require_once __DIR__ . "/../logout.php";
        exit();
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}
