<?php
/*
 * Copyright © 2018-2026 RBSoft (Ravi Patel). All rights reserved.
 *
 * Tier-2 hardening: device endpoints now require a server-issued device token
 * via includes/device_auth.php. The $device / $authUser objects are provided
 * by that include (the device is looked up + ownership-checked against the
 * token, so client-supplied androidId/userId can no longer be spoofed).
 */

require_once __DIR__ . "/includes/device_auth.php";

try {
    $db = MysqliDb::getInstance();
    $device->setLastSeenAt(date("Y-m-d H:i:s"));
    $device->save();

    $userId = $authUser->getID();
    $ussdRequests = Ussd::where('Ussd.responseDate', null, 'IS')
        ->where('Ussd.userID', $userId)
        ->where('Ussd.deviceID', $device->getID())
        ->orderBy('Ussd.id', 'ASC')
        ->read_all(false);

    if (isset($_POST["versionCode"]) && $_POST["versionCode"] >= 32) {
        $rows = $db->rawQuery(
            "SELECT DISTINCT groupID, prioritize, userID, sentDate FROM Message WHERE status='Pending' AND deviceID=? ORDER BY sentDate ASC",
            [$device->getID()]
        );
        $data = [];
        foreach ($rows as $row) {
            $userID = $row["userID"];
            if (isset($data[$userID])) {
                if ($row["prioritize"]) {
                    $data[$userID]["prioritizedCampaigns"][] = $row["groupID"];
                } else {
                    $data[$userID]["campaigns"][] = $row["groupID"];
                }
            } else {
                $data[$userID] = [];
                $user = new User();
                $user->setID($userID);
                $user->read();
                if ($device->getUseOwnerSettings() && $user->getID() != $device->getUserID()) {
                    $data[$userID]["user"] = $device->getUser();
                    $data[$userID]["user"]->setSleepTime($user->getSleepTime());
                } else {
                    $data[$userID]["user"] = $user;
                }
                if ($row["prioritize"]) {
                    $data[$userID]["prioritizedCampaigns"] = [$row["groupID"]];
                } else {
                    $data[$userID]["campaigns"] = [$row["groupID"]];
                }
            }
        }
        if (isset($data[$device->getUserID()])) {
            $data[$device->getUserID()]["ussdRequests"] = $ussdRequests;
        } else {
            $data[$device->getUserID()] = [
                "campaigns" => [],
                "prioritizedCampaigns" => [],
                "user" => $device->getUser(),
                "ussdRequests" => $ussdRequests
            ];
        }
        echo json_encode([
            "success" => true,
            "data" => ["userCampaigns" => array_values($data)],
            "error" => null
        ]);
        die();
    }

    $rows = $db->rawQuery(
        "SELECT DISTINCT groupID, prioritize, sentDate FROM Message WHERE status='Pending' AND deviceID=? ORDER BY sentDate ASC",
        [$device->getID()]
    );
    $normalCampaigns = [];
    $prioritizedCampaigns = [];
    foreach ($rows as $row) {
        if ($row["prioritize"]) {
            $prioritizedCampaigns[] = $row["groupID"];
        } else {
            $normalCampaigns[] = $row["groupID"];
        }
    }
    if (isset($_POST["versionCode"]) && $_POST["versionCode"] >= 26) {
        echo json_encode([
            "success" => true,
            "data" => [
                "campaigns" => $normalCampaigns,
                "prioritizedCampaigns" => $prioritizedCampaigns,
                "ussdRequests" => $ussdRequests,
                "user" => $device->getUser()
            ],
            "error" => null
        ]);
    } else {
        echo json_encode(["success" => true, "data" => ["campaigns" => array_merge($prioritizedCampaigns, $normalCampaigns), "user" => $device->getUser()], "error" => null]);
    }
} catch (Throwable $t) {
    error_log($t->getMessage());
    echo json_encode(["success" => false, "data" => null, "error" => ["code" => 500, "message" => __("error_generic")]]);
}
