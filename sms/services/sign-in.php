<?php
/*
 * Copyright © 2018-2026 RBSoft (Ravi Patel). All rights reserved.
 *
 * Tier-2 hardening (C2): the dashboard web session is NEVER derived from a
 * device identifier. sign-in.php authenticates the device owner with their
 * password OR API key, then issues a server-side device token (stored on
 * Device.token) that the APK uses to call the /services/ endpoints. It does
 * NOT start a web session / set $_SESSION.
 *
 * Hotfix 2026-08-24: if the device row does not exist yet, it is created
 * automatically AFTER the owner's credentials verify. This lets the Android
 * app bootstrap itself (register a brand-new device) without first scanning
 * a QR code — while remaining safe because authentication happens against
 * the USER account, never against the device.
 */

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../vendor/autoload.php";
date_default_timezone_set(TIMEZONE);

if (isset($_POST["androidId"]) && isset($_POST["userId"])) {
    try {
        // 1) Authenticate the USER first (password or api key). The user id
        //    comes from POST but is only trusted after credential verification.
        $user = null;
        if (isset($_POST["password"])) {
            $candidate = new User();
            $candidate->setID((int) $_POST["userId"]);
            if ($candidate->read() && password_verify($_POST["password"], $candidate->getPassword())) {
                $user = $candidate;
            }
        } else if (isset($_POST["key"])) {
            $candidate = new User();
            $candidate->setApiKey($_POST["key"]);
            if ($candidate->read() && (int) $candidate->getID() === (int) $_POST["userId"]) {
                $user = $candidate;
            }
        }

        if (!$user) {
            echo json_encode(["success" => false, "data" => null, "error" => ["code" => 401, "message" => __("error_incorrect_credentials")]]);
            die;
        }

        // 2) Credentials valid -> find the device or auto-create it.
        $device = new Device();
        $device->setAndroidID($_POST["androidId"]);
        $device->setUserID($user->getID());
        if (!$device->read()) {
            // Enforce the per-user device limit before creating a new device.
            if ($user->isActiveDevicesLimitReached()) {
                echo json_encode(["success" => false, "data" => null, "error" => ["code" => 401, "message" => __("error_devices_limit_reached")]]);
                die;
            }
            MysqliDb::getInstance()->startTransaction();
            try {
                $device->setEnabled(1);
                $device->save();
                $deviceUser = new DeviceUser();
                $deviceUser->setDeviceID($device->getID());
                $deviceUser->setUserID($user->getID());
                $deviceUser->save(true, ['active' => 1]);
                MysqliDb::getInstance()->commit();
            } catch (Throwable $e) {
                MysqliDb::getInstance()->rollback();
                throw $e;
            }
        }

        // 3) Issue a fresh device token and persist it.
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
        error_log($t->getMessage());
        echo json_encode(["success" => false, "data" => null, "error" => ["code" => 500, "message" => __("error_generic")]]);
    }
} else {
    echo json_encode(["success" => false, "data" => null, "error" => ["code" => 400, "message" => __("error_invalid_request_format")]]);
}
