<?php

namespace App\Services;

use Config\Services;

/**
 * Envío de correo HTML vía Services::email() / Config\Email.
 * No hardcodea credenciales SMTP; Config\Email las toma de email.* en .env.
 */
class AppMailer
{
    private ?string $lastError = null;

    /**
     * @param array<string> $to
     * @param array<string> $cc
     * @param array<string> $absoluteAttachmentPaths Rutas absolutas en disco
     */
    public function sendHtml(
        array $to,
        array $cc,
        string $subject,
        string $bodyHtml,
        array $absoluteAttachmentPaths = []
    ): bool {
        $this->lastError = null;

        $email = Services::email();
        $config = config('Email');

        $email->clear(true);
        $email->setFrom($config->fromEmail, $config->fromName);
        $email->setMailType('html');

        $to = array_values(array_filter(array_map('trim', $to)));
        $cc = array_values(array_filter(array_map('trim', $cc)));

        if (empty($to)) {
            $this->lastError = 'No hay destinatarios válidos.';

            return false;
        }

        $email->setTo($to);

        if (!empty($cc)) {
            $email->setCC($cc);
        }

        $email->setSubject($subject);
        $email->setMessage($bodyHtml);

        foreach ($absoluteAttachmentPaths as $path) {
            if (is_file($path)) {
                $email->attach($path);
            }
        }

        if (!$email->send(false)) {
            $this->lastError = $email->printDebugger(['headers', 'subject', 'body']);

            return false;
        }

        return true;
    }

    public function getError(): ?string
    {
        return $this->lastError;
    }
}
