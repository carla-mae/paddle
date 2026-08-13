<?php
/**
 * Test script to diagnose Gmail SMTP configuration
 * Run this file in your browser to test the email sending
 */

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/PHPMailer/mailer_helper.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Email Configuration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; }
        .box { border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .success { background: #d4edda; border-color: #28a745; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>📧 PaddleGround Email Configuration Test</h1>

    <div class="box info">
        <h2>Environment Variables Loaded</h2>
        <?php
        $vars = [
            'MAIL_HOST' => getenv('MAIL_HOST') ?: ($_ENV['MAIL_HOST'] ?? 'NOT FOUND'),
            'MAIL_PORT' => getenv('MAIL_PORT') ?: ($_ENV['MAIL_PORT'] ?? 'NOT FOUND'),
            'MAIL_USERNAME' => getenv('MAIL_USERNAME') ?: ($_ENV['MAIL_USERNAME'] ?? 'NOT FOUND'),
            'MAIL_PASSWORD' => '***' . substr(getenv('MAIL_PASSWORD') ?: ($_ENV['MAIL_PASSWORD'] ?? ''), -4),
            'MAIL_FROM_EMAIL' => getenv('MAIL_FROM_EMAIL') ?: ($_ENV['MAIL_FROM_EMAIL'] ?? 'NOT FOUND'),
            'MAIL_FROM_NAME' => getenv('MAIL_FROM_NAME') ?: ($_ENV['MAIL_FROM_NAME'] ?? 'NOT FOUND'),
            'MAIL_ENCRYPTION' => getenv('MAIL_ENCRYPTION') ?: ($_ENV['MAIL_ENCRYPTION'] ?? 'NOT FOUND'),
        ];

        foreach ($vars as $key => $value) {
            echo "<p><strong>{$key}:</strong> <code>{$value}</code></p>";
        }

        // Check if all required vars are present
        $mailUsername = getenv('MAIL_USERNAME') ?: ($_ENV['MAIL_USERNAME'] ?? '');
        $mailPassword = getenv('MAIL_PASSWORD') ?: ($_ENV['MAIL_PASSWORD'] ?? '');
        $mailFromEmail = getenv('MAIL_FROM_EMAIL') ?: ($_ENV['MAIL_FROM_EMAIL'] ?? '');

        if (empty($mailUsername) || empty($mailPassword)) {
            echo "<p style='color: red;'><strong>❌ ERROR:</strong> MAIL_USERNAME or MAIL_PASSWORD is missing!</p>";
        } else {
            echo "<p style='color: green;'><strong>✅ OK:</strong> Required email variables are configured.</p>";
        }
        ?>
    </div>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
        $testEmail = trim($_POST['test_email'] ?? '');
        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            echo '<div class="box error"><h2>❌ Invalid Email</h2><p>Please enter a valid email address.</p></div>';
        } else {
            echo '<div class="box info"><h2>🔄 Sending Test Email...</h2>';
            echo '<p>Attempting to send test email to: <strong>' . htmlspecialchars($testEmail) . '</strong></p>';
            
            $subject = 'PaddleGround Email Test - ' . date('Y-m-d H:i:s');
            $body = "This is a test email from PaddleGround.\n\nIf you received this, your email configuration is working correctly!\n\nSent at: " . date('Y-m-d H:i:s');
            
            $result = send_smtp_mail($testEmail, 'Test User', $subject, $body);
            
            if ($result) {
                echo '<p style="color: green; font-size: 18px;"><strong>✅ SUCCESS!</strong></p>';
                echo '<p>The email was sent successfully. Check your inbox and spam folder.</p>';
            } else {
                echo '<p style="color: red; font-size: 18px;"><strong>❌ FAILED</strong></p>';
                echo '<p>The email could not be sent. Check the error log below.</p>';
            }
            echo '</div>';
        }
    }
    ?>

    <div class="box">
        <h2>Send Test Email</h2>
        <form method="POST">
            <p>
                <label for="test_email">Email Address:</label><br>
                <input type="email" id="test_email" name="test_email" placeholder="your-email@gmail.com" required style="width: 300px; padding: 8px;">
            </p>
            <button type="submit">Send Test Email</button>
        </form>
    </div>

    <div class="box info">
        <h2>Recent Error Log</h2>
        <?php
        $logFile = __DIR__ . '/PHPMailer/mail_error.log';
        if (file_exists($logFile)) {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES);
            $recentLines = array_slice($lines, -30); // Last 30 lines
            echo '<pre style="background: #f5f5f5; padding: 10px; overflow-x: auto; max-height: 400px;">';
            foreach ($recentLines as $line) {
                // Mask sensitive info
                $line = preg_replace('/MAIL_PASSWORD["\']?\s*[:=]\s*["\']?([^"\';\s]+)/', 'MAIL_PASSWORD=***', $line);
                echo htmlspecialchars($line) . "\n";
            }
            echo '</pre>';
        } else {
            echo '<p>No error log found yet. Send a test email to create one.</p>';
        }
        ?>
    </div>

    <div class="box info">
        <h2>Troubleshooting Steps</h2>
        <ol>
            <li><strong>Verify Gmail Account:</strong> Make sure you're using the correct Gmail address in MAIL_USERNAME</li>
            <li><strong>Check App Password:</strong> Go to <a href="https://myaccount.google.com/apppasswords" target="_blank">myaccount.google.com/apppasswords</a>
                <ul>
                    <li>Ensure 2-factor authentication is enabled first</li>
                    <li>Generate a new App Password for "Mail" on "Windows Computer"</li>
                    <li>Copy the 16-character password and update MAIL_PASSWORD in .env</li>
                </ul>
            </li>
            <li><strong>Check .env File:</strong> Verify the file is in the root directory and contains valid values</li>
            <li><strong>Clear Error Log:</strong> Delete PHPMailer/mail_error.log to reset debug info</li>
            <li><strong>Restart Server:</strong> If using a local server, restart it after updating .env</li>
        </ol>
    </div>
</body>
</html>
