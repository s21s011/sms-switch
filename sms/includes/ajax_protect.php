<?php
/*
 * Copyright © 2018-2026 RBSoft (Ravi Patel). All rights reserved.
 *
 * Tier-2 hardening: replaced the spoofable X-Requested-With header check with
 * a real CSRF token validation (synchronizer pattern). The X-Requested-With
 * header check is still applied as defense-in-depth but is NOT sufficient on
 * its own.
 */

if (count(get_included_files()) == 1) {
    http_response_code(403);
    die("HTTP Error 403 - Forbidden");
}

require_once __DIR__ . "/csrf.php";

// The login endpoint is the authentication entry point: it cannot carry a
// CSRF token yet and relies on the brute-force lockout instead. Exempt it.
$isLogin = str_ends_with($_SERVER['SCRIPT_NAME'] ?? '', 'login-form.php')
    || str_ends_with($_SERVER['PHP_SELF'] ?? '', 'login-form.php');

// Reject direct (non-AJAX) document requests to PHP endpoints.
if (!$isLogin && (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest')) {
    // Allow GET reads (e.g. data endpoints used via direct navigation in some setups),
    // but any state-changing request must carry a CSRF token.
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(403);
        die("HTTP Error 403 - Forbidden");
    }
}

// Validate CSRF token on every state-changing request (except the login entry).
if (!$isLogin && ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'DELETE')) {
    if (!csrf_validate()) {
        http_response_code(403);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(["error" => "CSRF validation failed"]);
        exit;
    }
}
