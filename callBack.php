<?php

require_once(dirname(__FILE__) . "/../core.php");

$response = new APISCSLog();
$response->msisdn = $_GET['msisdn'];
$response->service_id = $_GET['serviceID'];
$response->providerID = $_GET['providerID'];
$response->activationChannel = $_GET['activationChannel'];
$response->deactivationChannel = $_GET['deactivationChannel'];
$response->eventType = $_GET['eventType'];
$response->transactionResultCode = $_GET['transactionResultCode'];
$response->transactionID = $_GET['transactionID'];
$response->subscriptionID = !empty($_GET['subscriptionID']) ? $_GET['subscriptionID'] : 'NULL';
$response->subscriptionStatus = $_GET['subscriptionStatus'];
$response->langID = 1; // $_GET['langID']; // Always 2
$response->trialEndDate = $_GET['trialEndDate'];
$response->paidUntil = $_GET['paidUntil'];
$response->oldMsisdn = !empty($_GET['args']['oldMsisdn']) ? $_GET['args']['oldMsisdn'] : 'NULL';
$response->datetime = date("Y-m-d H:i:s");
$response->url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

$cpData = isset($_GET['args']['CPData']) ? json_decode($_GET['args']['CPData'], true) : null;

if (!empty($cpData)) {
    $response->langID = $cpData['lang_id'];
}

if (!isset($response->msisdn)) {
    die('NO MSISDN');
}

if (!isset($response->eventType)) {
    die('NO ACTION');
}

$response->insertResponse();



switch ($response->eventType) {
    case 'SUBSCRIBE':
        SubscriptionController::subscribeCallBack($response);
        break;

    case 'UNSUBSCRIBE':
        SubscriptionController::unsubscribeCallBack($response);
        break;

    case 'TERMINATION':
        // if ($response->msisdn == '998915486027') {
            SubscriptionController::unsubscribeCallBack($response, true);
        // }
        break;

    case 'CHARGE':
        SubscriptionController::chargeCallBack($response);
        break;

    case 'MSISDN_CHANGE':
        SubscriptionController::changeMsisdn($response);
        break;

    case 'UPDATE_STATUS':
        SubscriptionController::updateStatus($response);
        break;

    default:
        SubscriptionController::syncCallBack($response, new subscription($response->msisdn, confuguration::$serviceID));
        // Логируем
        break;
}
