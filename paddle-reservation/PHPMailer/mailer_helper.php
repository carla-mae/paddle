<?php
require_once __DIR__ . '/../config/env.php';

/**
 * Sends transactional email via Brevo's HTTP API (https://api.brevo.com)
 * instead of raw SMTP. Render (and several other hosts) block outbound
 * SMTP ports (25/465/587) at the network level — "Network is unreachable" —
 * so plain PHPMailer SMTP sending never gets a chance to even reach Gmail.
 * This sends over HTTPS instead, which is never blocked.
 *
 * Same function name + signature as before, so every existing call site
 * (register.php, forgot_password.php, verify_payment.php, etc.) keeps
 * working with zero changes.
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

    $apiKey      = $getEnv('BREVO_API_KEY', $config['brevo_api_key'] ?? '');
    $fromAddress = $getEnv('MAIL_FROM_ADDRESS', $getEnv('MAIL_FROM_EMAIL', $config['from_address'] ?? ''));
    $fromName    = $getEnv('MAIL_FROM_NAME', $config['from_name'] ?? 'PaddleGround');

    if ($apiKey === '' || $fromAddress === '') {
        $errorMsg = 'Missing BREVO_API_KEY or MAIL_FROM_ADDRESS. Set these in Render env vars, or in PHPMailer/mail_config.php.';
        error_log("Mailer Error: {$errorMsg}");
        file_put_contents(
            __DIR__ . '/mail_error.log',
            date('Y-m-d H:i:s') . " - ERROR - " . $errorMsg . "\n",
            FILE_APPEND
        );
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
        $errorMsg = "cURL error contacting Brevo: {$curlError}";
        error_log("Mailer Error: {$errorMsg}");
        file_put_contents(
            __DIR__ . '/mail_error.log',
            date('Y-m-d H:i:s') . " - ERROR - " . $errorMsg . "\n",
            FILE_APPEND
        );
        return false;
    }

    // Brevo returns 201 Created on success.
    if ($httpCode >= 200 && $httpCode < 300) {
        return true;
    }

    $errorMsg = "Brevo API returned HTTP {$httpCode}: {$response}";
    error_log("Mailer Error: {$errorMsg}");
    file_put_contents(
        __DIR__ . '/mail_error.log',
        date('Y-m-d H:i:s') . " - ERROR - " . $errorMsg . "\n",
        FILE_APPEND
    );

    return false;
}