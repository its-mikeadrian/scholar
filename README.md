# phpmailer-otp-login

Login with Email OTP using PHPMailer it is a PHP template for email-based OTP authentication. It demonstrates core security practices like encrypted sessions, CSRF protection, and rate limiting but is not production-ready. Use it as a learning or prototype template, and adjust the security and UI to fit your own requirements.

## 📬 Contact
For business inquiries, please reach out via email: mikeadrian123456@gmail.com

## 📚 Usage

- Register a user → login → check your email for OTP → verify → welcome.
- “Resend OTP” is rate-limited and attempts are tracked.
- Tick “Remember Me” to stay logged in (rotating tokens).

## 🔎 Example Output
Sample OTP email body:

Subject: Your OTP Login Code
Hi <username>, your OTP is: 123456 (expires in 5 minutes)

## 🧠 Why / Motivation
This project helps developers especially beginners implement email OTP login with Gmail. It provides a clean, readable template with minimal boilerplate, strong security defaults, and clear folder structure (public/, src/, config/, database/) to make adoption easy.

## 🤝 Contributing
Contributions are welcome! Feel free to submit pull requests or open issues to help improve security, user experience, documentation, and examples. If you find this project useful, please consider giving it a ⭐️ on GitHub!

## ✨ Features
- 🔐 Encrypted sessions (AES-256-GCM) stored in DB
- 📧 Email OTP login with resend throttle & attempt limits
- 🛡️ Hardened sessions, CSRF tokens, safe cookie defaults
- 🧠 “Remember Me” with token rotation & hijack protection
- ⚙️ Simple `.env` config with optional encrypted secrets

## 🚀 Installation (XAMPP on Windows)

- Install XAMPP from `https://www.apachefriends.org/` and ensure PHP 8.2+
- Place the project in `C:\xampp\htdocs\`
- Start XAMPP Control Panel and start `Apache` and `MySQL`
- Open `http://localhost/phpmyadmin` and create DB `otp` (utf8mb4)
- Import schema: phpMyAdmin → `otp` → Import → `database/login.sql``
- Access app at `http://localhost/phpmailer-otp-login/public/`

## 🚀 Installation (Composer on Windows)
- Install Composer for Windows: download `Composer-Setup.exe` from `https://getcomposer.org/` and let it detect `php.exe` (usually `C:\xampp\php\php.exe`). Verify just type `composer --version` in PowerShell.
- In the project root, install dependencies:
  - on terminal or powershell : first type `cd C:\xampp\htdocs\phpmailer-otp-login` hit enter then type `composer install` then enter

## 🔧 .env Configuration
Create `.env` at project root:
```
# Insert these on .env

# SMTP server settings
SMTP_HOST=smtp.gmail.com
SMTP_AUTH=true
SMTP_USERNAME='youremail@gmail.com'

#encrypted password. (leave empty when using plaintext variant)
SMTP_PASSWORD_ENC=
SMTP_IV=

# Plaintext fallback (leave empty when using encrypted variant)
SMTP_PASSWORD='your-smtp-password'

# Encryption mode:
SMTP_ENCRYPTION=STARTTLS
SMTP_PORT=587

# TLS certificate verification options
SMTP_VERIFY_PEER=false
SMTP_VERIFY_PEER_NAME=false
SMTP_ALLOW_SELF_SIGNED=true

# From address and name
SMTP_FROM_ADDRESS='youremail@gmail.com' 
SMTP_FROM_NAME=otp


# Optional: SMTP test configuration
SMTP_TEST_DEBUG=2
SMTP_TEST_TO=youremail@gmail.com

# Database configuration
DB_HOST=localhost
DB_USERNAME=root
DB_PASSWORD=
DB_NAME=otp
```

## 📫 Gmail SMTP (App Password)
- Use an App Password (recommended) or OAuth.
- Steps to create an App Password:
  - Enable 2-Step Verification in your Google Account: `https://myaccount.google.com/security` → 2-Step Verification → Turn On
  - Create an App Password: Security → App passwords → Select app "Mail" and device "Windows Computer" (or "Other") → Generate
  - Copy the 16-character app password (no spaces)
  - Use these `.env` settings insert it on SMTP_PASSWORD='insert here' :