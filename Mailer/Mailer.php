<?php

namespace Codesteppers\Mailer;

use Exception;

class Mailer
{
    public function sendMail($address, $subject, $body)
    {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer();
            $mail->isSMTP();
            // $mail->SMTPDebug = 3;
            $mail->setFrom($_SERVER['SMTP_SENDER_EMAIL'], $_SERVER['SMTP_SENDER_NAME']);
            $mail->addAddress($address);
            $mail->Username = $_SERVER['SMTP_USERNAME'];
            $mail->Password = $_SERVER['SMTP_PASSWORD'];
            $mail->Host = $_SERVER['SMTP_HOST'];
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = "ssl";
            $mail->Port = $_SERVER['SMTP_PORT'];
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            $mail->isHTML(true);
            return $mail->send();
        } catch (Exception $e) {
            return false;
        }
    }
}