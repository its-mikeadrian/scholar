## Current State
- Admin “Forgot password?” is UI-only: it never calls a backend endpoint and only closes after a timeout.
- There is no password-reset route, handler, or DB table.

## Goal
- Implement a secure, working admin password reset via email link, with CSRF protection, rate limiting, one-time tokens, and no account enumeration.

## Database
- Add a new table (e.g. `password_reset_tokens`) using the same selector/validator pattern already used by Remember Me:
  - Columns: `id`, `user_id`, `selector` (unique), `validator_hash`, `expires_at`, `used_at`, `ip_address`, `user_agent`, `created_at`.
  - Indexes on `selector`, `user_id`, `expires_at`.
- Provide a SQL file in `database/` and also create the table at runtime if missing (matches existing patterns like `login_audit`).

## Routes
- Add routes in [routes.php](file:///c:/xampp/htdocs/scholar/config/routes.php) for:
  - `POST /admin/forgot-password` → request reset email.
  - `GET /admin/reset-password` → show reset form (validates token).
  - `POST /admin/reset-password` → set new password (validates token + CSRF).

## Backend Handlers
- Create new public scripts:
  - `public/admin_forgot_password.php`
    - Validate method + CSRF.
    - Accept email; always respond with the same success message.
    - If email matches an active `admin|superadmin`, create a reset token:
      - `selector = bin2hex(random_bytes(8))`
      - `validator = bin2hex(random_bytes(32))`
      - store `hash('sha256', validator)`.
    - Rate limit by IP and by user (e.g., min 60s between emails; cap per 15 minutes).
    - Send email via existing `src/mailer.php`.
    - Audit to `login_audit` with outcomes like `pw_reset_requested` / `pw_reset_sent`.

  - `public/admin_reset_password.php`
    - GET: validate `selector` + `token` + expiry + unused; show “Set new password” form.
    - POST: validate CSRF, token, password policy, then:
      - update `users.password = password_hash(...)`.
      - mark token `used_at = NOW()`.
      - revoke sessions (`DELETE FROM sessions WHERE user_id = ?`) and Remember Me tokens (`DELETE FROM remember_tokens WHERE user_id = ?`).
      - audit `pw_reset_completed`.

## Frontend Wiring (Admin Login)
- Update [login_page.php](file:///c:/xampp/htdocs/scholar/public/login_page.php) modal to be a real `<form>` posting to `/admin/forgot-password` and include `csrf_input()`.
- Update [admin/script.js](file:///c:/xampp/htdocs/scholar/public/admin/script.js) to stop faking the request; just validate email client-side and submit the modal form.

## Security Hardening (Directly Related)
- Ensure reset flow:
  - No user enumeration (same message whether email exists).
  - One-time tokens, short expiry (e.g., 30 minutes).
  - Constant-time token comparison.
  - CSRF on request + submit.
  - Rate limiting.
  - Session/token revocation on password change.
- Optional but recommended: change `src/mailer.php` defaults to verify TLS peers unless explicitly disabled by env.

## Verification
- Manual test matrix:
  - Unknown email → shows generic success, sends nothing.
  - Valid admin email → receives link; link opens reset form.
  - Expired/used/invalid token → rejected.
  - Successful reset → can log in with new password; old sessions/remember-me invalidated.
  - Rate limit → repeated requests blocked.

If you confirm, I’ll implement the above (new routes + new PHP scripts + UI wiring + SQL) and then run a quick end-to-end smoke test locally.