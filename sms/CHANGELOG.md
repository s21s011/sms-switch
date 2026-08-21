# SMS Switch v2.00.01 — Changelog

## 2.00.01 — SMS Switch (Envato Standard Release)

### Web application (`sms/`)
- **Rebrand:** product renamed **SMS Switch**; no third-party license
  headers, purchase-code checks, or Envato validation anywhere.
- **Security hardening (Tier-2):**
  - Device-token authentication on every `/services/*` endpoint
    (previously unauthenticated → IDOR / message disclosure / status
    forgery fixed).
  - Real per-session CSRF tokens on all state-changing AJAX requests.
  - Secrets moved to `.env`; `.htaccess` denies web access to `.env`,
    `composer.json`, `composer.lock`, and debug artifacts.
  - Secure + HttpOnly + SameSite session cookies; 30-minute idle timeout.
  - Login brute-force lockout (5 attempts / 15 min, per IP + email).
  - Uploads directory: PHP execution disabled; randomized file names;
    extension allow-list; per-user subdirectories.
  - Parameterized queries throughout — all string-concatenated SQL with
    user input removed (cron, dashboard, status counters, credit
    accounting).
  - Host-header injection guard (`ALLOWED_HOSTS`); proxy-aware HTTPS
    detection (`TRUSTED_PROXIES`).
  - Security headers: CSP, HSTS, X-Frame-Options, X-Content-Type-Options,
    Referrer-Policy, Permissions-Policy.
  - `APP_SECRET_KEY` no longer ships with a default — the installer
    generates a 64-hex random key and refuses production use without one.
- **Installer:** one-click web installer (preflight → config → install).
  Writes `.env` with generated secrets, imports schema idempotently,
  seeds the admin account, then self-locks (`install/_lock`).
- **DB_PORT support:** non-standard MySQL ports now honored everywhere
  (config, ORM, CLI tools, installer).
- **Debug artifacts removed:** `test.php` (open mail-relay tester) deleted;
  no `display_errors` in production paths.

### Android app (`android/`) — fixes the locked-screen problem
- **Reworked from the original `isms.apk`:**
  - Foreground `SyncService` with `PARTIAL_WAKE_LOCK` — sending continues
    with the screen off and the phone locked.
  - Doze-proof scheduling via `AlarmManager.setExactAndAllowWhileIdle`
    (with `setAndAllowWhileIdle` fallback); each poll re-arms the next.
  - Boot persistence — `BootReceiver` restarts polling on
    `BOOT_COMPLETED` and `MY_PACKAGE_REPLACED` (survives reboots and
    app updates without opening the app).
  - Multi-device / multi-user — several server configs polled in one app.
  - SIM-slot selection — dashboard's chosen SIM honored on dual-SIM
    phones via `SubscriptionManager`.
  - Device-token auth — matches the hardened server contract.
  - **Android 6 (API 23) through 14 (API 34)** supported: runtime
    permissions, notification channels, foreground-service types,
    exact-alarm fallbacks.
- **Build:** Gradle 8.9, AGP 8.5.2, JDK 17, compileSdk 34, minSdk 24.
  GitHub Actions workflow builds debug + (optionally signed) release APKs.
- The original `isms.apk` is bundled at `sms/isms.apk` for reference; the
  new CI-built APK supersedes it.
