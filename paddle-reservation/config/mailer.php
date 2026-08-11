<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/env.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_verification_email(string $toEmail, string $toName, string $code): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'] ?? getenv('MAIL_HOST') ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'] ?? getenv('MAIL_USERNAME');
        $mail->Password   = $_ENV['MAIL_APP_PASSWORD'] ?? getenv('MAIL_APP_PASSWORD');
        $mail->SMTPSecure = (strtolower($_ENV['MAIL_ENCRYPTION'] ?? getenv('MAIL_ENCRYPTION') ?? 'tls') === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = intval($_ENV['MAIL_PORT'] ?? getenv('MAIL_PORT') ?? 587);

        $mail->setFrom($mail->Username, 'Paddle Ground Reservation');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'Your verification code';
        $mail->Body    = "<p>Your 6-digit verification code is:</p><h2>{$code}</h2><p>This code expires in 10 minutes.</p>";
        $mail->AltBody  = "Your verification code is: {$code}";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer error: " . $mail->ErrorInfo . ' | ' . $e->getMessage());
        return false;
    }
}