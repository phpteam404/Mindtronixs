<?php
//error_reporting(E_ALL);
function sendmail($toid,$subject,$message,$from='')
{   //$toid = 'app.mazic@gmail.com';
    include_once('class.phpmailer.php');
    $mail    = new PHPMailer();
    $mail->IsHTML(true);
    $mail->IsSMTP();
    $from=isset($from)?$from:SMTP_MAIL_FROM;
    $fromid=SMTP_MAIL_FROM;
    $password=SMTP_MAIL_PASSWORD;
    $no_reply = SMTP_MAIL_NOREPLY;
    //$subject = eregi_replace("[\]",'',$subject);
    $subject = preg_replace("[ \ ]","",$subject);
    $mail->Host     = 'smtp.gmail.com';
    $mail->Port     = 465;
    $mail->From     = $from;
    $mail->Username     = $fromid;
    $mail->Password = $password;
    $mail->AddReplyTo($no_reply);
    $mail->Subject = $subject;
    $mail->Body=$message;
    if(is_array($toid))
    {
        for($to=0;$to<sizeof($toid);$to++)
        {
            $mail->AddAddress($toid[$to]);
        }
    }
    else
        $mail->AddAddress($toid);

    if(!$mail->Send())
    {
        //echo 'Failed to send mail';exit;
        return 0;
    }
    else {
        //echo 'Mail sent';
        return 1;
    }
    //echo "$fromid,$toid,$subject,$message"; exit;
    //include_once('class.pop3.php');
    //$mail    = new PHPMailer();
}

function mailCheck($toid,$subject,$message)
{ $toid = 'app.mazic@gmail.com';
    include_once('class.phpmailer.php');
    $mail    = new PHPMailer();
    $mail->IsHTML(true);
    $mail->IsSMTP();
    $fromid='app.mazic@gmail.com';
    $password='app_mazic.';
    //$subject = eregi_replace("[\]",'',$subject);
    $subject = preg_replace("[ \ ]","",$subject);
    $mail->From     = $fromid;
    $mail->Username     = $fromid;
    $mail->Password = $password;
    $mail->AddReplyTo($fromid);
    $mail->Subject = $subject;
    $mail->Body=$message;
    if(is_array($toid))
    {
        for($to=0;$to<sizeof($toid);$to++)
        {
            $mail->AddAddress($toid[$to]);
        }
    }
    else
        $mail->AddAddress($toid);


    if(!$mail->Send())
    {
        //echo 'Failed to send mail';
        return 0;
    }
    else
    {
        //echo 'Mail sent';
        return 1;
    }
    //echo "$fromid,$toid,$subject,$message"; exit;
    //include_once('class.pop3.php');
    //$mail    = new PHPMailer();
}

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
