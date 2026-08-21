# Envato Standard — Submission Checklist (SMS Switch v2.00.01)

This package is prepared to Envato "Standard" expectations for a PHP web
application + companion Android app.

## Code quality
- [x] Readable PHP; no syntax errors (lint-clean).
- [x] No hardcoded secrets; configuration via `.env` (installer generates
      `APP_SECRET_KEY`).
- [x] No debug output / `var_dump` / `print_r` in production paths
      (`test.php` removed; remaining `print_r` occurrences are inside
      htmlentities'd API documentation examples only).
- [x] Error reporting safe (logged server-side, not shown to clients).

## Security
- [x] Authentication on every privileged endpoint (web + device API).
- [x] CSRF protection on state-changing requests.
- [x] SQL injection safe (parameterized queries; all raw string-built SQL
      with user input removed).
- [x] XSS safe (output escaped; uploads served non-executable).
- [x] Secure cookies (Secure/HttpOnly/SameSite) + 30-min idle timeout.
- [x] Upload restrictions (type/size/exec-block/random names).
- [x] Brute-force lockout on login (5 attempts / 15 min).
- [x] `.env` / `composer.*` / debug files denied via `.htaccess`.
- [x] Security headers: CSP, HSTS, X-Frame-Options, X-Content-Type-Options,
      Referrer-Policy, Permissions-Policy.
- [x] Host-header injection guard; proxy-aware HTTPS/IP detection.

## Android companion app
- [x] Works with screen off / phone locked (foreground service + wakelock +
      exact alarms + boot receiver).
- [x] Supports Android 6 (API 23) – 14 (API 34).
- [x] Multi-device / multi-user; SIM-slot selection; device-token auth.
- [x] CI build via GitHub Actions (debug + optional signed release).

## Documentation
- [x] `README.md` — overview, features, install, usage.
- [x] `CHANGELOG.md` — version history.
- [x] `SECURITY.md` — vulnerability reporting + hardening notes.
- [x] `LICENSE` — MIT.
- [x] `install/README.txt` — installer quick start.
- [x] `android/README.md` — companion app build & setup.

## Installation experience
- [x] One-click web installer (`/install`) with preflight checks.
- [x] Self-locking after install; safe to delete.
- [x] Non-standard DB ports supported end-to-end.

## Assets / licensing
- [x] De-branded (no third-party license headers / footers).
- [x] Bundled `vendor/` (no external Composer step required on the host).
- [x] Original / placeholder launcher icons and strings.

## Notes for the reviewer
- The companion Android app source lives in `android/` and is built via the
  included GitHub Actions workflow (the host does not need Android Studio).
- Database schema is in `database/schema.sql`, imported automatically by the
  installer.
- The legacy `isms.apk` in `sms/` is the original vendor APK, bundled for
  reference; it is superseded by the new CI-built SMS Switch APK.
