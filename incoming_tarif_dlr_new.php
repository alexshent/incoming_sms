<?php
require_once (dirname(__FILE__)."/../core.php");

$response_result = $_GET['transaction_status'];
$transaction_id = $_GET['transaction_id'];
$attempts = $_GET['attempt'];
$msisdn = $_GET['msisdn'];

sleep(1);

switch ($response_result)
	        {
	        	case '0': 
					$response_result_desc = "Tarification Success";
					break;
				case '2': 
					$response_result_desc = "Prepaid subscriber not active";					
					break;
				case '3': 
					$response_result_desc = "Billing unavailable";
					log::ChargeError($msisdn,$response_result_desc);
					break;
				case '4': 
					$response_result_desc = "Prepaid subscriber has insufficient balance";					
					break;
				case '5': 
					$response_result_desc = "Billing Error";
					log::ChargeError($msisdn,$response_result_desc);
					break;
				case '-2': 
					$response_result_desc = "Tarification group not permited(Prepay)";
					log::ChargeError($msisdn,$response_result_desc);	
					break;
				case '-1': 
					$response_result_desc = "Tarification group not permited(Postpay)";
					#log::ChargeError($msisdn,$response_result_desc);									
					unsubscribe($msisdn);										
					break;	
				case '-10': 
					$response_result_desc = "Internal System Error";
					log::ChargeError($msisdn,$response_result_desc);
					$response_result=0;
					break;
				default: 
					$response_result_desc = "Unknow Error";
					log::ChargeError($msisdn,$response_result_desc);
					 break;						
	        }  	



$query=" UPDATE ChargeLog 
	        SET datetime_response=NOW(),
	            response_result= $response_result,
	            response_result_desc= '$response_result_desc',
	            attempts= $attempts  
          WHERE id = $transaction_id";
		  
db_layer::execute($query,"UPDATE Charge LOG FROM DLR");

if(db_layer::$sql_link->affected_rows===0)
{
	log::Error($transaction_id,"UNKNOW TARIF RESPONSE TRANSACTION ID");
	syslog(LOG_ERR,"UNKNOW TARIF RESPONSE TRANSACTION ID: $transaction_id");
	exit();
}



if ($response_result == "0")
{

	$query="    UPDATE Subscriptions as t1			            
             LEFT JOIN Profiles t2
                    ON t2.msisdn = $msisdn			   
			       SET t1.`status` =  IF(t1.`status` = 2, 2, 1),
					   t1.tarif_status = 2,
					   t1.last_charge_date = NOW(),
					   t1.last_success_charge_date = NOW(),
					   t2.is_visible = 1
			 	 WHERE t1.msisdn = $msisdn";			 	 
	
	//syslog(LOG_INFO,"UZ BEELINE USSD DATING msisdn=".$msisdn." SQL=".$query);	
    db_layer::execute($query,"UPDATE Subscriptions LOG FROM DLR");
	if($msisdn==998909058445){
		syslog(LOG_INFO,"s.luca SUCCESS : ".$query);
		syslog(LOG_INFO,"s.luca SUCCESS : ".json_encode(db_layer::$sql_link));
	}
	
	
	
}else
{
	try{
		
		// if($msisdn==998908088285){
			$subscription = new  subscription($msisdn,100);
			if ($subscription->subscription_id != null){
				$subscription->status = ($subscription->status==2) ? 2 : 3;
				$subscription->tarif_status = 3;
				$subscription->last_charge_date = 'NOW()';
				$subscription->Save();
			}
			
		// }else{
			// $query = "UPDATE Subscriptions SET `status`=  IF(`status` = 2, 2, 3),tarif_status = 3, last_charge_date = NOW(),flagTest = 1 WHERE msisdn = $msisdn";
			// db_layer::execute($query,"UPDATE Subscriptions LOG FROM DLR");

			// if(db_layer::$sql_link->affected_rows===0){
				// log::Error($msisdn,$query);
			// }
		// }
		
		
		
		
		
		
		
	}
	catch (Exception $exc)
	{
		syslog( LOG_ERR,"ERROR_BEELINE_USSD_DATING: DLR  ".$exc->getMessage()."  ||  ".$exc->getline() );
	}
		
		
		$query = "UPDATE `Profiles` SET is_visible = 0 WHERE msisdn = $msisdn";
		db_layer::execute($query,"UPDATE Subscriptions LOG FROM DLR");
	

		// $query="    UPDATE Subscriptions as t1	 
			 	 // LEFT JOIN Profiles t2
						// ON t2.msisdn = $msisdn                   				   
					   // SET t1.`status`=  IF(t1.`status` = 2, 2, 3),
						   // t1.tarif_status = 3,
						   // t1.last_charge_date = NOW(),
						   // t1.flagTest = 1,
						   // t2.is_visible = 0 
					 // WHERE t1.msisdn = $msisdn";
		
	
		// db_layer::execute($query,"UPDATE Subscriptions LOG FROM DLR");
	// syslog(LOG_INFO,"beeline Affected Rows : ".db_layer::$sql_link->affected_rows . " || MSISDN: $msisdn");
	if($msisdn==998909058445){
		syslog(LOG_INFO,"s.luca Another Status : ".$query);
		syslog(LOG_INFO,"s.luca Another Status : ".json_encode(db_layer::$sql_link));
	}
	
}

