# Security Policy

## Supported versions
| Version | Supported |
|---------|-----------|
| 2.0.x   | ✅ |

## Reporting a vulnerability
Please report security issues **privately** — do not open a public issue.
Email the maintainer or open a private security advisory. Include:
- A description and impact assessment.
- Steps to reproduce (or a proof-of-concept).
- Suggested remediation, if any.

We aim to acknowledge within 72 hours and ship a fix within 14 days.

## Hardening checklist (already applied)
- Device endpoints require a server-issued token; no IDOR.
- CSRF tokens on all state-changing requests.
- Secrets in `.env`; web access denied via `.htaccess`.
- Secure + HttpOnly + SameSite cookies; idle timeout.
- Uploads directory cannot execute scripts; validated + randomized names.
- Parameterized queries throughout; no raw SQL with user input.
- Login lockout after failed attempts.
- Host-header and proxy-header controls.
- Errors logged server-side, not returned to clients.

## After installation
- Delete the `install/` directory.
- Keep `uploads/` and `tmp/` outside executable scope (handled by `.htaccess`).
- Rotate `APP_SECRET_KEY` if it was ever exposed.
