<?php
/**
 * Created by PhpStorm.
 * User: Vitalie
 * Date: 9/4/15
 * Time: 10:30 AM
 */

class sms_remaining
{
    public static function Process($message,$source_adress,$dest_adress,$subscription,$transaction_id)
    {
        $debug_mode=false;
        if($subscription->lang_id==null || $subscription->lang_id==0)
        {
            $subscription->lang_id=2;
        }

        $user_profile = new profile($source_adress);
        //if($user_profile->profile_status!='complete')
        if($user_profile->nickname==null)
		{
            $notif_text=common::GetNotificationText('not_subscribed',1);
            $notif_sms = new sms(confuguration::$free_sms_short_number,$source_adress,$notif_text,false,$transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();
            if($debug_mode)
            {
                echo('Not complete profile'.PHP_EOL);
            }
            exit;
        }

        $query = "SELECT IF(ISNULL(sum(amount)),0,sum(amount)) as amount FROM SMSCounter WHERE msisdn = " . $source_adress;
        $data_sql_amount = db_layer::execute($query,"Get SMSCount ");
        $data_amount = mysqli_fetch_assoc($data_sql_amount);
        $amount = $data_amount['amount'];

        $param = array();
        if($subscription->trial_days==0)
        {
            $query = "SELECT count(reorder) as reorder FROM ReorderLog WHERE msisdn = " . $source_adress . " AND reorder = '1'";
            $data_sql_reorder = db_layer::execute($query,"Get ReorderLog ");
            $data_reorder = mysqli_fetch_assoc($data_sql_reorder);
            $reorder = $data_reorder['reorder'];
            if($reorder > 0)
            {
                $reorder = 100;
            }
            $param['{CurrentDate}'] = date('d.m.Y');
            
            if($amount > 100)
            {
                $param['{DaylyAmountUsed}'] = 0;
                $param['{ReorderAmountUsed}'] = 200 - $amount;
            }
            else
            {
                $param['{DaylyAmountUsed}'] = 100 - $amount;
                $param['{ReorderAmountUsed}'] = 100;
            }
            $param['{TotalSend}'] = $amount;
        }
        else
        {
            $param['{CurrentDate}'] = date('d.m.Y');
            $param['{TotalSend}'] = $amount;
            $param['{DaylyAmountUsed}'] = 200 - $amount;
            $param['{ReorderAmountUsed}'] = 0;
        }

        $notif_text=common::GetNotificationText('on_sms_amount_check',$subscription->lang_id);
        foreach ($param as $key => $value)
        {
            $notif_text = str_replace($key, $value, $notif_text);
        }
        $notif_sms = new sms(confuguration::$free_sms_short_number,$source_adress,$notif_text,false,$transaction_id);
        $notif_sms->PrepareForSend();
        $notif_sms->Send();
        exit;
    }
}
