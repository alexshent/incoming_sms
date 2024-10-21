<?php
    // var_dump(123);die;
require_once(dirname(__FILE__) . "/../core.php");
log::Info(null, "===============Process Start==============");


$session_id = $_GET['dialog_id'];
$ussd_input = $_GET['ussd_text'];
$src_adress = $_GET['msisdn'];
$dst_adress = $_GET['service_code'];

$ussd_service_op = 1;


if (preg_match("/^[\d]+$/", $ussd_input) != 1) {
    $ussd_input = "9999";
}


$ussd_session_id = $session_id;
try {

    //Check if access is granted to msisdn
    $ussd_session = new ussd_session($src_adress, $ussd_session_id);
    // common::SendResponse("Сервис временно не доступен", confuguration::$ussd_short_number, $ussd_session, false);
    // exit;
    //if (!in_array($ussd_session->msisdn, confuguration::$testMsisdns)) {
    //    common::SendResponse("Сервис временно не доступен", confuguration::$ussd_short_number, $ussd_session, false);
    //   exit;
    //}
    // #Zaglushka 2020 START
    // common::SendResponse("Сервис временно не доступен", confuguration::$ussd_short_number, $ussd_session, false);
    // exit;
    // #Zaglushka 2020 END

    // check if msisdn is in cache for subscribe
    if (common::GetFromCache($src_adress)) {
        $notif_text = common::GetNotificationText('subscribe_in_process', 2);
        common::SendResponse($notif_text, confuguration::$ussd_short_number, $ussd_session, false);
        $ussd_session->menu = 'incoming_ussd > subscribe_in_process';
        $ussd_session->Save();
        exit;
    }


    if ($ussd_session->is_expired_or_not_exists) {
        $access_granted = access::IsGranted($src_adress);
        if ($access_granted == false) {
            $query = "INSERT INTO Logs (log_type,log_event,log_data,log_optional_marker,log_time) ";
            $query .= " VALUES ('INFO','Access Denied'," . $src_adress . ",'USSD',NOW())";
            db_layer::execute($query, "Log access denied");

            $notif_text = common::GetNotificationText('you_are_banned', 2);
            common::SendResponse($notif_text, confuguration::$ussd_short_number, $ussd_session, false);

            exit;
        }
    }

    $subscription = new subscription($src_adress, 100);

    // if($src_adress == 998911637637){
    //     var_dump($subscription); die;
    // }

    $profile_data = new profile($src_adress);

    log::Info(null, '===Recieved new ussd request===');
    log::Info($src_adress, '===Source adress===');
    log::Info($dst_adress, '===Destination adress===');
    log::Info($ussd_input, '===Request body===');

    log::Debug($ussd_session, '===USSD Session info===');
    log::Debug($subscription, '===Subscription info===');
    log::Debug($profile_data, '===Profile data===');


    /*
У каждого Меню есть две фазы\действия:
1. Мы показываем текст меню(Меню А).Просто показываем и ждём когда пользователь увидет и что-то нажмёт.
2. Мы обрабатываем ответ пользователя(Пишем в БД,вызыаем что хотим) на Меню А и вызываем фазу/действие 1 Меню B.
*/


    if ($ussd_session->is_expired_or_not_exists) {
        $ussd_session->menu = common::DefineStartMenu($subscription);
    }

    if ($ussd_session->is_expired_or_not_exists && ($_GET['ussd_text'] == '*810*1#')) {
        if ($subscription->subscription_id == null || $subscription->status == 2) {
            // $ussd_session->menu = 'intro_short';
        }
    }


    log::Debug("=========Current menu->" . $ussd_session->menu . "===============" . PHP_EOL);

    //Каждый элемент меню делаеться отдельным классом что бы поддерживать одни и тем же методы  и когда делаем require_once  не ловим ошибку.
    require_once(dirname(__FILE__) . "/../menu/" . $ussd_session->menu . ".php");
    $menu_name = $ussd_session->menu;
    $menu = new $menu_name();
    $menu->construct_with_parm($subscription, $ussd_session, $profile_data, $ussd_input);


    if ($ussd_session->is_expired_or_not_exists) {
        $menu->ShowMenu();
    } else {
        $menu->Process();
    }



    log::Info("===============Process End==============");
} catch (Exception $e) {

    syslog(LOG_ERR, "ERROR_BEELINE_USSD_DATING: incomming_ussd " . $e->getMessage() . " " . $e->getline());
}
