<?php

declare(strict_types=1);

class ReportUserHandler implements IncomingSmsHandler
{

    public function process(string $message, string $source_adress, string $dest_adress, subscription $subscription, string $transaction_id): void
    {
        global $debug_mode;
        $databaseLayer = DatabaseLayer::getInstance();

        $nickname = explode("=", $message);
        $nickname=$nickname[1];

        $data = $databaseLayer->SELECT_FROM_Profiles($nickname);
        if (mysqli_num_rows($data) == 0) {
            $notif_text=common::GetNotificationText('nick_is_correct_not_on_nick_assign', $subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number, $source_adress, $notif_text, false, $transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();

            if ($debug_mode) {
                echo("Reported User Not Subscribed");
            }

            return;
        }

        $profile =  new profile($source_adress);
        if ($profile->nickname==$nickname) {
            $notif_text=common::GetNotificationText('nick_is_correct_not_on_nick_assign', $subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number, $source_adress, $notif_text, false, $transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();

            if ($debug_mode) {
                echo("Self Report");
            }

            return;
        }

        $data = mysqli_fetch_assoc($data);
        $databaseLayer->INSERT_INTO_UserReports($data['msisdn'], $source_adress);

        $notif_text=common::GetNotificationText('on_report_accept', $subscription->lang_id);
        $notif_text = str_replace("{Nickname}", $nickname, $notif_text);
        $notif_sms = new sms(confuguration::$free_sms_short_number, $source_adress, $notif_text, false, $transaction_id);
        $notif_sms->PrepareForSend();
        $notif_sms->Send();

        if ($debug_mode) {
            echo("User Reported");
        }
    }
}
