<?php
/*
 * Copyright © 2018-2026 RBSoft (Ravi Patel). All rights reserved.
 *
 * Tier-2 hardening: device endpoints now require a server-issued device token
 * via includes/device_auth.php (replaces the unauthenticated androidId/userId
 * trust). Inbound messages are scoped to the authenticated device+owner, and
 * the auto-commands (stop/unsubscribe) can only affect the owner's own data.
 */

require_once __DIR__ . "/includes/device_auth.php";

try {
    if (isset($_POST["messages"])) {
        MysqliDb::getInstance()->startTransaction();
        // $device and $authUser are provided by device_auth.php (authenticated).
        $user = $authUser;
        if ($device->read()) {
            if (Setting::get("use_credits_for_received_messages_enabled")) {
                if ($user->getCredits() !== null && $user->getCredits() <= 0) {
                    echo json_encode(["success" => true, "data" => null, "error" => null]);
                    exit();
                }
            }

            $messages = json_decode($_POST["messages"], true);
            $messageObjects = [];
            $sendMessages = [];
            if ($messages) {
                $numbers = [];
                $responses = Response::where('userID', $user->getID())
                    ->where("enabled", "1")
                    ->read_all();
                foreach ($messages as $msg) {
                    $message = new Message();
                    $message->setNumber($msg["number"]);
                    $message->setMessage($msg["message"]);
                    if (isset($msg["simSlot"]) && $msg["simSlot"] != -1) {
                        $message->setSimSlot($msg["simSlot"]);
                    }
                    $message->setDeviceID($device->getID());
                    $message->setUserID($user->getID());
                    if (isset($msg["sentDate"])) {
                        $sentDate = new DateTime($msg["sentDate"]);
                        $sentDate->setTimezone(new DateTimeZone(TIMEZONE));
                        $message->setSentDate($sentDate->format("Y-m-d H:i:s"));
                    }
                    $receivedDate = new DateTime($msg["receivedDate"]);
                    $receivedDate->setTimezone(new DateTimeZone(TIMEZONE));
                    $message->setDeliveredDate($receivedDate->format("Y-m-d H:i:s"));
                    $message->setStatus("Received");
                    $message->save();
                    $numbers[] = $message->getNumber();
                    $messageObjects[] = $message;
                    if (isValidMobileNumber($message->getNumber())) {
                        $content = strtolower(trim($message->getMessage()));
                        if ($content === "start") {
                            $entry = new Blacklist();
                            $entry->setNumber($message->getNumber());
                            $entry->setUserID($user->getID());
                            if ($entry->read()) {
                                $entry->delete();
                                $sendMessages[] = [
                                    "number" => $message->getNumber(),
                                    "simSlot" => $message->getSimSlot(),
                                    "message" => __("removed_from_blacklist", [], $user->getLanguage()),
                                ];
                            }
                        }
                        if (str_starts_with($content, "stop")) {
                            // Only the device owner may manage their own blacklist.
                            $entry = new Blacklist();
                            $entry->setNumber($message->getNumber());
                            $entry->setUserID($user->getID());
                            if (!$entry->read()) {
                                $entry->save();
                                $sendMessages[] = [
                                    "number" => $message->getNumber(),
                                    "simSlot" => $message->getSimSlot(),
                                    "message" => __("success_blacklisted", [], $user->getLanguage()),
                                    "ignoreBlacklist" => true
                                ];
                            }
                        } else if (str_starts_with($content, "unsubscribe")) {
                            $parts = explode(" ", $message->getMessage());
                            if (count($parts) === 2 && ctype_digit($parts[1])) {
                                $contact = new Contact();
                                $contact->setNumber($message->getNumber());
                                $contact->setContactsListID($parts[1]);
                                if ($contact->read()) {
                                    // Only unsubscribe from a list that belongs to this user.
                                    if ((int) $contact->getUserID() === (int) $user->getID()) {
                                        $contact->setSubscribed(false);
                                        $contact->save();
                                        $sendMessages[] = [
                                            "number" => $message->getNumber(),
                                            "simSlot" => $message->getSimSlot(),
                                            "message" => __("success_unsubscribed", [], $user->getLanguage())
                                        ];
                                    }
                                }
                            }
                        }
                        foreach ($responses as $response) {
                            $result = $response->match($message->getMessage());
                            if ($result) {
                                $sendMessages[] = [
                                    "number" => $message->getNumber(),
                                    "simSlot" => $message->getSimSlot(),
                                    "message" => $response->getResponse()
                                ];
                            }
                        }
                    }
                }

                try {
                    if (Setting::get("sms_to_email_enabled") && $user->getSmsToEmail() && !empty($messageObjects)) {
                        $contacts = $user->getContacts($numbers);
                        $receivedMessages = [];
                        $simObjects = Sim::where("deviceID", $device->getID())->read_all();
                        $sims = [];
                        foreach ($simObjects as $simObj) {
                            $sims[$simObj->getSlot()] = strval($simObj);
                        }

                        foreach ($messageObjects as $message) {
                            $receivedMessages[] = [
                                "number" => isset($contacts[$message->getNumber()]) ? $contacts[$message->getNumber()] . " ({$message->getNumber()})" : $message->getNumber(),
                                "message" => $message->getMessage(),
                                "device" => strval($device),
                                "sim" => $message->getSimSlot() != null ? " ({$sims[$message->getSimSlot()]})" : "",
                                "status" => "Received"
                            ];
                        }

                        $admin = User::getAdmin();
                        $from = array($admin->getEmail(), $admin->getName());
                        $to = [
                            $user->getReceivedSmsEmail() ?: $user->getEmail(),
                            $user->getName()
                        ];
                        foreach ($receivedMessages as $receivedMessage) {
                            Job::queue("sendEmail", [$from, $to, "Message from {$receivedMessage['number']} on {$receivedMessage['device']} ({$receivedMessage['sim']})", $receivedMessage["message"]]);
                        }
                    }
                } catch (Exception $t) {
                    error_log($t->getMessage());
                }
                if (Setting::get("use_credits_for_received_messages_enabled")) {
                    $user->depleteCredits(count($messageObjects));
                }
            }
            MysqliDb::getInstance()->commit();
            try {
                if (!empty($sendMessages)) {
                    Message::sendMessages($sendMessages, $user, [$device->getID()], null, 1);
                }
            } catch (Exception $exception) {
                error_log($exception);
            }
            if (!empty($messageObjects)) {
                $user->callWebhook('messages', $messageObjects);
            }
            echo json_encode(["success" => true, "data" => null, "error" => null]);
        } else {
            echo json_encode(["success" => false, "data" => null, "error" => ["code" => 401, "message" => __("error_device_not_found")]]);
        }
    } else {
        throw new Exception(__("error_invalid_request_format"));
    }
} catch (Throwable $t) {
    echo json_encode(["success" => false, "data" => null, "error" => ["code" => 500, "message" => $t->getMessage()]]);
}
