<?php
require_once __DIR__ . '/vendor/autoload.php';

require_once 'config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_otp_email($to_email, $username, $otp, $is_resend = false) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_EMAIL;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_EMAIL, SMTP_NAME);
        $mail->addAddress($to_email);
        $mail->isHTML(false);
        $mail->Subject = $is_resend ? 'Your OTP Code (Resent)' : 'Your OTP Code';
        $safe_username = preg_replace('/[\r\n]+/', ' ', $username);
        $mail->Body = "Dear $safe_username,\n\nYour OTP is: $otp\nThis is valid for 5 minutes.\n\nThank you.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Email not sent: " . $mail->ErrorInfo;
    }
}
?>