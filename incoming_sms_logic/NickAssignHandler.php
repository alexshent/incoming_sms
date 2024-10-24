<?php

declare(strict_types=1);

class NickAssignHandler implements IncomingSmsHandler
{

    private array $cyr = array(
    'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'Ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я', 'і', 'ї', 'є', 'ü', 'ş', 'ý', 'ö', 'ä', 'ň', 'ç', 'ž',
    'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'І', 'Ї', 'Є', 'Ü', 'Ş', 'Ý', 'Ö', 'Ä', 'Ň', 'Ç', 'Ž');

    private array $lat = array(
    'a', 'b', 'v', 'g', 'd', 'e', 'e', 'j',	'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', '', 'y', '', 'e', 'yu', 'ya' ,'i', 'yi', 'e', 'u', 'sh', 'y', 'o', 'a', 'n', 'ch', 'zh',
    'A', 'B', 'V', 'G',	'D', 'E', 'E', 'J', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch', '', 'Y', '', 'E', 'Yu', 'Ya', 'I', 'Yi', 'E', 'U', 'Sh', 'Y', 'O', 'A', 'N', 'Ch', 'Zh');

    public function process(string $message, string $source_adress, string $dest_adress, subscription $subscription, string $transaction_id): void
    {
        global $debug_mode;
        $databaseLayer = DatabaseLayer::getInstance();

        $cyr_count = 0;
        $lat_count = 0;
        $sym_count = 0;
        $bad_count = 0;
        $encoding = 'UTF-8';

        $lat = array(
            'a', 'b', 'v', 'g', 'd', 'e', 'z', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'y', 'x', 'q', 'w',
            'A', 'B', 'V', 'G', 'D', 'E', 'Z', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Y', 'X', 'Q', 'W');

        $sym = array(
            '!', '#', '$', '&', '(', ')', '<', '>', '=', '-', '_', '.', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0');

        $nickname = explode(":", $message);
        $tmp="";
        for ($x=1;$x<count($nickname);$x++) {
            $tmp.=$nickname[$x];
        }
        $nickname=$tmp;
        $nickname = trim($nickname);
        $nickname = str_replace(",", "", $nickname);
        $nickname = str_replace(" ", "", $nickname);
        $nickname = str_replace("=", "", $nickname);
        $nickname = str_replace("'", "", $nickname);
        $nickname = str_replace("\\", "", $nickname);
        if ($nickname==null || $nickname=="" || strlen($nickname)==0) {
            $notif_text=common::GetNotificationText('nick_is_not_correct', $subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number, $source_adress, $notif_text, false, $transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();

            if ($debug_mode) {
                echo('NIK: Empty Nickname after clear wrong simbols'.PHP_EOL);
            }

            return;
        }

        //	syslog(LOG_INFO, 'USSD NICK: '.$nickname);
        if (mb_strlen($nickname, $encoding) > 10) {
            $nickname = mb_substr($nickname, 0, 10, $encoding);
        }

        $string = self::mbStringToArray($nickname, $encoding);

        for ($i = 0; $i < count($string); $i++) {
            if (in_array($string[$i], $this->cyr)) {
                $cyr_count++;
            } elseif (in_array($string[$i], $lat)) {
                $lat_count++;
            } elseif (in_array($string[$i], $sym)) {
                $sym_count++;
            } else {
                $bad_count++;
                //	syslog(LOG_INFO, 'USSD BAD: '.$string[$i] .' '.$bad_count);
            }
        }

        if ($bad_count > 0) {
            $notif_text=common::GetNotificationText('nick_is_not_correct', $subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number, $source_adress, $notif_text, false, $transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();

            if ($debug_mode) {
                echo('NIK: Incorrect symbols'.PHP_EOL);
            }

            return;
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

        if (mb_strlen($nickname, $encoding) > 10) {
            $nickname = mb_substr($nickname, 0, 10, $encoding);
        }

        $mas = str_split($nickname);
        $numeric_amount= 0;
        foreach ($mas as $value) {
            if (is_numeric($value)) {
                $numeric_amount++;
            }
        }

        if ($numeric_amount > 5) {
            $notif_text=common::GetNotificationText('nick_is_not_correct', $subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number, $source_adress, $notif_text, false, $transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();

            if ($debug_mode) {
                echo('NIK: Incorrect Nickname, it contains at least 10 digits'.PHP_EOL);
            }

            return;
        }

        $user_profile = new profile($source_adress);

        //some parametrs missing or profile (sex,age e.t.c)
        //Если осталось заполнить только ник или профиль не полный(вариант изменение ника)
        if ($user_profile->id==null  || ($user_profile->profile_status!='notification' && $user_profile->profile_status!='complete')) {
            if ($subscription->lang_id==null || $subscription->lang_id==0) {
                $subscription->lang_id=2;
            }
            //Not_subscribed actualy mean not registered here. Do not ask...
            $notif_text=common::GetNotificationText('not_subscribed', $subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number, $source_adress, $notif_text, false, $transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();

            if ($debug_mode) {
                echo('NIK: Not Complete profile'.PHP_EOL);
            }

            return;
        }

        if (0 === strlen($nickname)) {
            $notif_text=common::GetNotificationText('nick_is_not_correct', $subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number, $source_adress, $notif_text, false, $transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();

            if ($debug_mode) {
                echo('NIK: Lengh 0'.PHP_EOL);
            }

            return;
        }

        //check if busy
        $check_if_busy=profile::CheckIfNickBusy(db_layer::encode_sql_unsafe_data($nickname));
        if ($check_if_busy==1) {
            $notif_text=common::GetNotificationText('nick_already_assigned', $subscription->lang_id);
            $notif_sms = new sms(confuguration::$free_sms_short_number, $source_adress, $notif_text, false, $transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();

            if ($debug_mode) {
                echo('NIK: Busy'.PHP_EOL);
            }

            return;
        }

        /*29.05.17 S.Dmitrii TASK 7083*/
        //check if banned
        if ($source_adress==998909977345) {
            $check_if_banned=profile::CheckIfNickBanned(db_layer::encode_sql_unsafe_data($nickname));
            if (1 == $check_if_banned) {
                $notif_text=common::GetNotificationText('on_nick_ban', $subscription->lang_id);
                $notif_sms = new sms(confuguration::$free_sms_short_number, $source_adress, $notif_text, false, $transaction_id);
                $notif_sms->PrepareForSend();
                $notif_sms->Send();

                if ($debug_mode) {
                    echo('NIK: Banned'.PHP_EOL);
                }

                return;
            }
        }

        //check Nick format
        {
            $sendFriendList = false;
            if ($user_profile->nickname==null) {
                //	$notif_text=common::GetNotificationText('nick_assign_success',$subscription->lang_id);

                $notif_text=common::GetNotificationText('nick_assign_success_new', $subscription->lang_id);
                $notif_text=str_replace("{Nickname}", $nickname, $notif_text);
                $sendFriendList = true;
            } else {
                $sqlData = $databaseLayer->SELECT_text_FROM_Descriptions('sex', $user_profile->sex, 1);
                $sex = mysqli_fetch_assoc($sqlData);

                $sqlData = $databaseLayer->SELECT_text_FROM_Descriptions('sex', $user_profile->search_for, 1);
                $search_for = mysqli_fetch_assoc($sqlData);

                $sqlData = $databaseLayer->SELECT_text_FROM_Descriptions('type_of_relation', $user_profile->type_of_relation, 1);
                $type_of_relation = mysqli_fetch_assoc($sqlData);

                $sqlData = $databaseLayer->SELECT_text_FROM_Descriptions('region', $user_profile->region, 1);
                $region = mysqli_fetch_assoc($sqlData);

                $databaseLayer->INSERT_INTO_SORMArchiveProfilesData($subscription, $nickname, $sex, $user_profile, $search_for, $type_of_relation, $region);

                $notif_text=common::GetNotificationText('nick_change_success', $subscription->lang_id);

                $databaseLayer->UPDATE_UserFriends($nickname, $source_adress);
            }
            $user_profile->nickname = db_layer::encode_sql_unsafe_data($nickname);
            /*

            A.Nicolaev Drop DB FIX
            */
            // $user_profile->profile_status="complete";
            if ($user_profile->age!=null
                && $user_profile->search_for!=null
                && $user_profile->region!=null
                && $user_profile->type_of_relation!=null) {
                $user_profile->profile_status="complete";
            } else {
                $user_profile->profile_status="notification";
            }

            $user_profile->datetime_nick_assign="NOW()";
            $user_profile->is_visible=1;
            $user_profile->Save();

            $notif_sms = new sms(confuguration::$free_sms_short_number, $source_adress, $notif_text, false, $transaction_id);
            $notif_sms->PrepareForSend();
            $notif_sms->Send();

            if ($sendFriendList) {
                common::sendSmsWithFriends($source_adress, $subscription->lang_id);
            }

            if ($debug_mode) {
                echo('NIK: Changed msisdn: '.$source_adress.PHP_EOL);
            }
        }
    }

    private function mbStringToArray($string, $encoding): array
    {
        $array = [];
        $strlen = mb_strlen($string);
        while ($strlen) {
            $array[] = mb_substr($string, 0, 1, $encoding);
            $string = mb_substr($string, 1, $strlen, $encoding);
            $strlen = mb_strlen($string, $encoding);
        }

        return $array;
    }

    private function transliterate($textcyr = null, $textlat = null): ?string
    {
        if ($textcyr) {
            return str_replace($this->cyr, $this->lat, $textcyr);
        } elseif ($textlat) {
            return str_replace($this->lat, $this->cyr, $textlat);
        } else {
            return null;
        }
    }
}
