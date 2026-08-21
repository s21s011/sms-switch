<?php

require_once __DIR__ . "/../includes/device_auth.php";

try {
    $device->setToken($_POST["token"] ?? null);
    $device->setEnabled(1);
    $device->save(false);
    $response = ["success" => true, "data" => ["device" => $device], "error" => null];
    echo json_encode($response);
} catch (Throwable $t) {
    echo json_encode(["success" => false, "data" => null, "error" => ["code" => 500, "message" => __("error_generic")]]);
}