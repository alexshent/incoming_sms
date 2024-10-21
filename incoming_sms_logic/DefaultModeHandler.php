<?php

declare(strict_types=1);

class DefaultModeHandler implements IncomingSmsHandler
{

    // Default mode - send sms between two users. Format : Nick, message
    public function process(string $message, string $source_adress, string $dest_adress, subscription $subscription, string $transaction_id): void
    {
        global $debug_mode;
        $databaseLayer = DatabaseLayer::getInstance();

        if ($subscription->lang_id==null || $subscription->lang_id==0) {
            $subscription->lang_id=2;
        }

        $user_profile = new profile($source_adress);
        if (null == $user_profile->nickname) {
            $notif_text=common::GetNotificationText('not_subscribed', 1);
            $notif_sms = new sms(confuguration::$free_sms_short_number, $source_adress, $notif_text, false, $transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();

            if ($debug_mode) {
                echo('Not complete profile'.PHP_EOL);
            }

            return;
        }

        $items = explode(',', $message);

        $nickname = trim($items[0]);
        if (0 === strlen($nickname)) {
            $notif_text=common::GetNotificationText('on_error', $subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number, $source_adress, $notif_text, false, $transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();

            if ($debug_mode) {
                echo('Redirect sms wrong nickname '.PHP_EOL);
            }

            return;
        }

        if (!isset($items[1])) {
            $notif_text=common::GetNotificationText('on_error', $subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number, $source_adress, $notif_text, false, $transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();

            if ($debug_mode) {
                echo('Empty message'.PHP_EOL);
            }

            return;
        }

        unset($items[0]);

        $message_part = implode(',', $items);

        $recipient_data = $databaseLayer->SELECT_FROM_Profiles($nickname);

        if (0 == mysqli_num_rows($recipient_data)) {
            $notif_text=common::GetNotificationText('nick_is_correct_not_on_nick_assign', $subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number, $source_adress, $notif_text, false, $transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();

            if ($debug_mode) {
                echo('Redirect sms user do not exists '.PHP_EOL);
            }

            return;
        }

        //nick is blocked by reciepent
        $recipient_data = mysqli_fetch_assoc($recipient_data);
        $block_data_sender = user_block::construct_by_msisdn_blockmsisdn($source_adress, $recipient_data['msisdn']);
        $block_data_recipient = user_block::construct_by_msisdn_blockmsisdn($recipient_data['msisdn'], $source_adress);

        if ($block_data_recipient->id != null || $block_data_sender->id != null) {
            $notif_text=common::GetNotificationText('user_is_blocked', $subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number, $source_adress, $notif_text, false, $transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();

            if ($debug_mode) {
                echo('User is blocked'.PHP_EOL);
            }

            return;
        }

        // from = to
        if ($source_adress == $recipient_data['msisdn']) {
            $notif_text=common::GetNotificationText('nick_is_correct_not_on_nick_assign', $subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number, $source_adress, $notif_text, false, $transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();

            if ($debug_mode) {
                echo('Redirect self send'.PHP_EOL);
            }

            return;
        }

        // check if sender is the owner of the number from the message body
        $numberFromMessageBody = $this->parseNumberFromMessage($message_part);
        if (null !== $numberFromMessageBody && !$this->isNumberOwner($numberFromMessageBody, $user_profile)) {
            if ($debug_mode) {
                echo('Sender is not the owner of the number from the message body'.PHP_EOL);
            }

            return;
        }

        $amount_data = $databaseLayer->SELECT_SUM_Amount($source_adress);

        /*
        $query = "SELECT IFNULL(SUM(reorder),0) as amount FROM ReorderLog WHERE msisdn=".$source_adress." AND reorder=1 ";
        $amount_sql_data_reorder =  db_layer::execute($query,"Get ReorderSuccessAmount");
        $amount_data_reorder = mysqli_fetch_assoc($amount_sql_data_reorder);


        //Закончился стандартный пакет 50 смс в день
        if($amount_data['amount']==49 && $amount_data_reorder['amount']==0 && $subscription->trial_days==0)
        {
            $notif_text=common::GetNotificationText('on_sms_limit_50',$subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number,$source_adress,$notif_text,false,$transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();
        }
        //закончился один из дополнительных пакетов
        if($amount_data['amount']==(49+$amount_data_reorder['amount']*10) &&  $amount_data['amount']>49 && $subscription->trial_days==0)
        {
                $notif_text=common::GetNotificationText('on_sms_limit_10_reorder',$subscription->lang_id);
                $notif_sms = new sms(confuguration::$free_sms_short_number,$source_adress,$notif_text,false,$transaction_id);
                $notif_sms->PrepareForSend();
                $notif_sms->Send();

        }
        //нужно выслать реордер
        if($amount_data['amount']>=(50+$amount_data_reorder['amount']*10) && $subscription->trial_days==0)
        {

            //send tarif only if we don't have reorder in route or already succes
            $query="SELECT reorder FROM ReorderLog WHERE msisdn=".$source_adress." AND reorder=0 LIMIT 1";
            $chech_reorder = db_layer::execute($query,"Get Reorder Data");
            if(mysqli_num_rows($chech_reorder)==0)
            {
                $query="INSERT INTO ReorderLog (msisdn,datetime,reorder) VALUES ('";
                $query.=$source_adress."',";
                $query.=" NOW(),";
                $query.="0)";
                db_layer::execute($query,"INSERT ReorderLog");
                $tarif_sms = new sms(confuguration::$reorder_paid_sms_short_number,$source_adress,"Reorder",true,$transaction_id);
                $tarif_sms->kannel_coding=1;
                $tarif_sms->message_type="reorder";
                $tarif_sms->PrepareForSend();
                $tarif_sms->Send();
            }

            $chat_sms=new sms(confuguration::$free_sms_short_number,$recipient_data['msisdn'],$message_part,false,$transaction_id);
            $chat_sms->PrepareForSend();
            //buffer SMS
            $chat_sms->text = $user_profile->nickname.":".$chat_sms->text;

            $query="INSERT INTO OutgoingSMS_ReorderBuffer (";
            $query.="scr_adress,dst_adress,text,status,create_date,send_date,last_status_date,dlr_url,charset,dlr_mask,priority,marker_optional,transaction_id)";
            $query.=" VALUES ('";
            $query.=$chat_sms->src_adress."',";
            $query.=$chat_sms->dst_adress.",'";
            $query.=db_layer::encode_sql_unsafe_data($chat_sms->text)."',";
            $query.="101,";
            $query.="NOW(),";
            $query.="NOW(),";
            $query.="NULL,'";
            $query.=$chat_sms->kannel_dlr_url."','";
            $query.=$chat_sms->kannel_charset."',";
            $query.=$chat_sms->kannel_dlr_mask.",";
            $query.=$chat_sms->kannel_sms_priority.",'";
            $query.=$source_adress."',";
            $query.=$chat_sms->transaction_id.")";
            db_layer::execute($query,"INSERT OutgoingSMS_ReorderBuffer");

            if($debug_mode)
            {
                echo("Reorder requested".PHP_EOL);
            }
        }
          */
        if (is_array($amount_data) && $amount_data['amount'] >= 200) {
            $notif_text=common::GetNotificationText('on_sms_limit_200', $subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number, $source_adress, $notif_text, false, $transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();

            if ($debug_mode) {
                echo("Send limit 200 exceed");
            }

            return;
        }

        $databaseLayer->INSERT_INTO_SMSCounter($source_adress);

        #$query="INSERT INTO SORMConversationLog (src_adress,dst_adress,transaction_id,datetime_receive)"
        #        ."VALUES (".$source_adress.",".$recipient_data['msisdn'].",".$transaction_id.",NOW());";

        $databaseLayer->INSERT_INTO_SORMConversationLog($source_adress, $recipient_data['msisdn'], $transaction_id, $nickname);

        $message_part = $user_profile->nickname . ':' . $message_part;
        $chat_sms=new sms(confuguration::$free_sms_short_number, $recipient_data['msisdn'], $message_part, false, $transaction_id);
        $chat_sms->kannel_sms_priority = confuguration::$kannel_send_conversation_sms_priority;
        $chat_sms->PrepareForSend();
        $chat_sms->partner_msisdn = $source_adress;
        $chat_sms->Send();

        if ($debug_mode) {
            echo('Redirect sms success'.PHP_EOL);
        }
    }

    private function parseNumberFromMessage(string $message): ?string
    {
        $regex = '/((\+?998|20|33|50|77|88|90|91|93|94|95|97|99)[\s\.\d]+\d{1})\w/';
        preg_match($regex, $message, $matches);

        return $matches[0] ?? null;
    }

    private function isNumberOwner(string $number, profile $profile): bool
    {
        $numberProfile = new profile($number);

        return $numberProfile->nickname === $profile->nickname;
    }
}
