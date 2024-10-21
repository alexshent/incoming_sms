<?php

require_once (dirname(__FILE__)."/../core.php");

sleep(1);
$sms_state = $_GET['smsstate'];
$service_id = $_GET['service_id'];
$sms_id=$_GET['SMS_ID'];
$msisdn=$_GET['msisdn'];
$message_type=$_GET['message_type'];



$marker=4;
   
$query = "INSERT INTO DLR (msisdn,datetime,sms_id,state,marker,marker_word_status)";
$query.=" VALUES($msisdn,NOW(),$sms_id,$sms_state,$marker,'$message_type')";
db_layer::execute($query,"INSERT DLR");

updateSMS($sms_state, $message_type, $sms_id);

function updateSMS($sms_state, $message_type, $sms_id)
{
	$query = "UPDATE OutgoingSMS SET status=$sms_state,last_status_date=NOW(),marker_optional='$message_type' ";
	$query.= "WHERE id=$sms_id";
	$result = db_layer::execute($query,"UPDATE OutgoingSMS");


	if(db_layer::$sql_link->affected_rows===0 || $result===false)
	{
		$query = "UPDATE ArchiveOutgoingSMS SET status=$sms_state,last_status_date=NOW(),marker_optional='$message_type' ";
		$query.= "WHERE id=$sms_id";
		$result = db_layer::execute($query,"UPDATE ArchiveOutgoingSMS");
	}

	if(db_layer::$sql_link->affected_rows===0 || $result===false)
	{
		$part_table_name = "zipArchiveOutgoingSMS";
		$year = date("Y");
		$month = date("m");

		$full_table_name = $part_table_name."_".$year."_".$month."_01";
		
		$query = "UPDATE $full_table_name SET status=$sms_state,last_status_date=NOW(),marker_optional='$message_type' ";
		$query.= "WHERE id=$sms_id";
		$result = db_layer::execute($query,"UPDATE $full_table_name");
	}

	if(db_layer::$sql_link->affected_rows===0 || $result===false)
	{
		log::Error("incoming_dlr.php","UnknownDLR, could not find SMS with id $sms_id");
	}
}
   

