<?php

require_once __DIR__ . "/../includes/device_auth.php";

try {
    if (isset($_POST["messages"])) {
        $messages = json_decode($_POST["messages"], true);
        if (is_array($messages) && count($messages) > 0) {
            MysqliDb::getInstance()->startTransaction();
            foreach ($messages as $message) {
                if (isset($message["ID"]) && isset($message["status"])) {
                    $obj = new Message();
                    $obj->setID($message["ID"]);
                    if ($obj->read()) {
                        // Ownership check: the message must belong to this device/user.
                        if ((int) $obj->getUserID() !== (int) $authUser->getID() ||
                            (int) $obj->getDeviceID() !== (int) $device->getID()) {
                            throw new Exception(__("error_device_unauthorized"));
                        }
                        $obj->setStatus($message["status"]);
                        if (isset($message["deliveredDate"])) {
                            $time = new DateTime($message["deliveredDate"]);
                            $time->setTimezone(new DateTimeZone(TIMEZONE));
                            $obj->setDeliveredDate($time->format("Y-m-d H:i:s"));
                        }
                        if (isset($message["resultCode"])) {
                            $obj->setResultCode($message["resultCode"]);
                        }
                        if (isset($message["errorCode"])) {
                            $obj->setErrorCode($message["errorCode"]);
                        }
                        if (array_key_exists("simSlot", $message)) {
                            $obj->setSimSlot($message["simSlot"]);
                        }
                        $obj->save();
                    }
                } else {
                    throw new Exception(__("error_invalid_request_format"));
                }
            }
            /* Uncomment this block if you want to send webhook on message status change.
            $messageObjects = [];
            foreach ($messages as $message) {
                $obj = new Message();
                $obj->setID($message["ID"]);
                $obj->read();
                $messageObjects[$obj->userID] = $message;
            }
            foreach ($messageObjects as $userID => $data) {
                $user = new User();
                $user->setID($userID);
                if ($user->read()) {
                    $user->callWebhook('messages', $data);
                }
            }
            */
            MysqliDb::getInstance()->commit();
        } else {
            throw new Exception(__("error_invalid_request_format"));
        }
        echo json_encode(["success" => true, "data" => null, "error" => null]);
    }
} catch (Throwable $t) {
    error_log($t->getMessage());
    echo json_encode(["success" => false, "data" => null, "error" => ["code" => 500, "message" => __("error_generic")]]);
}