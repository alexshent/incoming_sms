<?php

declare(strict_types=1);

class AddBlockHandler implements IncomingSmsHandler
{

    public function process(string $message, string $source_adress, string $dest_adress, subscription $subscription, string $transaction_id): void
    {
        global $debug_mode;
        $databaseLayer = DatabaseLayer::getInstance();

        $nickname = explode("=", $message);
        $nickname = trim($nickname[1]);

        $user_profile = new profile($source_adress);
        //Если осталось заполнить только ник или профиль не полный(вариант изменение ника)
        // if($user_profile->profile_status!="complete")
        if (null == $user_profile->nickname) {
            if ($subscription->lang_id==null || $subscription->lang_id==0) {
                $subscription->lang_id=2;
            }
            //Not_subscribed actualy mean not registered here. Do not ask...
            $notif_text=common::GetNotificationText('not_subscribed', $subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number, $source_adress, $notif_text, false, $transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();

            if ($debug_mode) {
                echo('BLOK= Not Complete profile'.PHP_EOL);
            }

            return;
        }

        $block_sql_data = $databaseLayer->SELECT_FROM_Profiles($nickname);
        if (mysqli_num_rows($block_sql_data) == 0) {
            $notif_text=common::GetNotificationText('nick_is_correct_not_on_nick_assign', $subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number, $source_adress, $notif_text, false, $transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();

            if ($debug_mode) {
                echo('BLOK= nickname doesnt exists'.PHP_EOL);
            }

            return;
        }

        //user wants to block hisself
        if (strtoupper($nickname)==strtoupper($user_profile->nickname)) {
            $notif_text=common::GetNotificationText('nick_is_correct_not_on_nick_assign', $subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number, $source_adress, $notif_text, false, $transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();

            if ($debug_mode) {
                echo('BLOK= self block'.PHP_EOL);
            }

            return;
        }

        $block_data = mysqli_fetch_assoc($block_sql_data);
        $user_block= user_block::construct_by_msisdn_blockmsisdn($source_adress, $block_data['msisdn']);
        if ($user_block->id==null) {
            $user_block->msisdn = $subscription->msisdn;
            $user_block->blocked_msisdn = $block_data['msisdn'];
            $user_block->blocked_profile_id= $block_data['id'];
            $user_block->blocked_nickname= $block_data['nickname'];
            $user_block->datetime_add="NOW()";
            $user_block->datetime_remove="NULL";
            $user_block->Save();

        } else {
            if ($debug_mode) {
                echo("Already blocked".PHP_EOL);
            }
        }
        /*
        $user_block =  new user_block(null,
            $subscription->msisdn,
            $block_data['msisdn'],
            $block_data['id'],
            $block_data['nickname'],
            "Now()",
            "NULL");*/

        $friend_to_delete = friend::construct_by_msisdn_friendnickname($source_adress, $nickname);
        if ($friend_to_delete->id!=null) {
            $friend_to_delete->Remove();
            if ($debug_mode) {
                echo("Remove friend after block".PHP_EOL);
            }
        }

        //RemoveFriendBySubcribeID($from_msisdn,$blockdata['id']);

        if ($debug_mode) {
            echo('BLOK= '.$source_adress.' block '.$block_data['msisdn'].PHP_EOL);
        }
    }
}
