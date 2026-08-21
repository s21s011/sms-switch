SMS Gateway — Web Installer
===========================

This folder contains the one-click web installer. It is intentionally placed
inside the webroot so you can run it from your browser.

HOW TO RUN
----------
1. Upload the entire `sms/` folder to your site (public_html/sms/ or a subdomain).
2. In your browser open: https://your-domain.com/sms/install/
3. The installer runs 3 steps:
   Step 0 – Preflight (PHP version + required extensions)
   Step 1 – Configuration form (DB credentials, admin account)
   Step 2 – Writes .env, imports schema.sql, seeds admin user, locks itself
4. When you see "SMS Gateway installed", DELETE the `install/` folder for security.

REQUIREMENTS (checked at Step 0)
--------------------------------
- PHP 8.1+ (8.3 recommended)
- Extensions: mysqli, mbstring, openssl, json, curl, zip
- MySQL 5.7+ / MariaDB 10.3+ (database + user created in cPanel first)
- Writable `sms/` directory (so .env can be created)

MANUAL SETUP (alternative)
--------------------------
If you prefer not to use the web installer:
1. Copy `.env.example` → `.env` and fill in your DB credentials.
2. Import `database/schema.sql` via phpMyAdmin or `mysql -u USER -p DB < schema.sql`.
3. Create an admin user directly in the database:
   INSERT INTO `User` (name,email,password,isAdmin,apiKey,dateAdded,timeZone)
   VALUES ('Admin','admin@example.com', <bcrypt-hash>, 1, <random-hex>, NOW(), 'UTC');
4. Delete the `install/` folder.

SECURITY NOTES
--------------
- The installer creates a lock file (`install/_lock`) after success so it
  cannot be run again. If you really need to reinstall, delete that file
  (this WILL wipe the database).
- The generated `.env` is set to 0600 permissions.
- Never commit `.env` to version control — it is in .gitignore.

TROUBLESHOOTING
---------------
- "Cannot connect to MySQL": verify host, user, password, and that the
  database exists (or the user has CREATE DATABASE privilege).
- "PHP extension X is not enabled": enable it in cPanel → Select PHP Version.
- "Directory not writable": chmod 755 the `sms/` folder (or 775 if using a
  different user/group).
- Blank page: check PHP error_log in cPanel; it usually means a fatal error
  (missing extension, syntax error, etc.).

After successful install, log in at: https://your-domain.com/sms/login.php