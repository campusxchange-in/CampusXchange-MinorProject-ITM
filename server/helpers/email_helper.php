
<?php

/* =====================================================
   CAMPUSXCHANGE SHARED EMAIL HELPER
===================================================== */

require_once __DIR__ . '/../config/env_loader.php';

require_once __DIR__ . '/../mail/PrivateMailer/vendor/autoload.php';

use PrivateMailer\Mailer;

/* =====================================================
   CREATE MAILER INSTANCE
===================================================== */

function cx_mailer(): Mailer
{
    static $mailer = null;

    if ($mailer === null) {
        $mailer = new Mailer();
    }

    return $mailer;
}

/* =====================================================
   SEND EMAIL VERIFICATION
===================================================== */

function cx_send_verification_email(
    string $email,
    string $name,
    string $verificationLink
): bool {
    return cx_mailer()->sendVerifyEmail(
        $email,
        $name,
        $verificationLink
    );
}

/* =====================================================
   SEND PASSWORD RESET
===================================================== */

function cx_send_password_reset(
    string $email,
    string $name,
    string $resetLink
): bool {
    return cx_mailer()->sendPasswordReset(
        $email,
        $name,
        $resetLink
    );
}

/* =====================================================
   SEND OTP
===================================================== */

function cx_send_otp(
    string $email,
    string $name,
    string $otp
): bool {
    return cx_mailer()->sendOTP(
        $email,
        $name,
        $otp
    );
}

/* =====================================================
   SEND WELCOME EMAIL
===================================================== */

function cx_send_welcome_email(
    string $email,
    string $name
): bool {
    return cx_mailer()->sendWelcome(
        $email,
        $name
    );
}

/* =====================================================
   SEND ACCOUNT APPROVED EMAIL
===================================================== */

function cx_send_account_approved_email(
    string $email,
    string $name
): bool {
    return cx_mailer()->sendAccountApproved(
        $email,
        $name
    );
}

/* =====================================================
   GET LAST MAILER ERROR
===================================================== */

function cx_mailer_last_error(): string
{
    return cx_mailer()->getLastError();
}

?>
