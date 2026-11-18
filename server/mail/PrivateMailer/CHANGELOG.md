# Changelog

All notable changes to **PrivateMailer** will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/), and this project follows Semantic Versioning.

---

## [1.3.0] - 2026-08-23

### Added

- Added license validation for PrivateMailer.
- Added remote license validation through the public license repository.
- Added license expiration checking.
- Added license status checking.
- Added license failure handling.
- Mail sending is blocked when the license is missing, inactive, expired, or unavailable.
- Added support for client-specific license keys.
- Added separate private and public license repositories for license management.

### Changed

- Mail sending now performs license validation before sending.
- PrivateMailer now requires a valid license key to send emails.

### Security

- Private license information remains outside the client package.
- Client installations only access the public license validation records.
- Private repository credentials are not included in PrivateMailer.

# [1.2.2] - 2026-08-06

### Changed
- Version number updated from 1.2.1 to 1.2.2.

### Fixed
- None.

### Added
- None.

# [1.2.1] - 2026-08-06

## Fixed

- Fixed namespace in `Config.php`.
- Standardized all classes under the `PrivateMailer` namespace.
- Improved PSR-4 autoload compatibility.

# [1.2.0] - 2026-08-05

## Added

- Generic `send()` method for sending custom HTML emails.
- Built-in Password Reset email template.
- Built-in OTP email template.
- Built-in Welcome email template.
- Built-in Verify Email template.
- Built-in Account Approved email template.
- Responsive HTML email layout.
- Dynamic branding using `.env`.
- Composer package support.
- Version constant (`Mailer::VERSION`).
- Error retrieval with `getLastError()`.

## Changed

- Improved overall package architecture.
- Centralized email template rendering.
- Standardized public mail methods.
- Improved validation for required email fields.
- Better recipient cleanup before sending emails.

## Fixed

- Removed direct output (`echo`) from the mailer.
- Improved error handling.
- Added automatic plain-text email generation (`AltBody`).

---

# [1.1.0] - 2026-08-03

## Added

- Reusable `PrivateMailer` Composer package.
- SMTP configuration using `.env`.
- PSR-4 autoloading.
- GitHub private package support.
- PHPMailer integration.
- Initial README documentation.

---

# [1.0.0] - 2026-08-01

## Added

- Initial release.
- SMTP email sending using PHPMailer.
- Basic reusable Mailer class.

---

# Roadmap

## Planned for v1.3.0

- CC support
- BCC support
- Reply-To support
- File attachments
- Embedded images
- Improved HTML templates

## Planned for v2.0.0

- Multiple email layouts
- Theme system
- Localization (multi-language support)
- Markdown email templates
- Email queue integration
