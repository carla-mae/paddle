<?php
require_once __DIR__ . '/src/Exception.php';
require_once __DIR__ . '/src/PHPMailer.php';
require_once __DIR__ . '/src/SMTP.php';
require_once __DIR__ . '/../config/env.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_smtp_mail(string $toEmail, string $toName, string $subject, string $bodyText): bool {
    $getEnv = function(string $name, string $fallback = ''): string {
        $value = getenv($name);
        if ($value !== false && trim($value) !== '') {
            return trim($value);
        }
        if (isset($_ENV[$name]) && trim((string)$_ENV[$name]) !== '') {
            return trim((string)$_ENV[$name]);
        }
        if (isset($_SERVER[$name]) && trim((string)$_SERVER[$name]) !== '') {
            return trim((string)$_SERVER[$name]);
        }
        return $fallback;
    };

    $envHost = $getEnv('MAIL_HOST', $getEnv('SMTP_HOST', ''));
    $envUser = $getEnv('MAIL_USERNAME', $getEnv('SMTP_USERNAME', ''));
    $envPass = str_replace(' ', '', $getEnv('MAIL_APP_PASSWORD', $getEnv('MAIL_PASSWORD', '')));
    $envPort = $getEnv('MAIL_PORT', $getEnv('SMTP_PORT', ''));
    $envEncryption = $getEnv('MAIL_ENCRYPTION', $getEnv('SMTP_ENCRYPTION', ''));
    $envFromName = $getEnv('MAIL_FROM_NAME', $getEnv('SMTP_FROM_NAME', ''));
    $envFromAddress = $getEnv('MAIL_FROM_ADDRESS', $getEnv('SMTP_FROM_ADDRESS', ''));

    $configPath = __DIR__ . '/mail_config.php';
    $config = file_exists($configPath) ? (array) require $configPath : [];

    $host = $envHost !== '' ? $envHost : ($config['smtp_host'] ?? 'smtp.gmail.com');
    $username = $envUser !== '' ? $envUser : trim($config['smtp_username'] ?? '');
    $password = $envPass !== '' ? $envPass : str_replace(' ', '', trim($config['smtp_password'] ?? ''));
    $port = $envPort !== '' ? (int)$envPort : 587;
    $encryption = $envEncryption !== '' ? $envEncryption : 'tls';
    $fromName = $envFromName !== '' ? $envFromName : ($config['from_name'] ?? 'PaddleGround');
    $fromAddress = $envFromAddress !== '' ? $envFromAddress : $username;

    if ($username === '' || $password === '') {
        error_log("Mailer Error: SMTP username or password is missing. Set MAIL_USERNAME and MAIL_APP_PASSWORD in Render, or configure PHPMailer/mail_config.php.");
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $username;
        $mail->Password   = $password;
        $mail->SMTPSecure = (strtolower($encryption) === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $port;
        $mail->CharSet    = 'UTF-8';
        $mail->isHTML(false);

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]
        ];

        $mail->setFrom($fromAddress ?: $username, $fromName ?: 'PaddleGround');
        $mail->addAddress($toEmail, $toName);

        $mail->Subject = $subject;
        $mail->Body    = $bodyText;

        $mail->send();
        return true;

    } catch (Exception $e) {
        $errorMsg = $mail->ErrorInfo ?: $e->getMessage();
        error_log("Mailer Error: {$errorMsg}");

        file_put_contents(
            __DIR__ . '/mail_error.log',
            date('Y-m-d H:i:s') . " - ERROR - " . $errorMsg . "\n",
            FILE_APPEND
        );

        return false;
    }
}