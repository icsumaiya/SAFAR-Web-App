<?php
// Sends transactional emails via SMTP (PHPMailer). If no email_config.php
// exists (no real SMTP credentials configured), falls back to writing
// the email to a log file instead of silently pretending it was sent —
// per project rule, we never fake email delivery.

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class EmailService
{
    private ?array $config;
    private string $logFile;

    public function __construct(?array $config, string $logFile)
    {
        $this->config = $config;
        $this->logFile = $logFile;
    }

    public function send(string $toEmail, string $toName, string $subject, string $body): bool
    {
        if ($this->config === null || empty($this->config['smtp_host'])) {
            $this->logInsteadOfSend($toEmail, $subject, $body . "\n\n[DEV MODE: no SMTP configured, email not actually sent]");
            return true;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $this->config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['smtp_user'];
            $mail->Password = $this->config['smtp_pass'];
            $mail->SMTPSecure = $this->config['smtp_secure'] ?? 'tls';
            $mail->Port = $this->config['smtp_port'] ?? 587;
            $mail->setFrom($this->config['from_email'], $this->config['from_name'] ?? 'SAFAR');
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->send();

            return true;
        } catch (PHPMailerException $e) {
            $this->logInsteadOfSend($toEmail, $subject, $body . "\n\n[SEND FAILED: {$e->getMessage()}]");
            return false;
        }
    }

    private function logInsteadOfSend(string $toEmail, string $subject, string $body): void
    {
        $entry = sprintf(
            "[%s] TO: %s | SUBJECT: %s\n%s\n---\n",
            date('Y-m-d H:i:s'),
            $toEmail,
            $subject,
            $body
        );
        @file_put_contents($this->logFile, $entry, FILE_APPEND);
    }
}