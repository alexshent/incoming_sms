<?php
/**
 * Created by PhpStorm.
 * User: Alexandr
 * Date: 1/20/15
 * Time: 12:43 PM
 */

class report_user
{
    public static function Process($message,$source_adress,$dest_adress,$subscription,$transaction_id)
    {
        $debug_mode = false;
        $nickname = explode("=", $message);
        $nickname=$nickname[1];

        $query="SELECT * FROM Profiles WHERE nickname='".db_layer::encode_sql_unsafe_data($nickname)."' LIMIT 1";
        $data=db_layer::execute($query,"GET Profile By Nickname");
        if(mysqli_num_rows($data)==0)
        {
            $notif_text=common::GetNotificationText('nick_is_correct_not_on_nick_assign',$subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number,$source_adress,$notif_text,false,$transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();
            if($debug_mode)
            {
                echo("Reported User Not Subscribed");
            }
            exit;
        }
        $profile =  new profile($source_adress);
        if($profile->nickname==$nickname)
        {
            $notif_text=common::GetNotificationText('nick_is_correct_not_on_nick_assign',$subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number,$source_adress,$notif_text,false,$transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();
            if($debug_mode)
            {
                echo("Self Report");
            }
            exit;
        }


        $data = mysqli_fetch_assoc($data);
        $query="INSERT INTO UserReports (reported_msisdn,reported_by,datetime,marker) VALUES(";
        $query.=$data['msisdn'].",".$source_adress.",NOW(),'uncheked')";
        db_layer::execute($query,"Report User");


        $notif_text=common::GetNotificationText('on_report_accept',$subscription->lang_id);
        $notif_text = str_replace("{Nickname}", $nickname, $notif_text);
        $notif_sms = new sms(confuguration::$free_sms_short_number,$source_adress,$notif_text,false,$transaction_id);
        $notif_sms->PrepareForSend();
        $notif_sms->Send();
        if($debug_mode)
        {
            echo("User Reported");
        }
        exit;

    }



} 