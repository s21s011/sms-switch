<?php
/*
 * Copyright © 2018-2026 RBSoft (Ravi Patel). All rights reserved.
 *
 * Tier-2 hardening (C2): the dashboard web session is NEVER derived from a
 * device identifier. sign-in.php now authenticates the device owner with
 * their password OR API key, then issues a server-side device token (stored
 * on Device.token) that the APK uses to call the /services/ endpoints. It does
 * NOT start a web session / set $_SESSION.
 */

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../vendor/autoload.php";
date_default_timezone_set(TIMEZONE);

if (isset($_POST["androidId"]) && isset($_POST["userId"])) {
    try {
        $device = new Device();
        $device->setAndroidID($_POST["androidId"]);
        $device->setUserID((int) $_POST["userId"]);
        if (!$device->read()) {
            echo json_encode(["success" => false, "data" => null, "error" => ["code" => 401, "message" => __("error_device_not_found")]]);
            die;
        }

        // Authenticate the device owner (password OR api key) — never device-id alone.
        $user = null;
        if (isset($_POST["password"])) {
            $candidate = new User();
            $candidate->setEmail($device->getUser()->getEmail());
            if ($candidate->read() && password_verify($_POST["password"], $candidate->getPassword())) {
                $user = $candidate;
            }
        } else if (isset($_POST["key"])) {
            $candidate = new User();
            $candidate->setApiKey($_POST["key"]);
            $candidate = $candidate->read();
            if ($candidate && (int) $candidate->getID() === (int) $device->getUserID()) {
                $user = $candidate;
            }
        }

        if (!$user) {
            echo json_encode(["success" => false, "data" => null, "error" => ["code" => 401, "message" => __("error_incorrect_credentials")]]);
            die;
        }

        // Issue a fresh device token and persist it.
        $token = bin2hex(random_bytes(32));
        $device->setToken($token);
        $device->setEnabled(1);
        if (isset($_POST["sims"])) {
            $device->saveSims(json_decode($_POST["sims"]));
        }
        $device->save();

        $response = [
            "success" => true,
            "data" => [
                "token" => $token,
                "device" => $device
            ],
            "error" => null
        ];
        echo json_encode($response);
        die;
    } catch (Throwable $t) {
        echo json_encode(["success" => false, "data" => null, "error" => ["code" => 500, "message" => __("error_generic")]]);
    }
} else {
    echo json_encode(["success" => false, "data" => null, "error" => ["code" => 400, "message" => __("error_invalid_request_format")]]);
}
