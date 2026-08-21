# SMS Switch — Self-Hosted Bulk & API SMS Platform

A privacy-first, self-hosted SMS gateway. Users send and receive SMS/MMS and
run USSD requests **through their own Android phones**, managed from a clean
web dashboard. No third-party SMS aggregator, no per-message fees — your
device, your SIM, your data.

> **Security posture:** hardened to a Tier-2 standard — device-token auth on
> every API endpoint, CSRF protection, secrets in `.env`, secure session
> cookies, locked-down uploads, brute-force lockout, parameterized queries,
> and suppressed error output.

---

## Features

- **Web dashboard** — compose SMS/MMS, campaigns, contact lists, templates.
- **SIM selection** — choose which SIM slot a message uses on dual-SIM phones.
- **Multi-user & multi-device** — one install serves many users; each user can
  link several Android devices.
- **API** — REST endpoints for sending, status reporting, receiving, USSD.
- **Contacts & lists** — import CSV/XLSX, blacklists, auto-reply rules.
- **Credits & plans** — per-user credit accounting and (optional) PayPal plans.
- **Android app (SMS Switch)** — foreground-service polling that keeps working
  when the phone is locked or the screen is off (**Android 6–14**).

## Requirements

- PHP **8.1+** (8.3 recommended) with `mysqli`, `mbstring`, `openssl`, `json`,
  `curl`, `zip`, `gd`.
- MySQL **5.7+** / MariaDB **10.3+**.
- An Android phone (Android 6–14) running the SMS Switch companion app.

## One-click installation (cPanel)

1. Upload the **entire** `sms/` folder to your site
   (`public_html/sms/` or a subdomain).
2. Create a **MySQL database + user** in cPanel → MySQL® Databases.
3. In your browser open `https://your-domain.com/sms/install/`.
4. The installer runs **3 steps**: preflight → configuration → done.
   It creates `.env` (with generated `APP_SECRET_KEY`), imports the schema,
   seeds the admin account, then self-locks.
5. **Delete the `install/` folder** when finished (security).
6. Log in at `https://your-domain.com/sms/login.php`.

> Prefer manual setup? Copy `.env.example` → `.env`, fill in your DB
> credentials, then import `database/schema.sql` and create an admin user.

## Connecting an Android device

1. Install the **SMS Switch APK** — build it from `android/` via the included
   GitHub Actions workflow (`Build SMS Switch APK` → Artifacts). The bundled
   legacy `isms.apk` is reference only; it does not survive locked screens.
2. In the app: enter server URL (`…/sms/`), Android ID, numeric user ID, and
   password → **Register Device** → **Start Gateway**.
3. Allow "ignore battery optimization" so Doze never pauses polling.

## Project layout

```
sms/
├─ config.php            Secrets loader (.env)
├─ install/              One-click web installer (delete after use)
├─ database/schema.sql   Database schema + default settings
├─ includes/             CSRF, session, device-auth helpers
├─ services/             Device-facing REST API
├─ ajax/                 Dashboard AJAX
├─ model/ controller/    ORM models + DB layer
├─ uploads/ tmp/         Writable dirs (script execution disabled)
├─ vendor/               Bundled Composer dependencies
└─ isms.apk              Original APK (reference only)
```

## License

Released under the **MIT License** — see `LICENSE`.
