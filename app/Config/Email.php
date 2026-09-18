<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Email extends BaseConfig
{
    public string $fromEmail  = 'servidores@especialistashosting.com';
    public string $fromName   = 'Especialistas Hosting';
    public string $recipients = '';

    /**
     * The "user agent"
     */
    public string $userAgent = 'CodeIgniter';

    /**
     * The mail sending protocol: mail, sendmail, smtp
     * Cuando existan credenciales SMTP, cambiar a 'smtp' y completar SMTPHost/SMTPUser/SMTPPass.
     */
    public string $protocol = 'mail';

    /**
     * The server path to Sendmail.
     */
    public string $mailPath = '/usr/sbin/sendmail';

    /**
     * SMTP Server Address
     */
    public string $SMTPHost = '';

    /**
     * SMTP Username
     */
    public string $SMTPUser = '';

    /**
     * SMTP Password
     */
    public string $SMTPPass = '';

    /**
     * SMTP Port
     */
    public int $SMTPPort = 25;

    /**
     * SMTP Timeout (in seconds)
     */
    public int $SMTPTimeout = 5;

    /**
     * Enable persistent SMTP connections
     */
    public bool $SMTPKeepAlive = false;

    /**
     * SMTP Encryption.
     *
     * @var string '', 'tls' or 'ssl'. 'tls' will issue a STARTTLS command
     *             to the server. 'ssl' means implicit SSL. Connection on port
     *             465 should set this to ''.
     */
    public string $SMTPCrypto = 'tls';

    /**
     * Enable word-wrap
     */
    public bool $wordWrap = true;

    /**
     * Character count to wrap at
     */
    public int $wrapChars = 76;

    /**
     * Type of mail, either 'text' or 'html'
     */
    public string $mailType = 'text';

    /**
     * Character set (utf-8, iso-8859-1, etc.)
     */
    public string $charset = 'UTF-8';

    /**
     * Whether to validate the email address
     */
    public bool $validate = false;

    /**
     * Email Priority. 1 = highest. 5 = lowest. 3 = normal
     */
    public int $priority = 3;

    /**
     * Newline character. (Use “\r\n” to comply with RFC 822)
     */
    public string $CRLF = "\r\n";

    /**
     * Newline character. (Use “\r\n” to comply with RFC 822)
     */
    public string $newline = "\r\n";

    /**
     * Enable BCC Batch Mode.
     */
    public bool $BCCBatchMode = false;

    /**
     * Number of emails in each BCC batch
     */
    public int $BCCBatchSize = 200;

    /**
     * Enable notify message from server
     */
    public bool $DSN = false;

    public function __construct()
    {
        parent::__construct();

        $fromEmail = env('email.fromEmail');
        if (is_string($fromEmail) && $fromEmail !== '') {
            $this->fromEmail = $fromEmail;
        }

        $fromName = env('email.fromName');
        if (is_string($fromName) && $fromName !== '') {
            $this->fromName = $fromName;
        }

        $smtpHost = env('email.SMTPHost');
        if (is_string($smtpHost) && $smtpHost !== '') {
            $this->SMTPHost = $smtpHost;
        }

        $smtpUser = env('email.SMTPUser');
        if (is_string($smtpUser) && $smtpUser !== '') {
            $this->SMTPUser = $smtpUser;
        }

        $smtpPass = env('email.SMTPPass');
        if (is_string($smtpPass) && $smtpPass !== '') {
            $this->SMTPPass = $smtpPass;
        }

        $smtpPort = env('email.SMTPPort');
        if ($smtpPort !== null && $smtpPort !== '' && $smtpPort !== false) {
            $this->SMTPPort = (int) $smtpPort;
        }

        $smtpCrypto = env('email.SMTPCrypto');
        if (is_string($smtpCrypto)) {
            $this->SMTPCrypto = $smtpCrypto;
        }

        $protocol = env('email.protocol');
        if (is_string($protocol) && $protocol !== '') {
            $this->protocol = $protocol;
        } elseif ($this->SMTPHost !== '' && $this->SMTPUser !== '') {
            $this->protocol = 'smtp';
        }
    }
}
