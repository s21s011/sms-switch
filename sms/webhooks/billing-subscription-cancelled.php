<?php

try {
    require_once __DIR__ . "/../config.php";
    require_once __DIR__ . "/../vendor/autoload.php";
    date_default_timezone_set(TIMEZONE);

    // Get the body of the webhook event
    $body = json_decode(file_get_contents('php://input'));
    //file_put_contents(__DIR__ . "/subscription-logs.txt", json_encode($body, JSON_PRETTY_PRINT), FILE_APPEND);

    // Get the necessary parameters from the $_SERVER super global
    $transmissionId = $_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'];
    $transmissionSig = $_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'];
    $transmissionTime = $_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME'];
    $authAlgo = $_SERVER['HTTP_PAYPAL_AUTH_ALGO'];
    $certUrl = $_SERVER['HTTP_PAYPAL_CERT_URL'];

    // Get the webhook id
    $webhookId = Setting::get("paypal_subscription_webhook_id");
    if (empty($webhookId)) {
        $currentUrl = "https://{$_SERVER["HTTP_HOST"]}{$_SERVER["REQUEST_URI"]}";
        $webhookId = PayPal::getWebHookId($currentUrl, $body->event_type);
        Setting::apply(["paypal_subscription_webhook_id" => $webhookId]);
    }

    // Verify the webhook signature
    $isValid = PayPal::verifyWebhookSignature($transmissionId, $transmissionSig, $transmissionTime, $authAlgo, $certUrl, $webhookId, $body);

    if (! $isValid) {
        error_log("Webhook signature verification failed");
        http_response_code(400);
        echo json_encode([
            "message" => "Webhook signature verification failed"
        ]);
        exit();
    }

    $subscription = new Subscription();
    $subscription->setSubscriptionID($body->resource->id);
    if ($subscription->read()) {
        MysqliDb::getInstance()->startTransaction();

        /*
        $cycle_executions = $body->resource->billing_info->cycle_executions;
        foreach ($cycle_executions as $cycle_execution) {
            if ($cycle_execution->tenure_type !== "REGULAR") {
                continue;
            }
            if ($subscription->getCyclesCompleted() > $cycle_execution->cycles_completed) {
                $user = new User();
                $user->setID($subscription->getUserID());
                $extraCycles = $subscription->getCyclesCompleted() - $cycle_execution->cycles_completed;
                $expiryDate = date("Y-m-d H:i:s", $subscription->getExpiryDate()->getTimestamp() - ($extraCycles * $subscription->getPlan()->getFrequencyInSeconds()));
                $user->setExpiryDate($expiryDate);
                $user->save();
                $subscription->setCyclesCompleted($cycle_execution->cycles_completed);
                $subscription->setExpiryDate($expiryDate);
            }
            break;
        }
        */

        $objects = Payment::where("Payment.subscriptionID", $subscription->getID())->read_all();
        $payments = [];
        foreach ($objects as $object) {
            $payments[$object->getTransactionID()] = $object;
        }
        $startTime = $subscription->getSubscribedDate()->setTimezone(new DateTimeZone("UTC"));
        $endTime = new DateTime("now", new DateTimeZone("UTC"));
        $transactions = PayPal::getSubscriptionTransactions($subscription->getSubscriptionID(), $startTime, $endTime);
        $completedTransactions = 0;
        if (empty($transactions)) {
            foreach ($payments as $payment) {
                if ($payment->getStatus() === "COMPLETED") {
                    $completedTransactions++;
                }
            }
        } else {
            foreach ($transactions as $transaction) {
                if ($transaction->status === "COMPLETED") {
                    $completedTransactions++;
                }
                if (isset($payments[$transaction->id])) {
                    $payment = $payments[$transaction->id];
                    if ($payment->getStatus() !== $transaction->status) {
                        $payment->setStatus($transaction->status);
                        $payment->save();
                    }
                } else {
                    $payment = new Payment();
                    $payment->setTransactionID($transaction->id);
                    $payment->setSubscriptionID($subscription->getID());
                    $payment->setUserID($subscription->getUserID());
                    $payment->setAmount($transaction->amount_with_breakdown->gross_amount->value);
                    $payment->setStatus($transaction->status);
                    $payment->setTransactionFee($transaction->amount_with_breakdown->fee_amount->value);
                    $payment->setCurrency($transaction->amount_with_breakdown->gross_amount->currency_code);
                    $payment->setDateAdded(date("Y-m-d H:i:s", strtotime($transaction->time)));
                    $payment->save();
                }
            }
        }

        if ($subscription->getCyclesCompleted() > $completedTransactions) {
            $user = new User();
            $user->setID($subscription->getUserID());
            $extraCycles = $subscription->getCyclesCompleted() - $completedTransactions;
            $expiryDate = date("Y-m-d H:i:s", $subscription->getExpiryDate()->getTimestamp() - ($extraCycles * $subscription->getPlan()->getFrequencyInSeconds()));
            $user->setExpiryDate($expiryDate);
            $user->save();
            $subscription->setCyclesCompleted($completedTransactions);
            $subscription->setExpiryDate($expiryDate);
        }

        if ($body->resource->status != 'CANCELLED') {
            $subscription->cancel("Payment failed");
        } else {
            $subscription->setStatus($body->resource->status);
            $subscription->save();
        }
        MysqliDb::getInstance()->commit();
    } else {
        error_log("Subscription id '{$body->resource->id}' doesn't exist in database!");
    }
} catch (Throwable $t) {
    error_log($t->getMessage());
    http_response_code(500);
    echo json_encode([
        "message" => $t->getMessage()
    ]);
}