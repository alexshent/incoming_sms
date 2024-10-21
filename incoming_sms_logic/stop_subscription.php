<?php

/**
 * Created by PhpStorm.
 * User: Alexandr
 * Date: 1/20/15
 * Time: 7:50 AM
 */

class stop_subscription
{
    public static function ProcessAbonentStop($message, $source_adress, $dest_adress, $subscription, $transaction_id)
    {
        if (!SubscriptionController::onUnsubscribe($source_adress, 'USSD')) {
            syslog(LOG_WARNING, "ProcessAbonentStop fail " . $source_adress);
        }
        exit;

        $profile = new profile($source_adress);
        $debug_mode = false;
        $subscription->status = 2;
        $subscription->date_stopped = "NOW()";
        $subscription->deactivation_channel = "SMS";
        $subscription->Save();

        if ($subscription->lang_id == null || $subscription->lang_id == 0) {
            $subscription->lang_id = 2;
        }
        $notif_text = common::GetNotificationText('on_unsubscribe', $subscription->lang_id);
        $notif_sms = new sms(confuguration::$free_sms_short_number, $source_adress, $notif_text, false, $transaction_id);
        $notif_sms->PrepareForSend();
        $notif_sms->Send();


        if ($profile->profile_status == "complete") {
            $query = "SELECT text FROM Descriptions WHERE parameter = 'sex' AND parameter_id=" . $profile->sex . " AND lang_id = 1 limit 1";
            $sqlData = db_layer::execute($query, "Get Profile Description stop");
            $sex = mysqli_fetch_assoc($sqlData);

            $query = "SELECT text FROM Descriptions WHERE parameter = 'sex' AND parameter_id=" . $profile->search_for . " AND lang_id = 1 limit 1";
            $sqlData = db_layer::execute($query, "Get Profile Description stop");
            $search_for = mysqli_fetch_assoc($sqlData);

            $query = "SELECT text FROM Descriptions WHERE parameter = 'type_of_relation' AND parameter_id=" . $profile->type_of_relation . " AND lang_id = 1 limit 1";
            $sqlData = db_layer::execute($query, "Get Profile Description stop");
            $type_of_relation = mysqli_fetch_assoc($sqlData);

            $query = "SELECT text FROM Descriptions WHERE parameter = 'region' AND parameter_id= " . $profile->region . " AND lang_id =1 limit 1";
            $sqlData = db_layer::execute($query, "Get Profile Description stop");
            $region = mysqli_fetch_assoc($sqlData);




            $query = "INSERT INTO SORMArchiveProfilesData (datetime,subscription_id,msisdn,nickname,sex,age,search_for,type_of_relation,region,datetime_start,datetime_nick_assign,datetime_stop)
						  VALUES (NOW(), " . $subscription->subscription_id . ","
                . $subscription->msisdn . ",'"
                . db_layer::encode_sql_unsafe_data($profile->nickname) . "','"
                . $sex['text'] . "','"
                . $profile->age . "','"
                . $search_for['text'] . "','"
                . $type_of_relation['text'] . "','"
                . $region['text'] . "','"
                . $subscription->date_started . "','"
                . $profile->datetime_nick_assign . "',NOW());";

            db_layer::execute($query, "Log Profile Data to SORMArchiveProfilesData ProcessAbonentStop");
        }
        $query = "DELETE FROM UserFriends WHERE friend_msisdn=" . $source_adress . " OR msisdn=" . $source_adress;
        db_layer::execute($query, "DELETE Friends after unsubscribe");

        $query = "DELETE FROM Profiles WHERE msisdn=" . $source_adress;
        db_layer::execute($query, "DELETE profile after unsubscribe");

        $query = "DELETE FROM UserBlackList WHERE msisdn=" . $source_adress . " OR blocked_msisdn=" . $source_adress;
        db_layer::execute($query, "DELETE UserBlackList after unsubscribe");
        if ($debug_mode) {
            echo ("Unsubscribe By abonent");
        }
        exit;
    }
    public static function ProcessOperatorStop($message, $source_adress, $dest_adress, $subscription, $transaction_id)
    {
        if (!SubscriptionController::onUnsubscribe($source_adress, 'USSD')) {
            syslog(LOG_WARNING, "ProcessAbonentStop fail " . $source_adress);
        }
        exit;

        if (in_array($source_adress, confuguration::$testMsisdns)) {
            if (!SubscriptionController::onUnsubscribe($source_adress, 'USSD')) {
                syslog(LOG_WARNING, "ProcessAbonentStop fail " . $source_adress);
            }
            exit;
        }

        $profile = new profile($source_adress);
        $debug_mode = false;
        $subscription->status = 2;
        $subscription->date_stopped = "NOW()";
        $subscription->deactivation_channel = "SMS1";
        $subscription->Save();

        if ($profile->profile_status == "complete") {

            $query = "SELECT text FROM Descriptions WHERE parameter = 'sex' AND parameter_id=" . $profile->sex . " AND lang_id = 1 limit 1";
            $sqlData = db_layer::execute($query, "Get Profile Description stop1");
            $sex = mysqli_fetch_assoc($sqlData);

            $query = "SELECT text FROM Descriptions WHERE parameter = 'sex' AND parameter_id=" . $profile->search_for . " AND lang_id = 1 limit 1";
            $sqlData = db_layer::execute($query, "Get Profile Description stop1");
            $search_for = mysqli_fetch_assoc($sqlData);

            $query = "SELECT text FROM Descriptions WHERE parameter = 'type_of_relation' AND parameter_id=" . $profile->type_of_relation . " AND lang_id = 1 limit 1";
            $sqlData = db_layer::execute($query, "Get Profile Description stop1");
            $type_of_relation = mysqli_fetch_assoc($sqlData);

            $query = "SELECT text FROM Descriptions WHERE parameter = 'region' AND parameter_id= " . $profile->region . " AND lang_id =1 limit 1";
            $sqlData = db_layer::execute($query, "Get Profile Description stop1");
            $region = mysqli_fetch_assoc($sqlData);




            $query = "INSERT INTO SORMArchiveProfilesData (datetime,subscription_id,msisdn,nickname,sex,age,search_for,type_of_relation,region,datetime_start,datetime_nick_assign,datetime_stop)
						  VALUES (NOW(), " . $subscription->subscription_id . ","
                . $subscription->msisdn . ",'"
                . db_layer::encode_sql_unsafe_data($profile->nickname) . "','"
                . $sex['text'] . "',"
                . $profile->age . ",'"
                . $search_for['text'] . "','"
                . $type_of_relation['text'] . "','"
                . $region['text'] . "','"
                . $subscription->date_started . "','"
                . $profile->datetime_nick_assign . "',NOW());";

            db_layer::execute($query, "Log Profile Data to SORMArchiveProfilesData ProcessOperatorStop");
        }
        $query = "DELETE FROM UserFriends WHERE friend_msisdn=" . $source_adress . " OR msisdn=" . $source_adress;
        db_layer::execute($query, "DELETE Friends after unsubscribe");

        $query = "DELETE FROM Profiles WHERE msisdn=" . $source_adress;
        db_layer::execute($query, "DELETE profile after unsubscribe");


        $query = "DELETE FROM UserBlackList WHERE msisdn=" . $source_adress . " OR blocked_msisdn=" . $source_adress;
        db_layer::execute($query, "DELETE UserBlackList after unsubscribe");

        if ($debug_mode) {
            echo ("Unsubscribe By operator");
        }
        exit;
    }
}
