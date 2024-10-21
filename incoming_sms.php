<?php

require_once(dirname(__FILE__) . "/../core.php");

spl_autoload_register(function ($class_name) {
    include 'incoming_sms_logic/' . $class_name . '.php';
});

set_time_limit(0);
//http://127.0.0.1/USSD_DATING/ucell_uz/handlers/incoming_sms.php?source_adress=%p&dest_adress=%P&text=%a"

//syslog(LOG_INFO,$_SERVER['QUERY_STRING']);

$databaseLayer = DatabaseLayer::getInstance();

$source_adress = substr($_GET['source_adress'], 1);
//$source_adress = $_GET['source_adress'];
$dest_adress = $_GET['dest_adress'];
$message = $_GET['text'];
$debug_mode = $_GET['debug'];

/*
if($source_adress!='998901890749' &&
   $source_adress!='998911637637' &&
   $source_adress!='998901755793' &&
   $source_adress!='998909977345' &&
   $source_adress!='998909025243' &&
   $source_adress!='998911638795' &&
   $source_adress!='998901242045' &&
   $source_adress!='998903749057' &&
   $source_adress!='998901242057' &&
   $source_adress!='998911637631' &&
   $source_adress!='998901874165' &&
   $source_adress!='998909025243'    )
{
    exit;
}*/

if (!preg_match("/^\d+$/", $source_adress)) {
    die;
}

//recieved long sms
if (isset($_GET['udh']) && $_GET['udh'] != null) {

    //We Cant normaly retrive udh from _GET , we have to use RAW URL to process this HEX representation of bits
    $udh = common::ParseKannelUDH($_SERVER['QUERY_STRING']);
    if ($udh == false) {
        log::Error($_SERVER['QUERY_STRING'], "Unable parse UDH");
        exit;
    }

    if ($udh['total_parts'] > 1) {
        //THAT ALL reasamble will be performed by other script, Run from crontab long_sms_reasamble_mtc_tk.php.
        //it will call this php once again when message will be full
        $databaseLayer->INSERT_INTO_IncomingSMS_ConcatinationBuffer($source_adress, $dest_adress, $message, $udh);
        exit;
    }
}

$transaction_id = $databaseLayer->INSERT_INTO_IncomingSMS($source_adress, $dest_adress, $message);
//General check if user is subscribed to service

//Баг в smpp 0X00 =@ но 00 зарезервирован за другим что вызывает в ряде случаев неправельную декодировку, как в нашем.
$message = str_replace("¡", "@", $message);
#$message = db_layer::encode_sql_unsafe_data($message);
//Выставляем ограничение в 700 символов в тексте
if (iconv_strlen($message) > 700) {
    $message = mb_substr($message, 0, 700, 'UTF-8');
}

