<?php
include('mailer.php');
$to="bhasha.s@thresholdsoft.com";
$subject="hi, how r u";
$msg="hi this is sample email from php1";
$from="bhasha.s@thresholdsoft.com";
$res=mailCheck($to,$subject,$msg);
echo $res; 
?>
