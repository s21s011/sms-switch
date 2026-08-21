# SMS Switch — Self-Hosted Bulk & API SMS Platform

**Version:** 2.00.01 · **License:** MIT

A privacy-first, self-hosted SMS gateway. Send and receive SMS/MMS and run
USSD requests **through your own Android phones**, managed from a clean web
dashboard. No third-party SMS aggregator, no per-message fees — your device,
your SIM, your data.

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
- **REST API** — send, status reporting, receiving, USSD, contacts.
- **Contacts & lists** — import CSV/XLSX, blacklists, auto-reply rules.
- **Credits & plans** — per-user credit accounting and (optional) PayPal plans.
- **Android app (SMS Switch)** — foreground-service polling that keeps working
  when the phone is locked or the screen is off. Supports **Android 6–14**.

---

## Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| PHP | 8.1 | 8.3 |
| Extensions | mysqli, mbstring, openssl, json, curl, zip, gd | same |
| Database | MySQL 5.7 / MariaDB 10.3 | MySQL 8 / MariaDB 10.11 |
| Android | 6.0 (API 23) | 10–14 |

---

## One-click installation (cPanel)

1. Upload the **entire** `sms/` folder to your site
   (`public_html/sms/` or a subdomain).
2. Create a **MySQL database + user** in cPanel → MySQL® Databases.
3. In your browser open `https://your-domain.com/sms/install/`.
4. The installer runs **3 steps**: preflight → configuration → done.
   It creates `.env` (with a generated `APP_SECRET_KEY`), imports the schema,
   seeds the admin account, and self-locks.
5. **Delete the `install/` folder** when finished (security).
6. Log in at `https://your-domain.com/sms/login.php`.

> Prefer manual setup? Copy `.env.example` → `.env`, fill in your DB
> credentials, then import `database/schema.sql` and create an admin user.

---

## Connecting an Android device

1. Install the **SMS Switch** APK (build it via the included GitHub Actions
   workflow — see `android/README.md`; the legacy `isms.apk` in `sms/` is
   kept for reference only).
2. In the app: enter your server URL (`…/sms/`), your Android ID (shown in
   the app), your numeric user ID, and your dashboard password.
3. Tap **Register Device**, then **Start Gateway**, and allow the
   "ignore battery optimization" prompt.
4. The device now polls every 15 seconds — even when locked or charging
   overnight.

---

## Project layout

```
SmS Switch v2.00.01/
├─ sms/                    Web application (cPanel-ready)
│  ├─ config.php           Secrets loader (.env)
│  ├─ install/             One-click web installer (delete after use)
│  ├─ database/schema.sql  Schema + default settings
│  ├─ includes/            CSRF, session, device-auth helpers
│  ├─ services/            Device-facing REST API
│  ├─ ajax/                Dashboard AJAX endpoints
│  ├─ model/ controller/   ORM models + DB layer
│  ├─ uploads/ tmp/        Writable dirs (script execution disabled)
│  ├─ vendor/              Bundled Composer dependencies
│  └─ isms.apk             Original APK (reference only)
├─ android/                SMS Switch app source (Gradle 8.9)
├─ .github/workflows/      APK build CI
└─ README.txt              This file
```

---

## Security notes

See `sms/SECURITY.md` for the full hardening checklist and vulnerability
reporting policy. Quick wins after install:

- Delete `sms/install/`.
- Keep HTTPS on; set `FORCE_HTTPS=true` in `.env`.
- Set `ALLOWED_HOSTS=your-domain.com` in `.env`.
- Rotate `APP_SECRET_KEY` if it was ever exposed.

---

## License

Released under the **MIT License** — see `sms/LICENSE`.
