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
 * Sends transactional email via Gmail SMTP or Brevo API.
 * Tries Brevo first (if configured), then falls back to Gmail SMTP.
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

    // Try Brevo first (if API key is configured)
    $brevoApiKey = $getEnv('BREVO_API_KEY', $config['brevo_api_key'] ?? '');
    if ($brevoApiKey !== '') {
        if (send_via_brevo($toEmail, $toName, $subject, $bodyText, $getEnv, $config)) {
            return true;
        }
    }

    // Fall back to Gmail SMTP
    return send_via_gmail_smtp($toEmail, $toName, $subject, $bodyText, $getEnv, $config);
}

/**
 * Send email via Brevo API
 */
function send_via_brevo(string $toEmail, string $toName, string $subject, string $bodyText, callable $getEnv, array $config): bool {
    $apiKey      = $getEnv('BREVO_API_KEY', $config['brevo_api_key'] ?? '');
    $fromAddress = $getEnv('MAIL_FROM_ADDRESS', $getEnv('MAIL_FROM_EMAIL', $config['from_address'] ?? ''));
    $fromName    = $getEnv('MAIL_FROM_NAME', $config['from_name'] ?? 'PaddleGround');

    if ($apiKey === '' || $fromAddress === '') {
        return false;
    }

    $payload = json_encode([
        'sender'      => ['name' => $fromName, 'email' => $fromAddress],
        'to'          => [['email' => $toEmail, 'name' => $toName]],
        'subject'     => $subject,
        'textContent' => $bodyText,
    ]);

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
            'api-key: ' . $apiKey,
            'content-type: application/json',
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError !== '') {
        error_log("Brevo cURL error: {$curlError}");
        return false;
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        error_log("Email sent via Brevo to {$toEmail}");
        return true;
    }

    error_log("Brevo API returned HTTP {$httpCode}: {$response}");
    return false;
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
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $mailHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailUser;
        $mail->Password   = $mailPass;
        $mail->SMTPSecure = ($encryption === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $mailPort;

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $bodyText;

        $mail->send();
        error_log("Email sent via Gmail SMTP to {$toEmail}");
        return true;
    } catch (Exception $e) {
        error_log("Gmail SMTP Error: " . $mail->ErrorInfo . ' | ' . $e->getMessage());
        file_put_contents(
            __DIR__ . '/mail_error.log',
            date('Y-m-d H:i:s') . " - ERROR - " . $mail->ErrorInfo . "\n",
            FILE_APPEND
        );
        return false;
    }
}
