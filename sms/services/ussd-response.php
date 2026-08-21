<?php

require_once __DIR__ . "/../includes/device_auth.php";

try {
    if (isset($_POST["ussdId"]) && isset($_POST["response"])) {
        $ussd = new Ussd();
        $ussd->setID($_POST["ussdId"]);
        $ussd->setUserID($authUser->getID());
        $ussd->setDeviceID($device->getID());
        if ($ussd->read(false)) {
            $ussd->setResponse($_POST["response"]);
            $ussd->setResponseDate(date("Y-m-d H:i:s"));
            $ussd->save();

            $device->getUser()->callWebhook('ussdRequest', $ussd);
            echo json_encode(["success" => true, "data" => null, "error" => null]);
        } else {
            echo json_encode(["success" => false, "data" => null, "error" => ["code" => 401, "message" => __("error_device_not_found")]]);
        }
    } else {
        throw new Exception(__("error_invalid_request_format"));
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(["success" => false, "data" => null, "error" => ["code" => 500, "message" => __("error_generic")]]);
}
