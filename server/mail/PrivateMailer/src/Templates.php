<?php

namespace PrivateMailer;

class Templates
{
    /**
     * Render template inside the common layout.
     */
    private static function render(string $template, array $data = []): string
    {
        extract($data, EXTR_SKIP);

        $templatePath =
        __DIR__
        . "/../templates/emails/{$template}.php";

        if (!file_exists($templatePath)) {
            throw new \RuntimeException(
                "Email template '{$template}' not found."
            );
        }

        // Render the email content
        ob_start();

        require $templatePath;

        $content = ob_get_clean();

        // Default title
        $title ??= Config::get('MAIL_FROM_NAME');

        // Wrap in the common layout
        ob_start();

        require __DIR__ . "/../templates/layouts/default.php";

        return ob_get_clean();
    }

    public static function passwordReset(
        string $name,
        string $resetLink
    ): string {

        return self::render(
            "password_reset",
            compact(
                "name",
                "resetLink"
            )
        );
    }

    public static function otp(
        string $name,
        string $otp
    ): string {

        return self::render(
            "otp",
            compact(
                "name",
                "otp"
            )
        );
    }

    public static function welcome(
        string $name
    ): string {

        return self::render(
            "welcome",
            compact("name")
        );
    }

    public static function verifyEmail(
        string $name,
        string $verifyLink
    ): string {

        return self::render(
            "verify_email",
            compact(
                "name",
                "verifyLink"
            )
        );
    }

    public static function accountApproved(
        string $name
    ): string {

        return self::render(
            "account_approved",
            compact("name")
        );
    }
}

?>