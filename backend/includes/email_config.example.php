<?php
// Copy to email_config.php (gitignored) and fill in real SMTP credentials
// (e.g. Gmail App Password, Mailtrap, etc.) if/when you have them.
// If email_config.php doesn't exist, EmailService automatically falls
// back to logging mode instead of pretending to send.

return [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_user' => 'your-email@gmail.com',
    'smtp_pass' => 'your-app-password',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',
    'from_email' => 'no-reply@safar.com',
    'from_name' => 'SAFAR',
];