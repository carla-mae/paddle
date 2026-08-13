<?php
require_once __DIR__ . '/../config/env.php';

// Prefer Composer when it is installed, but this project also ships PHPMailer
// in this directory. Requiring vendor/autoload.php unconditionally makes every
// notification fail with a fatal error on XAMPP installations without vendor/.
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
} else {
    require_once __DIR__ . '/src/Exception.php';
    require_once __DIR__ . '/src/PHPMailer.php';
    require_once __DIR__ . '/src/SMTP.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Sends transactional email via Gmail SMTP.
 * Works with every existing call site without changes.
 */
function send_smtp_mail(string $toEmail, string $toName, string $subject, string $bodyText): bool {
    $getEnv = function (string $name, string $fallback = ''): string {
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

    $configPath = __DIR__ . '/mail_config.php';
    $config = file_exists($configPath) ? (array) require $configPath : [];

    // Send via Gmail SMTP
    return send_via_gmail_smtp($toEmail, $toName, $subject, $bodyText, $getEnv, $config);
}

/**
 * Send email via Gmail SMTP using PHPMailer
 */
function send_via_gmail_smtp(string $toEmail, string $toName, string $subject, string $bodyText, callable $getEnv, array $config): bool {
    $mailHost   = $getEnv('MAIL_HOST', $config['mail_host'] ?? 'smtp.gmail.com');
    $mailPort   = (int) $getEnv('MAIL_PORT', $config['mail_port'] ?? '587');
    $mailUser   = $getEnv('MAIL_USERNAME', $config['smtp_username'] ?? '');
    $mailPass   = $getEnv('MAIL_PASSWORD', $config['smtp_password'] ?? '');
    $fromEmail  = $getEnv('MAIL_FROM_EMAIL', $config['mail_from_email'] ?? $mailUser);
    $fromName   = $getEnv('MAIL_FROM_NAME', $config['from_name'] ?? 'PaddleGround');
    $encryption = strtolower($getEnv('MAIL_ENCRYPTION', $config['mail_encryption'] ?? 'tls'));

    if ($mailUser === '' || $mailPass === '') {
        error_log("Mailer Error: Missing MAIL_USERNAME or MAIL_PASSWORD in .env or mail_config.php");
        file_put_contents(
            __DIR__ . '/mail_error.log',
            date('Y-m-d H:i:s') . " - CONFIG ERROR - Missing MAIL_USERNAME or MAIL_PASSWORD\n",
            FILE_APPEND
        );
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->SMTPDebug = 2; // Enable detailed debug output
        $mail->Debugoutput = function ($str, $level) {
            file_put_contents(
                __DIR__ . '/mail_error.log',
                date('Y-m-d H:i:s') . " - SMTP DEBUG [{$level}] - " . $str . "\n",
                FILE_APPEND
            );
        };
        
        $mail->Host       = $mailHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailUser;
        $mail->Password   = $mailPass;
        $mail->SMTPSecure = ($encryption === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $mailPort;
        $mail->Timeout    = 15; // 15 second timeout for connection
        $mail->SMTPKeepAlive = false; // Close connection after sending

        // Log configuration details
        file_put_contents(
            __DIR__ . '/mail_error.log',
            date('Y-m-d H:i:s') . " - CONFIG - Host: {$mailHost}, Port: {$mailPort}, User: {$mailUser}, Encryption: {$encryption}\n",
            FILE_APPEND
        );

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $bodyText;

        $mail->send();
        error_log("Email sent via Gmail SMTP to {$toEmail}");
        file_put_contents(
            __DIR__ . '/mail_error.log',
            date('Y-m-d H:i:s') . " - SUCCESS - Email sent to {$toEmail}\n",
            FILE_APPEND
        );
        return true;
    } catch (Exception $e) {
        error_log("Gmail SMTP Error: " . $mail->ErrorInfo . ' | ' . $e->getMessage());
        file_put_contents(
            __DIR__ . '/mail_error.log',
            date('Y-m-d H:i:s') . " - ERROR - PHPMailer: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage() . "\n",
            FILE_APPEND
        );
        return false;
    }
}
