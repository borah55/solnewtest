# Installation Guide

This guide walks you through installing **Solnew** on a fresh server, step by
step. If you only need a quick summary, the README has one. This file goes
further and covers the things that usually trip people up: SMTP, captcha,
FaucetPay, shortlinks, Nginx config, and the most common errors.

> If you are upgrading from the legacy script, jump to
> [**Upgrading**](#upgrading-from-the-legacy-script) at the bottom.

---

## Table of contents

1. [Server requirements](#1-server-requirements)
2. [Get the code](#2-get-the-code)
3. [File permissions](#3-file-permissions)
4. [Web server config](#4-web-server-config)
5. [Run the installer](#5-run-the-installer)
6. [First admin login](#6-first-admin-login)
7. [Site settings](#7-site-settings)
8. [Captcha setup](#8-captcha-setup)
9. [FaucetPay setup](#9-faucetpay-setup)
10. [Shortlink setup](#10-shortlink-setup)
11. [SMTP / email setup](#11-smtp--email-setup)
12. [Going to production (HTTPS)](#12-going-to-production-https)
13. [Troubleshooting](#13-troubleshooting)
14. [Upgrading from the legacy script](#14-upgrading-from-the-legacy-script)

---

## 1. Server requirements

| Requirement | Minimum | Recommended |
|-------------|---------|-------------|
| PHP         | 7.1     | 8.1 or 8.2  |
| MySQL       | 5.6     | 8.0 / MariaDB 10.5+ |
| Web server  | Apache 2.4 with `mod_rewrite` | Apache or Nginx |
| Disk        | 50 MB   | 200 MB free for templates and logs |

**PHP extensions required** (the installer's pre-flight will refuse to run
otherwise):

- `mbstring`
- `curl`
- `mysqli` with `mysqlnd`
- `openssl`
- `password_hash` and `random_bytes` (built-in on 7.1+, but some hosts
  disable them)

On Ubuntu / Debian:

```bash
sudo apt update
sudo apt install php php-mbstring php-curl php-mysql php-mysqlnd php-xml \
                 mariadb-server apache2 libapache2-mod-php
sudo a2enmod rewrite
sudo systemctl restart apache2
```

On CentOS / RHEL / Alma / Rocky:

```bash
sudo dnf install php php-mbstring php-curl php-mysqlnd php-xml \
                 mariadb-server httpd
sudo systemctl enable --now httpd mariadb
```

---

## 2. Get the code

### Option A: Git clone (recommended)

```bash
cd /var/www
sudo git clone https://github.com/borah55/solnewtest.git solnew
cd solnew
```

### Option B: Download a release

Grab the latest tag from the **Releases** page on GitHub, unzip it, and
upload the contents to your webroot via SFTP.

Either way, the project root must end up containing `index.php`,
`.htaccess`, and the `config/`, `controllers/`, `lib/`, etc. directories.

---

## 3. File permissions

The web server user (`www-data`, `apache`, `nginx`, depending on the
distro) needs read access to everything and **write access** to:

- `config/`           - the installer writes `config.php` here
- `resources/`        - so the installer can delete itself when finished
- `storage/`          - cache and logs
- `storage/cache/`
- `storage/cache/templates_c/`
- `storage/log/`

```bash
# Replace www-data with apache / nginx if applicable.
sudo chown -R www-data:www-data /var/www/solnew
sudo find /var/www/solnew -type d -exec chmod 755 {} \;
sudo find /var/www/solnew -type f -exec chmod 644 {} \;
sudo chmod -R 775 /var/www/solnew/config \
                  /var/www/solnew/resources \
                  /var/www/solnew/storage
```

---

## 4. Web server config

### Apache

A `.htaccess` ships in the project root and handles URL rewriting plus
security headers automatically. You only need to make sure
`AllowOverride All` is set for the project directory:

```apache
<VirtualHost *:80>
    ServerName faucet.example.com
    DocumentRoot /var/www/solnew

    <Directory /var/www/solnew>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog  ${APACHE_LOG_DIR}/solnew-error.log
    CustomLog ${APACHE_LOG_DIR}/solnew-access.log combined
</VirtualHost>
```

Save as `/etc/apache2/sites-available/solnew.conf`, then:

```bash
sudo a2ensite solnew
sudo systemctl reload apache2
```

### Nginx

Nginx ignores `.htaccess`, so the rewrite and headers go in the server
block:

```nginx
server {
    listen 80;
    server_name faucet.example.com;
    root /var/www/solnew;
    index index.php;

    # URL rewriting equivalent to .htaccess.
    location / {
        try_files $uri $uri/ /index.php?param=$uri&$args;
    }

    # PHP processing.
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
    }

    # Block access to sensitive directories.
    location ~ ^/(config|storage|models)/ { deny all; }
    location ~ ^/lib/.*\.php$ { deny all; }

    # Security headers.
    add_header X-Content-Type-Options "nosniff"          always;
    add_header X-Frame-Options        "SAMEORIGIN"       always;
    add_header Referrer-Policy        "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy     "geolocation=(), microphone=(), camera=()" always;

    # Cache static assets.
    location ~* \.(css|js|png|jpe?g|svg|woff2?|ico)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

Save as `/etc/nginx/sites-available/solnew`, link it, and reload:

```bash
sudo ln -s /etc/nginx/sites-available/solnew /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

---

## 5. Run the installer

1. Open `https://faucet.example.com/` in a browser.
2. Because `config/config.php` doesn't yet exist, the framework hands off
   to the wizard at **`/resources/Installer/`** automatically.
3. The first screen runs a **pre-flight check**. Every row should be
   green. If anything is red, fix it before continuing - usually it's
   a missing extension or non-writable directory.
4. Fill in the form:

   | Field            | What it means                                                 |
   |------------------|---------------------------------------------------------------|
   | Website domain   | The hostname only, no `https://`. e.g. `faucet.example.com`.  |
   | Contact email    | Where contact-form messages go.                               |
   | Database host    | Usually `localhost`.                                          |
   | Database name    | Will be **created** if it doesn't exist.                      |
   | Database user    | Must have `CREATE`, `INSERT`, `SELECT`, `UPDATE`, `DELETE`.   |
   | Database pass    | Leave empty if your user has no password.                     |
   | Admin username   | The account you'll use to log into `/admin/login`.            |
   | Admin password   | At least 6 characters.                                        |

5. Click **Install**. The wizard will:
   - create the database (if missing) and run `schema.sql`
   - seed the `config` table with sensible defaults
   - hash your admin password with `password_hash()`
   - generate `config/config.php` with a random `COOKIE_SECRET`
   - **delete itself** from `resources/Installer/`

6. You'll be redirected to the admin login page.

> **If the installer doesn't redirect**, browse to `/admin/login` manually.
> If you see a "Configuration is missing" message, check that the web
> server user can write to `/config/`.

---

## 6. First admin login

- URL: `https://faucet.example.com/admin/login`
- Username / password: whatever you set during installation.

You should see the admin dashboard. If you do - the script is installed.

---

## 7. Site settings

Open `Admin -> Site settings` and edit the values. The most important ones:

### General

| Key                       | What                                               |
|---------------------------|----------------------------------------------------|
| `website_name`            | Shown in the navbar and emails.                    |
| `website_homepage_title`  | The browser tab title on the home page.            |
| `contact_email_address`   | Where contact-form messages are delivered.         |
| `no_reply_email_address`  | The `From:` address on outbound mail.              |

### Faucet

| Key                       | What                                               |
|---------------------------|----------------------------------------------------|
| `coin_information`        | Pick a currency from the FaucetPay-supported list. |
| `faucet_reward`           | Amount paid per claim, in the chosen currency.     |
| `faucet_time_limit`       | Cooldown in minutes between claims by one user.    |
| `referral_percentage`     | E.g. `25` means each referred claim earns the referrer 25% of the claim amount. |
| `faucetpay_api_key`       | See [FaucetPay setup](#9-faucetpay-setup).         |

Click **Save settings** at the bottom.

---

## 8. Captcha setup

Bots will eat your faucet alive without one. Two providers are supported:

### Google reCAPTCHA v2

1. Visit https://www.google.com/recaptcha/admin/create
2. Choose **reCAPTCHA v2** -> **"I'm not a robot" Checkbox**.
3. Add your domain.
4. Copy the **Site key** and **Secret key**.
5. In `Admin -> Site settings -> Captcha`, set:
   - **Provider:** `Google reCAPTCHA`
   - **Site key:** paste the site key
   - **Secret key:** paste the secret key
6. Save.

### hCaptcha

1. Sign up at https://www.hcaptcha.com/
2. Create a new site, copy the **Site key** and **Secret key**.
3. In `Admin -> Site settings -> Captcha`, set:
   - **Provider:** `hCaptcha`
   - **Site key:** paste it
   - **Secret key:** paste it
4. Save.

Test by signing out and visiting `/account/login` - you should see the
captcha widget under the password field.

---

## 9. FaucetPay setup

Solnew pays out via **FaucetPay** (https://faucetpay.io/) - users add
their FaucetPay address during registration, and you fund a single
FaucetPay merchant balance.

1. Sign up / sign in to FaucetPay.
2. Visit **Account -> Merchant API**.
3. Create a new API key.
4. Whitelist your server's outbound IP if FaucetPay asks.
5. Copy the API key.
6. In `Admin -> Site settings -> Faucet`, paste it into
   `faucetpay_api_key` and save.
7. Top up your FaucetPay balance with the currency you've chosen.

---

## 10. Shortlink setup

Optional. Adding a shortlink monetises each claim by routing the user
through an interstitial. Pick **one** provider:

### ouo.io

1. Sign up at https://ouo.io/.
2. Visit **Tools -> API** and copy your API key.
3. In `Admin -> Site settings -> Shortlink`:
   - **Provider:** `ouo.io`
   - **ouo.io API key:** paste it.
4. Save.

### shorte.st

1. Sign up at https://shorte.st/.
2. Visit **Tools -> Developers API** and copy the **Public API token**.
3. In `Admin -> Site settings -> Shortlink`:
   - **Provider:** `shorte.st`
   - **shorte.st API token:** paste it.
4. Save.

To disable shortlinks entirely, set the provider to `Disabled`.

---

## 11. SMTP / email setup

`Admin -> Site settings -> Email` controls outbound mail used for
account confirmations, password resets, and email-change verification.

### Easy mode (server `mail()`)

Leave **Use SMTP** as `No` and skip the rest. Works on most managed
hosts; less reliable on raw VPSes.

### Recommended: SMTP via a transactional provider

Sign up for any of:

- Mailgun (5k free emails / month for 3 months)
- SendGrid
- AWS SES
- Postmark
- Your domain's MX provider (Zoho / Google Workspace / Fastmail)

Then in the Email block:

| Key                      | Example                          |
|--------------------------|----------------------------------|
| Use SMTP                 | `Yes`                            |
| Email confirmation       | `Yes` (highly recommended)       |
| SMTP host                | `smtp.mailgun.org`               |
| SMTP port                | `465`                            |
| SMTP encryption          | `ssl`                            |
| SMTP auth                | `Yes`                            |
| SMTP username            | provided by your email provider  |
| SMTP password            | provided by your email provider  |

Save and immediately test the round-trip: register a new test account,
confirm it receives the activation email.

> **Common gotcha:** the `From:` header is taken from
> `no_reply_email_address`. Make sure that address is verified with your
> SMTP provider, otherwise outbound mail will be rejected.

---

## 12. Going to production (HTTPS)

Install a free Let's Encrypt certificate:

```bash
sudo apt install certbot python3-certbot-apache  # or python3-certbot-nginx
sudo certbot --apache -d faucet.example.com      # or --nginx
```

Then in `config/config.php`, set:

```php
'SECURE'      => true,
'WEBSITE_URL' => 'https://faucet.example.com/',
```

The framework will now mark all cookies `Secure`. You should also do a
final HTTPS smoke test:

- Sign in on the public site
- Make a test claim
- Sign in to `/admin/login`
- Update a setting
- Sign out

If any link kicks back to HTTP, double-check that your reverse proxy is
forwarding `X-Forwarded-Proto: https`.

---

## 13. Troubleshooting

### "Configuration is missing. Please run the installer."

`config/config.php` doesn't exist. Either you haven't completed the
installer yet, or `config/` isn't writable - the installer would have
shown an error in that case. Re-check directory permissions
([Section 3](#3-file-permissions)).

### Installer says the script is already installed

Either the file `config/config.php` exists (the installer refuses to
overwrite), or it self-deleted from a previous run and `SCRIPT_INSTALLED`
is defined. Delete `config/config.php` to start over.

### 500 Internal Server Error after install

Open `storage/log/error.log` first; if it's empty, look at your web
server's error log (`/var/log/apache2/error.log`,
`/var/log/nginx/error.log`). Most common causes:

- PHP version too old (`php -v` should be 7.1+)
- Missing PHP extension - run `php -m` and look for `mysqli`, `mbstring`, `curl`, `openssl`
- `mod_rewrite` disabled - run `sudo a2enmod rewrite` and restart Apache
- `AllowOverride None` in your vhost - change to `AllowOverride All`

### Templates render as raw `{$variable}`

Smarty's compile dir is read-only. Make sure
`/storage/cache/templates_c/` is writable by the web server user.

### Captcha refuses every submission

- Double-check the site/secret keys (no trailing whitespace)
- Make sure your domain matches the captcha provider's allowed list
- Look in browser DevTools -> Network for the captcha request and
  verify the response

### "FaucetPay responded: insufficient balance"

Top up your FaucetPay merchant balance with the currency you've selected.
Test claims still work in the database, but the payout step fails until
funded.

### Outbound emails never arrive

- Check the **spam folder** first (especially with Gmail / Outlook)
- Verify the SMTP credentials are correct
- Some hosts block outbound port 25 - use 465 (SSL) or 587 (TLS)
- Make sure the `From:` address (`no_reply_email_address`) is verified
  with your SMTP provider

### "The captcha test was not solved correctly" on every claim

Bots and frontend caches sometimes serve a stale captcha widget. Confirm
the user is logged in (the dashboard requires a session), then
hard-refresh the dashboard page.

---

## 14. Upgrading from the legacy script

If you have an older copy running on the same database, the schema is
compatible but the framework expects a few additions. The safest path:

1. **Back up everything** - both the codebase and a `mysqldump` of the
   live database.
2. Move the old codebase aside (`mv solnew solnew.old`).
3. Deploy this version into a fresh directory and copy
   `solnew.old/config/config.php` over.
4. Open the database and confirm the **`users` table** has these
   columns - if not, run the corresponding `ALTER`s by hand:
   ```sql
   ALTER TABLE users
       ADD COLUMN failed_logins MEDIUMINT NOT NULL DEFAULT 0,
       ADD COLUMN last_failed_login BIGINT NOT NULL DEFAULT 0;
   ALTER TABLE users
       ADD UNIQUE KEY user_email (user_email);
   ALTER TABLE config
       ADD UNIQUE KEY parameter (parameter);
   ```
5. Convert tables to `utf8mb4`:
   ```sql
   ALTER DATABASE your_db
       CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   -- Then for each table:
   ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   -- ...repeat for ads, claims_*, config, email_updates, error_log,
   -- referral_returns, withdrawals, admin_details
   ```
6. **Admin passwords**: the legacy SHA-512 hash is honoured for one more
   sign-in. The next time the admin logs in, switch them over by
   changing the password from `Admin -> Profile`.
7. Smoke-test as in the [Going to production](#12-going-to-production-https)
   section.

---

That's the lot. If you hit something this guide doesn't cover, open an
issue on the GitHub repo with:

- PHP version (`php -v`)
- Web server (`apache2 -v` or `nginx -v`)
- The error message you're seeing
- The first ~50 lines of `storage/log/error.log`
