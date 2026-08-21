# SMS Gateway — Android App (API 24–34, reworked)

This is a hardened rework of the original `isms.apk` so it keeps working when
the phone is **locked** or the **screen is off**, on Android 7 through 14.

## What changed vs the original APK
- **Foreground polling service** (`SyncService`) holds a `PARTIAL_WAKE_LOCK`
  and runs each poll under a wakelock, so sending is not killed when the CPU
  sleeps.
- **Doze-proof scheduling** via `AlarmManager.setExactAndAllowWhileIdle`
  (with a `setAndAllowWhileIdle` fallback). The next poll is always re-armed,
  so polling continues under battery saver / Doze / locked screen.
- **Boot persistence** — `BootReceiver` re-starts the service on
  `BOOT_COMPLETED` and `MY_PACKAGE_REPLACED`, so the gateway survives reboots
  and app updates without opening the app.
- **Multi-device / multi-user** — the app stores several `DeviceConfig`
  entries (server URL + androidId + userId + token) and polls each one.
- **SIM selection** — `SmsSender` maps the dashboard's 0-based SIM slot to the
  correct subscription id via `SubscriptionManager` and sends through
  `SmsManager.getSmsManagerForSubscriptionId(...)`, so the web dashboard's
  chosen SIM slot is honoured on dual-SIM phones.
- **Device-token auth** — every call to the server now sends the server-issued
  device `token` (the PHP side requires it; see the Tier-2 hardening notes).

## Server contract (secured endpoints)
The app authenticates with the `token` returned by `services/sign-in.php`
(password) or `services/register-device.php` (QR onboarding). It then calls:
- `services/get-messages.php` — fetch next pending batch (POST: token, androidId, userId, groupId)
- send via `SmsManager` on the chosen SIM
- `services/report-status.php` — report Sent/Failed per message
- `services/receive-message.php` — push received SMS back to the server
- `services/update-token.php` / `services/sign-out.php` — lifecycle

## Build
Local JDK/Android SDK are not required — the APK is built by GitHub Actions.

1. Push this `android/` folder (and `.github/workflows/build-apk.yml`) to a repo.
2. The **Build SMS Gateway APK** workflow produces a debug or signed release
   APK under **Actions → Artifacts → sms-gateway-apk**.
3. Install it, open it, add a device (Server URL ends with `/sms/`, your
   Android ID, your numeric user ID, and your password), tap **Register
   Device**, then **Start Gateway**.
4. Grant SMS / Phone / Notifications permissions when prompted, and allow the
   "ignore battery optimization" dialog so Doze never pauses polling.

### Optional release signing
Add these repo **Secrets**:
- `KEYSTORE_BASE64` — base64 of your release keystore (`base64 -w0 key.jks`)
- `KEYSTORE_PASSWORD`, `KEY_ALIAS`, `KEY_PASSWORD`

Without them the workflow builds a **debug** APK (fully functional).

## Replace the old APK
The built `app-release.apk` / `app-debug.apk` supersedes `isms.apk`. Point your
users to the new build. The server side (`sms/`) is unchanged except for the
Tier-2 security hardening.
