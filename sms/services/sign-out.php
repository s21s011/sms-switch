<?php

require_once __DIR__ . "/../includes/device_auth.php";

if ($device->read()) {
    $device->setEnabled(0);
    $device->save();
}
echo json_encode(["success" => true, "data" => null, "error" => null]);