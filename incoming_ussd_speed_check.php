<?php



 $dateTime = "\[".date("d")."\/".substr(date("F"),0,3)."\/".date("Y:H:i:",strtotime('-1 minutes'));
 $searchPath="\/USSD_DATING\/beeline_uz\/handlers\/incoming_ussd.php";
 $fileLocation="/var/log/nginx/access.log";
 
 
 
 
 exec("tail -20000 $fileLocation |grep \"$dateTime\" |grep \" $searchPath\" |wc -l",$requestPerMinute);


 echo round($requestPerMinute[0]/60,4);

 



?>