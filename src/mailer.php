<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/../config/env.php';

if (!function_exists('configureMailer')) {
    function configureMailer(PHPMailer $mail): void
    {
        loadEnv();
        $mail->isSMTP();
        $mail->SMTPDebug = 0;
        $mail->Host = (string) env_get('SMTP_HOST', 'smtp.gmail.com');
        $mail->SMTPAuth = filter_var(env_get('SMTP_AUTH', 'true'), FILTER_VALIDATE_BOOLEAN);
        $mail->Username = (string) env_get('SMTP_USERNAME', '');
        $pwd = smtp_password();
        if ($pwd !== null) {
            $mail->Password = $pwd;
        }

        $enc = strtoupper((string) env_get('SMTP_ENCRYPTION', 'SMTPS'));
        if ($enc === 'SMTPS' || $enc === 'SSL') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $defaultPort = 465;
        } elseif ($enc === 'STARTTLS' || $enc === 'TLS') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $defaultPort = 587;
        } else {
            // Fallback: keep as provided string if PHPMailer accepts; default to SMTPS
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $defaultPort = 465;
        }
        $mail->Port = (int) env_get('SMTP_PORT', (string) $defaultPort);

        $verifyPeer = filter_var(env_get('SMTP_VERIFY_PEER', 'true'), FILTER_VALIDATE_BOOLEAN);
        $verifyPeerName = filter_var(env_get('SMTP_VERIFY_PEER_NAME', 'true'), FILTER_VALIDATE_BOOLEAN);
        $allowSelfSigned = filter_var(env_get('SMTP_ALLOW_SELF_SIGNED', 'false'), FILTER_VALIDATE_BOOLEAN);
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => $verifyPeer,
                'verify_peer_name' => $verifyPeerName,
                'allow_self_signed' => $allowSelfSigned,
            ],
        ];
    }
}

if (!function_exists('applyFromAddress')) {
    function applyFromAddress(PHPMailer $mail, ?string $fallbackName = null): void
    {
        $fromAddress = env_get('SMTP_FROM_ADDRESS', env_get('SMTP_USERNAME'));
        $fromName = env_get('SMTP_FROM_NAME', $fallbackName ?: 'Scholar');
        if ($fromAddress) {
            $mail->setFrom($fromAddress, (string) $fromName);
        }
    }
}