try {

    $subscription = new subscription($source_adress, 100);

    if (trim($message) == "2") {
        exit;
    }

    if (strtoupper(substr($message, 0, 6)) == "STOP1") {
        /* * возможность отписать все подписки из SMSSubscriptions отключили так как это уже делается через handler в SMSSubscriptions
        *
        $send_url = "http://localhost/SMSSubscriptions/API/ApiStatusHandler.php?login=beeline_uz&password=beeline_uz_api&msisdn=".$source_adress;

        $getXML = common::RequestHTTP($send_url);

        $xml = simplexml_load_string(trim($getXML));

        $data = array();
        if($xml->status == 0)
        {
            foreach($xml->subscription as $row)
            {
                if($row->sub_status == 1)
                {
                    $data[] = json_encode(json_decode($row->service_id));
                    // print_r($row->service_id."+".$row->sub_status.PHP_EOL);
                    // syslog(LOG_INFO, $source_adress." = ".$row->service_id."+".$row->sub_status);
                }
            }
            foreach($data as $service_id)
            {
                $curl = "curl 'http://localhost/SMSSubscriptions/API/ApiDeactivation.php?login=beeline_uz&password=beeline_uz_api&msisdn=".$source_adress."&service_id=".$service_id."&deactivation_channel=SMS1&notify_user=0'";
                // syslog(LOG_INFO, $source_adress." = ".$curl);
                exec($curl);
            }
        }
        */

        if ($subscription->subscription_id != null && $subscription->status != 2) {
            stop_subscription::ProcessOperatorStop($message, $source_adress, $dest_adress, $subscription, $transaction_id);
        }
        exit;
    }

    if ($subscription->subscription_id == null || $subscription->status == 2) {
        $notif_text = common::GetNotificationText('not_subscribed', 2);
        $notif_sms = new sms(confuguration::$free_sms_short_number, $source_adress, $notif_text, false, $transaction_id);
        $notif_sms->PrepareForSend();
        $notif_sms->Send();
        if ($debug_mode) {
            echo('Incoming sms , not Subscribed' . PHP_EOL);
        }
        exit;
    }

    if (strtoupper(substr($message, 0, 5)) == "STOP" ||  trim($message) == "1") {
        //Фиксим проблему, когда в одну секунду приходят много смс на отписку
        $AllowIncomingSmsProcess = "AllowCallbackProcess";
        if (!common::$AllowIncomingSmsProcess($source_adress, 'AllowIncomingSmsProcess', 3)) {
            $databaseLayer->INSERT_INTO_Logs_Not_Allow_Incoming_Sms_Process($source_adress, $message);
            exit;
        }
        stop_subscription::ProcessAbonentStop($message, $source_adress, $dest_adress, $subscription, $transaction_id);
    }

    $access_granted = access::IsGranted($source_adress);
    if ($access_granted == false) {
        $databaseLayer->INSERT_INTO_Logs_Access_Denied($source_adress);
        exit;
    }

    //Check if not out balance
    if ($subscription->status == 3) {
        if ($subscription->lang_id == null || $subscription->lang_id == 0) {
            $subscription->lang_id = 2;
        }

        $notif_text = common::GetNotificationText('no_credit', $subscription->lang_id);
        $notif_sms = new sms(confuguration::$free_sms_short_number, $source_adress, $notif_text, false, $transaction_id);
        $notif_sms->PrepareForSend();
        $notif_sms->Send();

        if ($subscription->tarif_status == 0 || $subscription->tarif_status == 3) {

            $allow_send_tarification = common::AllowSendTarification($source_adress);

            if ($allow_send_tarification == true) {

                $subscription->tarif_status = 1;
                $subscription->Save();

                #Send tarification request
                // charge::sendTarif($source_adress);
                //charge::sendTarif($source_adress,null,null,$subscription->subscription_id);
            }
        }

        if ($debug_mode) {
            echo('No money');
        }
        exit;
    }

    if (strtoupper(substr($message, 0, 5)) === "NICK:") {
        $handler = new NickAssignHandler();
    } elseif (strtoupper(substr($message, 0, 7)) === "FRIEND=") {
        $handler = new AddFriendHandler();
    } elseif (strtoupper(substr($message, 0, 6)) === "BLOCK=") {
        $handler = new AddBlockHandler();
    } elseif (strtoupper(substr($message, 0, 7)) === "REPORT=") {
        $handler = new ReportUserHandler();
    }
    /*
    if(strtoupper(substr($message, 0, 3)) == "SMS" || mb_strtoupper(mb_substr($message,0,3,'UTF-8'), 'UTF-8') == "СМС")
    {
        sms_remaining::Process($message,$source_adress,$dest_adress,$subscription,$transaction_id);
    }
    */ else {
        $handler = new DefaultModeHandler();
    }

    $handler->process($message, $source_adress, $dest_adress, $subscription, $transaction_id);
} catch (Exception $e) {
    syslog(LOG_ERR, "ERROR_BEELINE_USSD_DATING: incomming_ussd " . $e->getMessage() . " " . $e->getline());
}
