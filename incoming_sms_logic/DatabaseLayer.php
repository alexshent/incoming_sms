<?php

declare(strict_types=1);

final class DatabaseLayer
{
    private static ?DatabaseLayer $instance = null;

    public static function getInstance(): DatabaseLayer
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }

    public function INSERT_INTO_IncomingSMS_ConcatinationBuffer(string $source_adress, string $dest_adress, string $message, array $udh): void
    {
        $query = "INSERT INTO IncomingSMS_ConcatinationBuffer (src_adress,dst_adress,text,recieved_datetime,sequence_number,part_no,total_parts)";
        $query .= "VALUES (" . $source_adress . "," . $dest_adress . ",'" . db_layer::encode_sql_unsafe_data($message) . "',NOW(),'";
        $query .= db_layer::encode_sql_unsafe_data($udh['message_id']) . "'," . $udh['part_no'] . "," . $udh['total_parts'] . ")";
        db_layer::execute($query, "Buffer Concancat SMS part");
    }

    public function INSERT_INTO_IncomingSMS(string $source_adress, string $dest_adress, string $message): string
    {
        $query = "INSERT INTO IncomingSMS (src_adress,dst_adress,text,recieved_datetime) VALUES (";
        $query .= $source_adress . ",";
        $query .= $dest_adress . ",'";
        $query .= db_layer::encode_sql_unsafe_data($message) . "',";
        $query .= "NOW())";
        db_layer::execute($query, "Insert IncomingSMS");

        return (string) db_layer::$sql_link->insert_id;
    }

    public function INSERT_INTO_Logs_Not_Allow_Incoming_Sms_Process(string $source_adress, string $message): void
    {
        $query = "INSERT INTO Logs (log_type,log_event,log_data,log_optional_marker,log_time) ";
        $query .= " VALUES ('Warning','Not Allow Incoming Sms Process','source_adress= " . $source_adress . "| message= " . $message . "','Incoming Sms',NOW())";
        db_layer::execute($query, "Not Allow Incoming Sms Process");
    }

    public function INSERT_INTO_Logs_Access_Denied(string $source_address): void
    {
        $query = "INSERT INTO Logs (log_type,log_event,log_data,log_optional_marker,log_time) ";
        $query .= " VALUES ('INFO','Access Denied','" . $source_address . "','SMS',NOW())";
        db_layer::execute($query, "Log access denied");
    }

    public function SELECT_FROM_Profiles(string $nickname): mysqli_result
    {
        $query="SELECT * FROM Profiles WHERE nickname='".db_layer::encode_sql_unsafe_data($nickname)."' LIMIT 1";
        return db_layer::execute($query, "GET Profile By Nickname");
    }

    public function SELECT_text_FROM_Descriptions(string $parameter, string $parameter_id, int $lang_id): mysqli_result
    {
        $query = "SELECT text FROM Descriptions WHERE parameter = '$parameter' AND parameter_id='$parameter_id' AND lang_id = $lang_id limit 1";
        return db_layer::execute($query, "Get Profile Description ");
    }

    public function INSERT_INTO_SORMArchiveProfilesData($subscription, $nickname, $sex, $user_profile, $search_for, $type_of_relation, $region): void
    {
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

        db_layer::execute($query, "Log Profile Data to SORMArchiveProfilesData");
    }

    public function UPDATE_UserFriends(string $nickname, string $source_adress): void
    {
        $query="UPDATE UserFriends SET friend_nickname='".db_layer::encode_sql_unsafe_data($nickname);
        $query.="' WHERE friend_msisdn=".$source_adress;
        db_layer::execute($query, "Update friend Nickname");
    }

    public function INSERT_INTO_UserReports(string $msisdn, string $source_adress): void
    {
        $query="INSERT INTO UserReports (reported_msisdn,reported_by,datetime,marker) VALUES(";
        $query .= "$msisdn, $source_adress, NOW(), 'unchecked')";
        db_layer::execute($query, "Report User");
    }

    public function SELECT_SUM_Amount(string $source_adress): ?array
    {
        $query = "SELECT SUM(Amount) as amount FROM SMSCounter WHERE msisdn='".$source_adress."'";
        $amount_sql_data =  db_layer::execute($query, "Get SendSMSAmount");
        return mysqli_fetch_assoc($amount_sql_data);
    }

    public function INSERT_INTO_SMSCounter(string $source_adress): void
    {
        $query="INSERT INTO SMSCounter (msisdn,amount) VALUES(".$source_adress.",1)";
        db_layer::execute($query, "Add SMS counter");
    }

    public function INSERT_INTO_SORMConversationLog(string $source_address, string $msisdn, string $transaction_id, string $nickname): void
    {
        $query = "INSERT INTO SORMConversationLog (src_adress,dst_adress,transaction_id,datetime_receive, receiver_nick)"
                ."VALUES ($source_address, $msisdn, $transaction_id, NOW(), $nickname);";
        db_layer::execute($query, "Add to SORMConversationLog");
    }
}
