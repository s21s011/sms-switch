<?php

try {
    require_once __DIR__ . "/includes/login.php";

    $start_date = empty($_REQUEST["startDate"]) ? null : $_REQUEST["startDate"];
    $end_date = empty($_REQUEST["endDate"]) ? null : $_REQUEST["endDate"];
    require_once __DIR__ . "/includes/search.php";

    /** @var array<int, Message> $messages */
    if (count($messages) > 0) {
        if (isset($start_date)) {
            if (isset($end_date)) {
                $name = "Messages_{$start_date}_{$end_date}.csv";
            } else {
                $now = (new DateTime())->format('Y-m-d');
                $name = "Messages_{$start_date}_{$now}.csv";
            }
        } else {
            $name = "Messages.csv";
        }
        objectsToExcel($messages, $name, ["number" => __("mobile_number"), "message" => __("message"), "status" => __("status"), "sentDate" => __("sent_date"), "deliveredDate" => __("delivered_date")], array("userID", "deviceID", "ID", "groupID", "resultCode", "errorCode", "retries", "expiryDate"));
    } else {
        header("location:messages.php?" . $_SERVER['QUERY_STRING']);
    }
} catch (Exception $e) {
    echo json_encode(array(
        "error" => $e->getMessage()
    ));
}

