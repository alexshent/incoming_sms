<?php

require_once (dirname(__FILE__)."/../core.php");


try
{

$msisdn=$_GET['msisdn'];
$lang_id=$_GET['lang_id'];
$notif_key=$_GET['notif_key'];


  if($lang_id!=1 && $lang_id!=2)
    {
      $lang_id=1;    
    }
 

$notif_text=common::GetNotificationText($notif_key, $lang_id);
             $notif_sms = new sms(confuguration::$free_sms_short_number,$msisdn,$notif_text,false,333);
             $notif_sms->PrepareForSend();
             $notif_sms->Send();

     echo 0;

}
catch (Exception $ex)
{
     echo 1;
}

?>
