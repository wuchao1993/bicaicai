<?php
namespace email;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use email_config;
use think\Config;

class Email{

    public function __construct()
    {

    }

    public static function send($sendInfo){
        try{
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->SMTPDebug = email_config::EMAIL_SMTP_DEBUG;
            $mail->SMTPAuth = email_config::EMAIL_SMTP_AUTH;
            $mail->Host = email_config::EMAIL_HOST;
            $mail->Username = email_config::EMAIL_USERNAME;
            $mail->Password = email_config::EMAIL_PASSWORD;
            $mail->SMTPSecure = email_config::EMAIL_SMTP_SECURE;
            $mail->Port = email_config::EMAIL_PORT;
            $mail->setFrom(email_config::EMAIL_FROM, email_config::EMAIL_NICKNAME);
            $addressees = Config::get('email.addressees');
            foreach ($addressees as $addressee){
                $mail->addAddress($addressee['username'], $addressee['nickname']);
            }
            $mail->isHTML(true);
            $mail->Subject = $sendInfo['subject'];
            $mail->Body    = $sendInfo['body'];
            $mail->AltBody = $sendInfo['alt_body'];
            $mail->send();
            echo 'Message has been sent';
        }catch (Exception $exception){
            echo $exception->getMessage();
        }
    }
}