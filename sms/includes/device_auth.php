<?php
/**
 * Device (Android APK) authentication (Tier-2 hardening).
 *
 * The original device-facing services trusted client-supplied androidId /
 * userId / groupId with NO authentication, which is an IDOR: anyone could
 * poll another device's pending messages or report statuses for them.
 *
 * Fix: every device service now requires a server-issued device token
 * (Device.token, set by services/update-token.php during sign-in). The token
 * is presented as a POST/header param `token` and is validated against the
 * device record before any data is touched. The caller receives a `$device`
 * (Device) and `$authUser` (User) on success, or a 401 JSON on failure.
 *
 * A `groupId` (from a pending Message batch) is additionally verified to
 * belong to that device.
 */

if (count(get_included_files()) == 1) {
    http_response_code(403);
    die("HTTP Error 403 - Forbidden");
}

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../vendor/autoload.php";

date_default_timezone_set(TIMEZONE);

header("Content-Type: application/json; charset=utf-8");

function deviceAuthFail(string $message): void
{
    http_response_code(401);
    echo json_encode(["success" => false, "data" => null, "error" => ["code" => 401, "message" => $message]]);
    exit;
}

// Token may arrive as POST body or X-Device-Token header.
$token = $_POST["token"] ?? ($_SERVER["HTTP_X_DEVICE_TOKEN"] ?? "");
$androidId = $_POST["androidId"] ?? "";
$userId = $_POST["userId"] ?? "";

if (!is_string($token) || $token === "" || !is_string($androidId) || $androidId === "" || !is_numeric($userId)) {
    deviceAuthFail(__("error_device_unauthorized"));
}

$device = new Device();
$device->setAndroidID($androidId);
$device->setUserID((int) $userId);
if (!$device->read()) {
    deviceAuthFail(__("error_device_not_found"));
}

// Constant-time comparison against the stored token.
$stored = $device->getToken();
if (!is_string($stored) || $stored === "" || !hash_equals($stored, $token)) {
    deviceAuthFail(__("error_device_unauthorized"));
}

if (!$device->getEnabled()) {
    deviceAuthFail(__("error_device_disabled"));
}

$authUser = $device->getUser();
if (!$authUser) {
    deviceAuthFail(__("error_device_not_found"));
}

// If a groupId is supplied (message batch), ensure it belongs to THIS device.
if (isset($_POST["groupId"]) && $_POST["groupId"] !== "") {
    $grp = MysqliDb::getInstance()
        ->where("groupID", $_POST["groupId"])
        ->where("deviceID", $device->getID())
        ->getValue("Message", "COUNT(*)");
    if (!$grp) {
        deviceAuthFail(__("error_device_unauthorized"));
    }
}