function unsubscribe($msisdn){
    	
		
		$subscription = new  subscription($msisdn,100);
       
	    if($subscription->status != 2 && $subscription->subscription_id != null)
	    {
	    	$profile = new profile($msisdn);
	        $subscription->status=2;
	        $subscription->date_stopped="NOW()";
			$subscription->deactivation_channel = "POSTPAID";
	        $subscription->Save();
			syslog(LOG_INFO,"beeline unsubscribe this-> : ".json_encode($subscription));
			if($profile->profile_status=="complete")
			{
			
				$query = "SELECT text FROM Descriptions WHERE parameter = 'sex' AND parameter_id=".$profile->sex." AND lang_id = 1 limit 1";
				$sqlData = db_layer::execute($query,"Get Profile Description stop1");
				$sex = mysqli_fetch_assoc($sqlData);
				
				$query = "SELECT text FROM Descriptions WHERE parameter = 'sex' AND parameter_id=".$profile->search_for." AND lang_id = 1 limit 1";
				$sqlData = db_layer::execute($query,"Get Profile Description stop1");
				$search_for = mysqli_fetch_assoc($sqlData);
				
				$query = "SELECT text FROM Descriptions WHERE parameter = 'type_of_relation' AND parameter_id=".$profile->type_of_relation." AND lang_id = 1 limit 1";
				$sqlData = db_layer::execute($query,"Get Profile Description stop1");
				$type_of_relation = mysqli_fetch_assoc($sqlData);
				
				$query = "SELECT text FROM Descriptions WHERE parameter = 'region' AND parameter_id= ".$profile->region." AND lang_id =1 limit 1";
				$sqlData = db_layer::execute($query,"Get Profile Description stop1");
				$region = mysqli_fetch_assoc($sqlData);
				
			
	
	
	                $query = "INSERT INTO SORMArchiveProfilesData (datetime,subscription_id,msisdn,nickname,sex,age,search_for,type_of_relation,region,datetime_start,datetime_nick_assign,datetime_stop)
							  VALUES (NOW(), ".$subscription->subscription_id.","
							            .$subscription->msisdn.",'"
							            .db_layer::encode_sql_unsafe_data($profile->nickname)."','"
							            .$sex['text']."','"
							            .$profile->age."','"
							            .$search_for['text']."','"
							            .$type_of_relation['text']."','"
							            .$region['text']."','"
							            .$subscription->date_started."','"
							            .$profile->datetime_nick_assign."',NOW());";     
										   	
			     db_layer::execute($query,"Log Profile Data to SORMArchiveProfilesData ProcessOperatorStop");
			}
	        $query = "DELETE FROM UserFriends WHERE friend_msisdn=".$msisdn." OR msisdn=".$msisdn;
	        db_layer::execute($query,"DELETE Friends after unsubscribe");
	
	        $query = "DELETE FROM Profiles WHERE msisdn=".$msisdn;
	        db_layer::execute($query,"DELETE profile after unsubscribe");
	     
	
	        $query = "DELETE FROM UserBlackList WHERE msisdn=".$msisdn." OR blocked_msisdn=".$msisdn;
	        db_layer::execute($query,"DELETE UserBlackList after unsubscribe");
			
			//архивируем сессию чтобы не сыпались ошибки
			$query="INSERT INTO ArchiveSessions (session_id,msisdn,msisdns_nickname,last_action_datetime,menu,find_relation,find_sex,find_age,find_region,list_number,user_input,last_profile_id) ";
			$query.= "SELECT session_id,msisdn,msisdns_nickname,last_action_datetime,menu,find_relation,find_sex,find_age,find_region,list_number,user_input,last_profile_id";
			$query.= " FROM Sessions WHERE msisdn = $msisdn";
					
			db_layer::execute($query,"Archivate Sessions Data");
	
			$query="DELETE FROM Sessions WHERE msisdn = $msisdn";	
			db_layer::execute($query,"Archivate Sessions Data");
	    	
	    }
	    
	}
