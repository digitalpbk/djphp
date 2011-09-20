<?php
require_once('class.phpmailer.php');

class Mail {
    static function send($to, $subject, $message,$html = NULL, $from = NULL){
        if(!$from)
            $from = App::$settings->EMAIL_DEFAULT_FROM;

        $mail  = new PHPMailer();
        $mail->SetFrom($from[1], $from[0]);

        if(is_array($to)) {
            foreach($to as $e) {
                if(is_array($e)){
                    list($name, $email) = $e;
                    $mail->AddAddress($email,$name);
                }
                else {
                    $mail->AddAddress($e);
                }
            }
        }
        else {
            $mail->AddAddress($to);
        }
        
        $mail->Subject    =  $subject;


        if($html){
            $mail->AltBody    = $message;
            $mail->MsgHTML($html);
        }
        else {
            $mail->Body       = $message;
        }
        return $mail->send();
    }

    static function admins($subject, $message){
        $admins = App::$settings->ADMINS;
        if($admins){
            return self::send($admins, $subject, $message);
        }
        return 0;
    }
}
