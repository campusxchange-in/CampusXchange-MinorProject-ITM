# 📧 PrivateMailer

![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?logo=php)

![Composer](https://img.shields.io/badge/Composer-Package-orange)

![License](https://img.shields.io/badge/License-MIT-green)

![Version](https://img.shields.io/badge/Version-1.3.0-blue)

A reusable PHP mailer package built on top of **PHPMailer**.

PrivateMailer provides a simple and clean API for sending emails in any PHP project. It supports custom emails as well as built-in templates for common authentication workflows such as password resets, OTP verification, welcome emails, email verification, and account approval.

## Why PrivateMailer?

Instead of writing the same PHPMailer configuration in every project, PrivateMailer provides a reusable Composer package with built-in email templates, centralized SMTP configuration, and a clean API.

This allows you to focus on your application while keeping email functionality consistent across projects.

---

# ✨ Features

- 📧 Generic HTML email sender
- 🔑 Password Reset Email
- 🔢 One-Time Password (OTP)
- 👋 Welcome Email
- ✔️ Email Verification
- ✅ Account Approved Email
- 🎨 Responsive HTML email templates
- ⚙️ Environment configuration using `.env`
- 📦 Composer package
- 🔒 Built on PHPMailer
- 🧩 Easy integration into any PHP project
- 🔐 Remote license validation
- ⏳ License expiration checking
- 🛑 Automatic email blocking for invalid or expired licenses

---

# 📋 Requirements

- PHP 8.1 or higher
- Composer

---

# 📦 Installation

Install using Composer.

```bash
composer require rnick007/privatemailer
```

---

# ⚙️ Project Setup

Create a `.env` file in your project root.

Example:

```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_NAME=Your Application

MAIL_PRIMARY_COLOR=#0d6efd

MAIL_LOGO=https://example.com/logo.png

MAIL_SIGNATURE="Your Team"

APP_URL=https://example.com

PRIVATE_MAILER_LICENSE=your-license-key
```

---

# 🚀 Initialize PrivateMailer

Create an initialization file.

Example:

```
project/
│
├── init.php
├── .env
└── vendor/
```

**init.php**

```php
<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();
```

Then include it wherever you want to send emails.

```php
require 'init.php';

use PrivateMailer\Mailer;

$mailer = new Mailer();
```

---

# 📧 Send Custom Email

```php
$mailer->send([

    'to' => 'user@example.com',

    'name' => 'John Doe',

    'subject' => 'Welcome',

    'body' => '<h2>Hello!</h2><p>Welcome to our website.</p>'

]);
```

---

# 🔑 Password Reset Email

```php
$mailer->sendPasswordReset(

    $email,

    $name,

    $resetLink

);
```

---

# 🔢 Send OTP

```php
$mailer->sendOTP(

    $email,

    $name,

    $otp

);
```

---

# 👋 Welcome Email

```php
$mailer->sendWelcome(

    $email,

    $name

);
```

---

# ✔️ Verify Email

```php
$mailer->sendVerifyEmail(

    $email,

    $name,

    $verifyLink

);
```

---

# ✅ Account Approved

```php
$mailer->sendAccountApproved(

    $email,

    $name

);
```

---

# ❌ Error Handling

```php
if (!$mailer->sendOTP($email, $name, $otp)) {

    echo $mailer->getLastError();

}
```

---

# 📁 Package Structure

```
PrivateMailer/
│
├── src/
│   ├── Config.php
│   ├── Mailer.php
│   ├── License.php
│   └── Templates.php
│
├── templates/
│   ├── layouts/
│   │   └── default.php
│   │
│   ├── emails/
│   │   ├── password_reset.php
│   │   ├── otp.php
│   │   ├── welcome.php
│   │   ├── verify_email.php
│   │   └── account_approved.php
│   │
│   └── assets/
│
├── .env.example
├── CHANGELOG.md
├── LICENSE
└── README.md
```

---

# 🔄 Updating

Update the package using Composer.

```bash
composer update rnick007/privatemailer
```

---

# 📌 Version

Current Version

```
1.2.0
```

---

# 🛣️ Roadmap

### Planned for v1.4.0

- Attachments
- CC
- BCC
- Reply-To
- Embedded Images

### Planned for v2.0.0

- Multiple Layouts
- Theme System
- Localization
- Markdown Templates
- Email Queue Support

---

# 🤝 Contributing

Contributions, bug reports, feature requests, and pull requests are welcome.

Please open an Issue before submitting major changes.

---

# 📄 License

This project is licensed under the MIT License.

See the **LICENSE** file for details.

---

# 👨‍💻 Author

**Nikhil Raykwar**

GitHub:

https://github.com/RNick007

---

## ⭐ Support

If you find this package useful, consider giving it a ⭐ on GitHub.