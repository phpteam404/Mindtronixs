<?php
error_reporting(0);
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception; 
require 'vendor/autoload.php';
function sendmail($toid,$subject,$message)
{
    //return 1;//not to send mail just return true
	$toid = 'saiprasad.b@thresholdsoft.com';// Mail to id
    $mail = new PHPMailer(true);                            
    try {
        //Server settings
        $mail->isSMTP();                                     
        $mail->Host = 'smtp.gmail.com';                      
        $mail->SMTPAuth = true;                             
        $mail->Username = 'servicegooooglemail@gmail.com';     
        $mail->Password = 'Service@1234#';            
        $mail->SMTPOptions = array(
            'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
            )
        );                         
        $mail->SMTPSecure = 'tls';                           
        $mail->Port = 587;                                   

        //Send Email
        $mail->setFrom('info@mindtronix.com');
        
        //Recipients
        $mail->addAddress($toid);              
        //$mail->addAddress('saiprasad.b@thresholdsoft.com');            
        $mail->addReplyTo('no-reply@mindtronix.com');
        
        //Content
        $mail->isHTML(true);                                  
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        
       //echo 'Message has been sent';
	   return 1;
       
    } catch (Exception $e) {
    //   echo 'Message could not be sent. Mailer Error: '.$mail->ErrorInfo;
	  return 0;       
    }
}

function mailCheck($toid,$subject,$message)
{ 

	$mail = new PHPMailer(true);                            
    try {
        //Server settings
        $mail->isSMTP();                                     
        $mail->Host = 'smtp.gmail.com';                      
        $mail->SMTPAuth = true;                             
        echo '<br> Username: '.$mail->Username = 'servicegooooglemail@gmail.com';     
        echo '<br> Password: '.$mail->Password = 'Service@1234#';             
        $mail->SMTPOptions = array(
            'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
            )
        );                         
        $mail->SMTPSecure = 'tls';                           
        $mail->Port = 587;                                   

        //Send Email
        $mail->setFrom('info@mindtronix.com');
        
        //Recipients
        $mail->addAddress('saiprasad.b@thresholdsoft.com');              
        $mail->addReplyTo('no-reply@mindtronix.com');
        
        //Content
        $mail->isHTML(true);                                  
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        
       echo '<br> Message has been sent';
       
    } catch (Exception $e) {
      echo '<br> Message could not be sent. Mailer Error: '.$mail->ErrorInfo;
       
    }
}
// mailCheck('','SubjecT APR '.date(),'MessagE APR 8 2020 5:19pm');
function wildcardreplace($wildcards,$wildcardreplaces=array(),$contnent){
    $wildcards=json_decode($wildcards);
    $unused_wildcards = array_diff($wildcards, array_keys($wildcardreplaces));
    $wildcards=array_map(function($val) { return '{'.$val.'}';} , $wildcards);
    foreach($wildcardreplaces AS $key => $value)
    {
        $contnent = str_replace('{'.$key.'}', $value, $contnent);
    }
    foreach($unused_wildcards AS $key => $value)
    {
        $contnent = str_replace('{'.$value.'}', '', $contnent);
    }
    return $contnent;
}

?>
