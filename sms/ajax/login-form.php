<?php

/**
 * @var string $currentLanguage
 */

try {
    require_once __DIR__ . "/../includes/ajax_protect.php";
    require_once __DIR__ . "/../includes/session.php";

    // Brute-force / lockout protection (Tier-2).
    $lockDir = __DIR__ . "/../tmp";
    if (!is_dir($lockDir)) {
        @mkdir($lockDir, 0755, true);
    }
    $clientIp = getUserIpAddress();
    $lockFile = $lockDir . "/login_" . md5($clientIp . '|' . ($_POST["email"] ?? ''));
    $now = time();
    $attempts = 0;
    if (is_file($lockFile)) {
        $data = json_decode(@file_get_contents($lockFile), true);
        if (is_array($data) && ($now - (int)($data["time"] ?? 0)) < LOGIN_LOCKOUT_SECONDS) {
            $attempts = (int)($data["count"] ?? 0);
        }
    }
    if ($attempts >= LOGIN_MAX_ATTEMPTS) {
        echo json_encode(["error" => __("error_too_many_attempts")]);
        exit;
    }

    if (!empty($_POST["email"]) && !empty($_POST["password"])) {
        $user = User::login($_POST["email"], $_POST["password"]);
        if ($user) {
            @unlink($lockFile);
            $user->setLastLogin(date('Y-m-d H:i:s'));
            $user->setLastLoginIP(getUserIpAddress());
            $user->setLanguage($currentLanguage);
            $user->save();
            $_SESSION["userID"] = $user->getID();
            $_SESSION["email"] = $user->getEmail();
            $_SESSION["name"] = $user->getName();
            $_SESSION["isAdmin"] = $user->getisAdmin();
            $_SESSION["timeZone"] = $user->getTimeZone();
            if ($user->devicesLimit > 0) {
                $totalDevices = Device::where('userID', $user->getID())->count();
                if ($totalDevices <= 0) {
                    $_SESSION["showTutorial"] = true;
                }
            }

            echo json_encode([
                "result" => true
            ]);
        } else {
            // Record failed attempt for lockout.
            file_put_contents($lockFile, json_encode(["time" => time(), "count" => $attempts + 1]));
            throw new Exception(__("error_incorrect_credentials"));
        }
    }
} catch (Throwable $t) {
    echo json_encode(array(
        'error' => $t->getMessage()
    ));
}