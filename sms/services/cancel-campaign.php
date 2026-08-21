<?php

require_once __DIR__ . "/includes/device_auth.php";

try {
    if (isset($_POST["groupId"])) {
        // Scope cancellation to the authenticated device's own messages only.
        Message::where('groupID', $_POST["groupId"])
            ->where('deviceID', $device->getID())
            ->where('status', ['Queued', 'Pending'], 'IN')
            ->update_all(['status' => 'Canceled', 'deliveredDate' => date('Y-m-d H:i:s')]);
        echo json_encode(["success" => true, "data" => null, "error" => null]);
    } else {
        echo json_encode(["success" => false, "data" => null, "error" => ["code" => 400, "message" => __("error_invalid_request_format")]]);
    }
} catch (Throwable $t) {
    error_log($t->getMessage());
    echo json_encode(["success" => false, "data" => null, "error" => ["code" => 500, "message" => __("error_generic")]]);
}