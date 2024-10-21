<?php
/**
 * Created by PhpStorm.
 * User: Alexandr
 * Date: 1/20/15
 * Time: 7:10 AM
 */


require_once (dirname(__FILE__)."/../../core.php");

class nick_assign
{
    
	public static function Process($message,$source_adress,$dest_adress,$subscription,$transaction_id)
    {
        $cyr_count = 0;
		$lat_count = 0;
		$sym_count = 0;
		$bad_count = 0;
		$encoding = 'UTF-8';
		$string = array();
        
		$cyr = array(
				'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'Ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я', 'і', 'ї', 'є', 'ü', 'ş', 'ý', 'ö', 'ä', 'ň', 'ç', 'ž',
                'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'І', 'Ї', 'Є', 'Ü', 'Ş', 'Ý', 'Ö', 'Ä', 'Ň', 'Ç', 'Ž');
			
		$lat = array(
			'a', 'b', 'v', 'g', 'd', 'e', 'z', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'y', 'x', 'q', 'w',
			'A', 'B', 'V', 'G', 'D', 'E', 'Z', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Y', 'X', 'Q', 'W');
        
		$sym = array(
			'!', '#', '$', '&', '(', ')', '<', '>', '=', '-', '_', '.', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0');
		
		
		$debug_mode = false;
        $nickname = explode(":", $message);
        $tmp="";
        for($x=1;$x<count($nickname);$x++)
        {
            $tmp.=$nickname[$x];
        }
        $nickname=$tmp;
        $nickname = trim($nickname);
        $nickname = str_replace(",","",$nickname);
        $nickname = str_replace(" ","",$nickname);
        $nickname = str_replace("=","",$nickname);
		$nickname = str_replace("'","",$nickname);
		$nickname = str_replace("\\","",$nickname);
		if($nickname==null || $nickname=="" || strlen($nickname)==0)
		{
			$notif_text=common::GetNotificationText('nick_is_not_correct',$subscription->lang_id);
			$notif_sms = new sms(confuguration::$free_sms_short_number,$source_adress,$notif_text,false,$transaction_id);
			$notif_sms->PrepareForSend();
			$notif_sms->Send();
			if($debug_mode)
			{
				echo('NIK: Empty Nickname after clear wrong simbols'.PHP_EOL);
			}
			exit;
		}
		
		
        
	//	syslog(LOG_INFO, 'USSD NICK: '.$nickname);
		if(mb_strlen($nickname, $encoding) > 10)
		{
			$nickname = mb_substr($nickname, 0, 10, $encoding); 
		}
		
		$string = self::mbStringToArray($nickname, $encoding);
        
		for($i = 0; $i < count($string); $i++)
		{
			if(in_array($string[$i],$cyr))
			{
				$cyr_count++;
			}
			elseif(in_array($string[$i],$lat))
			{
				$lat_count++;
			}
			elseif(in_array($string[$i],$sym))
			{
				$sym_count++;
			}
			else
			{
				$bad_count++;
			//	syslog(LOG_INFO, 'USSD BAD: '.$string[$i] .' '.$bad_count);
			}
		}
		
		if($bad_count > 0)
		{
			$notif_text=common::GetNotificationText('nick_is_not_correct',$subscription->lang_id);
			$notif_sms = new sms(confuguration::$free_sms_short_number,$source_adress,$notif_text,false,$transaction_id);
			$notif_sms->PrepareForSend();
			$notif_sms->Send();
			if($debug_mode)
			{
				echo('NIK: Incorrect symbols'.PHP_EOL);
			}
			exit;
		}
	
/*	
		if($cyr_count > $lat_count && $lat_count != 0)
		{
			$nickname = self::transliterate(null, $nickname);
		}
		if($cyr_count < $lat_count && $cyr_count != 0)
		{
*/
			$nickname = self::transliterate($nickname);
/*		}
		if($cyr_count == $lat_count && $cyr_count != 0)
		{
			$nickname = self::transliterate($nickname);
		}
*/		
		
		if(mb_strlen($nickname, $encoding) > 10)
		{
			$nickname = mb_substr($nickname, 0, 10, $encoding); 
		}
		
		
		$mas = str_split($nickname);
		$numeric_amount= 0;
		foreach ($mas as $value) 
		{
			if(is_numeric($value))
				{
					$numeric_amount++;
				}
		}
		if($numeric_amount > 5 )
		{
		  $notif_text=common::GetNotificationText('nick_is_not_correct',$subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number,$source_adress,$notif_text,false,$transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();
            if($debug_mode)
            {
                echo('NIK: Incorrect Nickname, it contains at least 10 digits'.PHP_EOL);
            }
            exit;
		}
		
		
           
		
        $user_profile = new profile($source_adress);

        //some parametrs missing or profile (sex,age e.t.c)
        //Если осталось заполнить только ник или профиль не полный(вариант изменение ника)
        if($user_profile->id==null  || ($user_profile->profile_status!='notification' && $user_profile->profile_status!='complete'))
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
                echo('NIK: Not Complete profile'.PHP_EOL);
            }
            exit;
        }

        if(strlen($nickname)==0)
        {
            $notif_text=common::GetNotificationText('nick_is_not_correct',$subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number,$source_adress,$notif_text,false,$transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();
            if($debug_mode)
            {
                echo('NIK: Lengh 0'.PHP_EOL);
            }
            exit;
        }


        //check if busy
        $check_if_busy=profile::CheckIfNickBusy(db_layer::encode_sql_unsafe_data($nickname));
        if($check_if_busy==1)
        {
            $notif_text=common::GetNotificationText('nick_already_assigned',$subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number,$source_adress,$notif_text,false,$transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();

            if($debug_mode)
            {
                echo('NIK: Busy'.PHP_EOL);
            }
            exit;
        }
		
		/*29.05.17 S.Dmitrii TASK 7083*/
        //check if banned
        if ($source_adress==998909977345)
        {
	        $check_if_banned=profile::CheckIfNickBanned(db_layer::encode_sql_unsafe_data($nickname));
	        if($check_if_banned==1)
	        {
	        	$notif_text=common::GetNotificationText('on_nick_ban',$subscription->lang_id);
	        	$notif_sms = new sms(confuguration::$free_sms_short_number,$source_adress,$notif_text,false,$transaction_id);
	        	$notif_sms->PrepareForSend();
	        	$notif_sms->Send();
	        	
	        	if($debug_mode)
	        	{
	        		echo('NIK: Banned'.PHP_EOL);
	        	}
	        	exit;
	        }
        }
		
        //check Nick format
        {
			$sendFriendList = false;
            if($user_profile->nickname==null)
            {
			//	$notif_text=common::GetNotificationText('nick_assign_success',$subscription->lang_id);
				
				$notif_text=common::GetNotificationText('nick_assign_success_new',$subscription->lang_id);
				$notif_text=str_replace("{Nickname}",$nickname,$notif_text);
				$sendFriendList = true;
            }
            else
            {
            	
				
				
				$query = "SELECT text FROM Descriptions WHERE parameter = 'sex' AND parameter_id=".$user_profile->sex." AND lang_id = 1 limit 1";
			$sqlData = db_layer::execute($query,"Get Profile Description ");
			$sex = mysqli_fetch_assoc($sqlData);
			
			$query = "SELECT text FROM Descriptions WHERE parameter = 'sex' AND parameter_id=".$user_profile->search_for." AND lang_id = 1 limit 1";
			$sqlData = db_layer::execute($query,"Get Profile Description ");
			$search_for = mysqli_fetch_assoc($sqlData);
			
			$query = "SELECT text FROM Descriptions WHERE parameter = 'type_of_relation' AND parameter_id=".$user_profile->type_of_relation." AND lang_id = 1 limit 1";
			$sqlData = db_layer::execute($query,"Get Profile Description ");
			$type_of_relation = mysqli_fetch_assoc($sqlData);
			
			$query = "SELECT text FROM Descriptions WHERE parameter = 'region' AND parameter_id= ".$user_profile->region." AND lang_id =1 limit 1";
			$sqlData = db_layer::execute($query,"Get Profile Description ");
			$region = mysqli_fetch_assoc($sqlData);
				
				
                $query = "INSERT INTO SORMArchiveProfilesData (datetime,subscription_id,msisdn,nickname,sex,age,search_for,type_of_relation,region,datetime_start,datetime_nick_assign)
						  VALUES ( NOW(),".$subscription->subscription_id.","
						            .$subscription->msisdn.",'"
						            .db_layer::encode_sql_unsafe_data($nickname)."','"
						            .$sex['text']."','"
						            .$user_profile->age."','"
						            .$search_for['text']."','"
						            .$type_of_relation['text']."','"
						            .$region['text']."','"
						            .$subscription->date_started."','"
						            .$user_profile->datetime_nick_assign."');";     
									   	
                db_layer::execute($query,"Log Profile Data to SORMArchiveProfilesData");
				
                $notif_text=common::GetNotificationText('nick_change_success',$subscription->lang_id);

                $query="UPDATE UserFriends SET friend_nickname='".db_layer::encode_sql_unsafe_data($nickname);
                $query.="' WHERE friend_msisdn=".$source_adress;
                db_layer::execute($query,"Update friend Nickname");
            }
            $user_profile->nickname = db_layer::encode_sql_unsafe_data($nickname);
			/*
			
			A.Nicolaev Drop DB FIX
			*/
           // $user_profile->profile_status="complete";
            if($user_profile->age!=null
                && $user_profile->search_for!=null
                && $user_profile->region!=null
                && $user_profile->type_of_relation!=null)
            {
                $user_profile->profile_status="complete";
            }
            else
            {
                $user_profile->profile_status="notification";
            }

            $user_profile->datetime_nick_assign="NOW()";
            $user_profile->is_visible=1;
            $user_profile->Save();
			

            $notif_sms = new sms(confuguration::$free_sms_short_number,$source_adress,$notif_text,false,$transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();
			
			if($sendFriendList)
                common::sendSmsWithFriends($source_adress, $subscription->lang_id);
			
            if($debug_mode)
            {
                echo('NIK: Changed msisdn: '.$source_adress.PHP_EOL);
            }
            exit;
        }
    }
	
	public function mbStringToArray($string, $encoding) 
	{ 
		$strlen = mb_strlen($string); 
		while ($strlen) { 
			$array[] = mb_substr($string, 0, 1, $encoding); 
			$string = mb_substr($string, 1, $strlen, $encoding); 
			$strlen = mb_strlen($string, $encoding); 
		} 
		return ($array); 
	}

	public function transliterate($textcyr = null, $textlat = null) 
	{
		$cyr = array(
			'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'Ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я', 'і', 'ї', 'є', 'ü', 'ş', 'ý', 'ö', 'ä', 'ň', 'ç', 'ž',
                'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'І', 'Ї', 'Є', 'Ü', 'Ş', 'Ý', 'Ö', 'Ä', 'Ň', 'Ç', 'Ž');
			$lat = array(
			'a', 'b', 'v', 'g', 'd', 'e', 'e', 'j',	'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', '', 'y', '', 'e', 'yu', 'ya' ,'i', 'yi', 'e', 'u', 'sh', 'y', 'o', 'a', 'n', 'ch', 'zh',	
            'A', 'B', 'V', 'G',	'D', 'E', 'E', 'J', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch', '', 'Y', '', 'E', 'Yu', 'Ya', 'I', 'Yi', 'E', 'U', 'Sh', 'Y', 'O', 'A', 'N', 'Ch', 'Zh');
		if($textcyr) return str_replace($cyr, $lat, $textcyr);
		else if($textlat) return str_replace($lat, $cyr, $textlat);
		else return null;
	}

}
