<?php
/**
 * Created by PhpStorm.
 * User: Alexandr
 * Date: 1/20/15
 * Time: 7:22 AM
 */




class friend_add
{
    public static function Process($message,$source_adress,$dest_adress,$subscription,$transaction_id)
    {
        $debug_mode = false;
        $nickname = explode("=", $message);
        $nickname = trim($nickname[1]);



        $user_profile = new profile($source_adress);
        //some parametrs missing or profile (sex,age e.t.c)
        //Если осталось заполнить только ник или профиль не полный(вариант изменение ника)
        //if($user_profile->profile_status!="complete")
		if($user_profile->nickname==null)
        {
            if($subscription->lang_id==null || $subscription->lang_id==0)
            {
                $subscription->lang_id=2;
            }
            //Not_subscribed actualy mean not registered here. Do not ask...
            $notif_text=common::GetNotificationText('not_subscribed',$subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number,$source_adress,$notif_text,false,$transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();

            if($debug_mode)
            {
                echo('FRIEND= Not Complete profile'.PHP_EOL);
            }
            exit;
        }
        //Смс нотификация - "Ваш ник.."
        //check if exists

        //$friendInfo=GetInfobyNickname($nickname);
        $query="SELECT * FROM Profiles WHERE nickname='".db_layer::encode_sql_unsafe_data($nickname)."' LIMIT 1";
        $friend_sql_data=db_layer::execute($query,"GET Profile By Nickname");

        if(mysqli_num_rows($friend_sql_data)==0)
        {
            $notif_text=common::GetNotificationText('nick_is_correct_not_on_nick_assign',$subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number,$source_adress,$notif_text,false,$transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();
            if($debug_mode)
            {
                echo('FRIEND= Friend not exists'.PHP_EOL);
            }
            exit;
        }

        //user whants to add himself to friends
        if(strtoupper($nickname)==strtoupper($user_profile->nickname))
        {
            $notif_text=common::GetNotificationText('nick_is_correct_not_on_nick_assign',$subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number,$source_adress,$notif_text,false,$transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();
            if($debug_mode)
            {
                echo('FRIEND= User want to add hisself'.PHP_EOL);
            }
            exit;
        }
        $friend_data = mysqli_fetch_assoc($friend_sql_data);

        $check_friend =  friend::construct_by_msisdn_friendnickname($source_adress,$friend_data['nickname']);
        if($check_friend->id==null)
        {
            $friend_subscription = new subscription($friend_data['msisdn'],100);
            $friend = new friend();
            $friend->id=null;
            $friend->subscription_id=$subscription->subscription_id;
            $friend->friend_subscription_id=$friend_subscription->subscription_id;
            $friend->friend_msisdn =  $friend_data['msisdn'];
            $friend->msisdn=$subscription->msisdn;
            $friend->friend_profile_id= $friend_data['id'];
            $friend->friend_nickname= $nickname;
            $friend->Save();
            if($debug_mode)
            {
                echo("FRIEND added".PHP_EOL);
            }
        }
        else
        {
            if($debug_mode)
            {
                echo("FRIEND Already added".PHP_EOL);
            }
        }




        db_layer::execute($query,"Unblock user");

        $delete_block=user_block::construct_by_msisdn_nickname($source_adress,$nickname);
        if($delete_block->id!=null)
        {
            if($debug_mode)
            {
                echo("FRIEND Unblock".PHP_EOL);
            }
            $delete_block->Remove();
        }

        exit;

    }

} 