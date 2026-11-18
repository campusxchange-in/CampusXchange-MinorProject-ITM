<?php

namespace PrivateMailer;

use InvalidArgumentException;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    public const VERSION = '1.2.0';

    private PHPMailer $mail;

    private License $license;

    private string $lastError = '';

    public function __construct()
    {
        $this->license = new License();
        $this->mail = new PHPMailer(true);

        $this->mail->isSMTP();
        $this->mail->Host = Config::get('MAIL_HOST');
        $this->mail->SMTPAuth = true;
        $this->mail->Username = Config::get('MAIL_USERNAME');
        $this->mail->Password = Config::get('MAIL_PASSWORD');
        $this->mail->SMTPSecure = Config::get('MAIL_ENCRYPTION');
        $this->mail->Port = (int) Config::get('MAIL_PORT');

        $this->mail->setFrom(
            Config::get('MAIL_USERNAME'),
            Config::get('MAIL_FROM_NAME')
        );

        $this->mail->isHTML(true);
    }

    /**
     * Returns the last mailer error.
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Generic email sender.
     */
    public function send(array $data): bool
    {

        $licenseKey = Config::get('PRIVATE_MAILER_LICENSE');

        if (!$this->license->isValid($licenseKey)) {
            $this->lastError =
                $this->license->getLastError();

            return false;
        }

        foreach (['to', 'subject', 'body'] as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException(
                    "Missing required field: {$field}"
                );
            }
        }

        try {

            $this->lastError = '';

            $this->mail->clearAllRecipients();
            $this->mail->clearAttachments();

            $this->mail->addAddress(
                $data['to'],
                $data['name'] ?? ''
            );

            $this->mail->Subject = $data['subject'];

            $this->mail->Body = $data['body'];

            $this->mail->AltBody =
                $data['altBody']
                ?? strip_tags($data['body']);

            return $this->mail->send();

        } catch (Exception $e) {

            $this->lastError = $this->mail->ErrorInfo;

            return false;
        }
    }

    /**
     * Send Password Reset Email.
     */
    public function sendPasswordReset(
        string $email,
        string $name,
        string $resetLink
    ): bool {

        return $this->send([

            'to' => $email,

            'name' => $name,

            'subject' => 'Password Reset',

            'body' => Templates::passwordReset(
                $name,
                $resetLink
            )

        ]);
    }

    /**
     * Send OTP Email.
     */
    public function sendOTP(
        string $email,
        string $name,
        string $otp
    ): bool {

        return $this->send([

            'to' => $email,

            'name' => $name,

            'subject' => 'Your OTP',

            'body' => Templates::otp(
                $name,
                $otp
            )

        ]);
    }

    /**
     * Send Welcome Email.
     */
    public function sendWelcome(
        string $email,
        string $name
    ): bool {

        return $this->send([

            'to' => $email,

            'name' => $name,

            'subject' => 'Welcome',

            'body' => Templates::welcome(
                $name
            )

        ]);
    }

    /**
     * Send Email Verification Email.
     */
    public function sendVerifyEmail(
        string $email,
        string $name,
        string $verifyLink
    ): bool {

        return $this->send([

            'to' => $email,

            'name' => $name,

            'subject' => 'Verify Your Email',

            'body' => Templates::verifyEmail(
                $name,
                $verifyLink
            )

        ]);
    }

    /**
     * Send Account Approved Email.
     */
    public function sendAccountApproved(
        string $email,
        string $name
    ): bool {

        return $this->send([

            'to' => $email,

            'name' => $name,

            'subject' => 'Account Approved',

            'body' => Templates::accountApproved(
                $name
            )

        ]);
    }
}

?>