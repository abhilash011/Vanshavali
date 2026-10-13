<?php

declare(strict_types=1);

namespace Vanshavali\Services;

/**
 * Email service for sending transactional emails.
 *
 * In development mode, emails are logged to file instead of sent.
 * In production, this can be swapped for SMTP / Mailgun / SES.
 */
class EmailService
{
    private string $fromEmail;
    private string $fromName;
    private string $appUrl;
    private bool $isDev;

    public function __construct()
    {
        $this->fromEmail = $_ENV['MAIL_FROM_EMAIL'] ?? 'noreply@vanshavali.com';
        $this->fromName  = $_ENV['MAIL_FROM_NAME'] ?? 'Vanshavali';
        $this->appUrl    = $_ENV['APP_FRONTEND_URL'] ?? 'http://localhost:3000';
        $this->isDev     = ($_ENV['APP_ENV'] ?? 'development') === 'development';
    }

    /**
     * Send email verification link.
     */
    public function sendVerificationEmail(string $toEmail, string $userName, string $token): bool
    {
        $verifyUrl = "{$this->appUrl}/verify-email?token={$token}";

        $subject = 'Verify Your Vanshavali Account';
        $htmlBody = $this->renderTemplate('verify_email', [
            'name'       => $userName,
            'verify_url' => $verifyUrl,
        ]);

        return $this->send($toEmail, $subject, $htmlBody);
    }

    /**
     * Send password reset link.
     */
    public function sendPasswordResetEmail(string $toEmail, string $userName, string $token): bool
    {
        $resetUrl = "{$this->appUrl}/reset-password?token={$token}";

        $subject = 'Reset Your Vanshavali Password';
        $htmlBody = $this->renderTemplate('password_reset', [
            'name'      => $userName,
            'reset_url' => $resetUrl,
        ]);

        return $this->send($toEmail, $subject, $htmlBody);
    }

    /**
     * Send an email. In dev mode, writes to log file.
     */
    private function send(string $to, string $subject, string $htmlBody): bool
    {
        if ($this->isDev) {
            return $this->logEmail($to, $subject, $htmlBody);
        }

        // Production: use PHP mail() or SMTP library
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$this->fromName} <{$this->fromEmail}>\r\n";

        return mail($to, $subject, $htmlBody, $headers);
    }

    /**
     * Log email to file (dev mode fallback).
     */
    private function logEmail(string $to, string $subject, string $body): bool
    {
        $logDir = dirname(__DIR__, 2) . '/logs/emails';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $filename = $logDir . '/' . date('Y-m-d_H-i-s') . '_' . md5($to . microtime()) . '.html';
        $content = <<<EOT
<!--
To: {$to}
Subject: {$subject}
Date: {$this->timestamp()}
-->
{$body}
EOT;

        file_put_contents($filename, $content);

        // Also log a summary for easy viewing
        $summaryFile = $logDir . '/email_log.txt';
        $summary = "[{$this->timestamp()}] To: {$to} | Subject: {$subject} | File: {$filename}\n";
        file_put_contents($summaryFile, $summary, FILE_APPEND | LOCK_EX);

        return true;
    }

    /**
     * Render a simple email template.
     */
    private function renderTemplate(string $templateName, array $vars): string
    {
        $templates = [
            'verify_email' => <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #92400e, #ca8a04); padding: 30px; border-radius: 16px 16px 0 0; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">🕉️ Vanshavali</h1>
        <p style="color: #fef3c7; margin: 8px 0 0;">Verify Your Email</p>
    </div>
    <div style="background: #fff; border: 1px solid #e5e7eb; border-top: none; padding: 30px; border-radius: 0 0 16px 16px;">
        <p style="color: #374151; font-size: 16px;">Hello <strong>{name}</strong>,</p>
        <p style="color: #6b7280; line-height: 1.6;">Thank you for joining Vanshavali! Please verify your email address by clicking the button below:</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{verify_url}" style="background: linear-gradient(135deg, #92400e, #ca8a04); color: white; padding: 14px 32px; border-radius: 12px; text-decoration: none; font-weight: 600; display: inline-block;">Verify My Email</a>
        </div>
        <p style="color: #9ca3af; font-size: 13px;">If the button doesn't work, copy this link:<br><a href="{verify_url}" style="color: #92400e;">{verify_url}</a></p>
        <p style="color: #9ca3af; font-size: 13px;">This link expires in 24 hours.</p>
    </div>
</body>
</html>
HTML,
            'password_reset' => <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #92400e, #ca8a04); padding: 30px; border-radius: 16px 16px 0 0; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 24px;">🕉️ Vanshavali</h1>
        <p style="color: #fef3c7; margin: 8px 0 0;">Password Reset</p>
    </div>
    <div style="background: #fff; border: 1px solid #e5e7eb; border-top: none; padding: 30px; border-radius: 0 0 16px 16px;">
        <p style="color: #374151; font-size: 16px;">Hello <strong>{name}</strong>,</p>
        <p style="color: #6b7280; line-height: 1.6;">We received a request to reset your password. Click the button below to create a new one:</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{reset_url}" style="background: linear-gradient(135deg, #92400e, #ca8a04); color: white; padding: 14px 32px; border-radius: 12px; text-decoration: none; font-weight: 600; display: inline-block;">Reset Password</a>
        </div>
        <p style="color: #9ca3af; font-size: 13px;">If the button doesn't work, copy this link:<br><a href="{reset_url}" style="color: #92400e;">{reset_url}</a></p>
        <p style="color: #9ca3af; font-size: 13px;">This link expires in 1 hour. If you didn't request this, you can safely ignore this email.</p>
    </div>
</body>
</html>
HTML,
        ];

        $html = $templates[$templateName] ?? '<p>Email template not found.</p>';

        foreach ($vars as $key => $value) {
            $html = str_replace('{' . $key . '}', htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'), $html);
        }

        return $html;
    }

    private function timestamp(): string
    {
        return date('Y-m-d H:i:s');
    }
}
