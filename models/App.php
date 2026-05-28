<?php
/**
 * Application service helpers.
 *
 * Bug fixes vs. the original:
 *   - admin password storage migrated to password_hash() (custom 100x
 *     SHA loop replaced); legacy hashes are upgraded on next login.
 *   - admin / user lookups use prepared statements throughout.
 *   - sendFaucetPay records the failure case correctly.
 *
 * @package Solnew
 */

if (!defined('FERNICO')) {
    http_response_code(403);
    exit('Forbidden');
}

class App
{
    /** @var array<string, string> Cached config values. */
    static $data = [];

    /**
     * Convenience: return the value of a single column from a single
     * row, given a fully-formed SELECT statement.
     *
     * Caller is responsible for sanitising any input baked into $stmt.
     */
    static function mysqlQueryFetchAssoc($stmt, $field)
    {
        global $fernico_db;
        $row = $fernico_db->query($stmt)->fetch_assoc();
        return $row ? ($row[$field] ?? null) : null;
    }

    /**
     * Trigger a payout via FaucetPay. Records the attempt in the
     * withdrawals table whether or not the call succeeded.
     */
    static function sendFaucetPay($user_id = 0, $amount = 0.0, $referral_payment = false)
    {
        global $fernico_db;

        $userId = (int) $user_id;
        $stmt = $fernico_db->prepare('SELECT address FROM users WHERE user_id = ? LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $address = (string) ($row['address'] ?? '');

        $resp = json_decode((string) fernico_post('https://faucetpay.io/api/v1/send', [
            'api_key'  => self::loadSiteVar('faucetpay_api_key'),
            'to'       => $address,
            'amount'   => $amount * 100000000,
            'currency' => self::loadSiteVar('coin_abbreviation'),
            'referral' => $referral_payment ? 1 : 0,
        ]), true);

        $status = is_array($resp) && (int) ($resp['status'] ?? 0) === 200 ? 1 : 0;

        $stmt = $fernico_db->prepare(
            'INSERT INTO withdrawals (user_id, address, amount, status)
                  VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('isdi', $userId, $address, $amount, $status);
        $stmt->execute();
        $stmt->close();

        return is_array($resp) ? $resp : ['status' => 0, 'message' => 'invalid response'];
    }

    /**
     * Render the captcha widget for the configured provider.
     */
    static function getCaptcha()
    {
        $provider = (int) self::loadSiteVar('captcha_used');
        $siteKey = htmlspecialchars((string) self::loadSiteVar('site_key'), ENT_QUOTES);

        switch ($provider) {
            case 1: // Google reCAPTCHA v2
                return "<div id='captcha'></div>
<script>
var onloadCallback = function() {
    grecaptcha.render('captcha', {
        sitekey: '{$siteKey}',
        hl: 'en'
    });
};
</script>
<script src='https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit' async defer></script>";

            case 2: // hCaptcha (provider 2 used to be the now-dead Crypto-Loot)
            case 3: // hCaptcha
                return "<div class='h-captcha' data-sitekey='{$siteKey}'></div>
<script src='https://hcaptcha.com/1/api.js' async defer></script>";

            default:
                return '';
        }
    }

    /**
     * Securely random float in [$min, $max).
     */
    static function value_gen($min, $max)
    {
        return random_int($min, $max - 1) + (random_int(0, PHP_INT_MAX - 1) / PHP_INT_MAX);
    }

    /**
     * Whether the current request belongs to a logged-in user.
     */
    static function userLoggedIn()
    {
        return !empty($_SESSION['user_name'])
            && (int) ($_SESSION['user_logged_in'] ?? 0) === 1;
    }

    /**
     * Redirect anonymous visitors to the login page (with a hint).
     */
    static function vomitLoginPageByRedirection($auth)
    {
        if (!$auth->UserLoggedIn()) {
            header('Location: ' . fernico_getAbsURL() . 'account/login/not-logged-in');
            fernico_destroy();
        }
    }

    /**
     * "5m ago" / "3h ago" style relative timestamps.
     */
    static function beautifyTime($time = 0)
    {
        $time = (int) $time;
        if ($time <= 0) {
            return 'just now';
        }

        $diff = abs(time() - $time);
        if ($diff < 60) {
            return $diff . 's ago';
        }
        if ($diff < 3600) {
            return round($diff / 60) . 'm ago';
        }
        if ($diff < 86400) {
            return round($diff / 3600) . 'h ago';
        }
        if ($diff < 2629743) {
            return round($diff / 86400) . 'd ago';
        }
        if ($diff < 31556926) {
            return round($diff / 2629743) . 'mo ago';
        }
        return round($diff / 31556926) . 'y ago';
    }

    static function getAd($location = '')
    {
        // Hook for theme-level ad placement; intentionally empty.
        return '';
    }

    /**
     * Whether any of $needles appears in $haystack.
     */
    static function strposa($haystack, array $needles = [], $offset = 0)
    {
        foreach ($needles as $needle) {
            if (strpos($haystack, $needle, $offset) !== false) {
                return true;
            }
        }
        return false;
    }

    static function generateDate($syntax, $change)
    {
        return date($syntax, strtotime($change));
    }

    /**
     * Shorten a URL via shorte.st. Returns the shortened URL or null
     * if the API call failed.
     */
    static function shortest($url = '')
    {
        $token = (string) self::loadSiteVar('shortest_api_token');
        if (!$token) {
            return null;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://api.shorte.st/v1/data/url',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_HTTPHEADER     => [
                'public-api-token: ' . $token,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query(['urlToShorten' => $url]),
        ]);
        $result = json_decode((string) curl_exec($ch), true);
        curl_close($ch);

        return is_array($result) && ($result['status'] ?? '') === 'ok'
            ? $result['shortenedUrl']
            : null;
    }

    /**
     * Verify a captcha response with the configured provider.
     */
    static function verifyCaptcha()
    {
        $provider = (int) self::loadSiteVar('captcha_used');
        $secretKey = (string) self::loadSiteVar('secret_key');

        if ($provider === 0) {
            return true; // Captcha disabled.
        }

        switch ($provider) {
            case 1: // Google reCAPTCHA
                $response = fernico_post(
                    'https://www.google.com/recaptcha/api/siteverify',
                    [
                        'secret'   => $secretKey,
                        'response' => (string) Request::POST('g-recaptcha-response'),
                    ]
                );
                $data = json_decode((string) $response, true);
                return is_array($data) && !empty($data['success']);

            case 2:
            case 3: // hCaptcha (also covers legacy provider 2 slot)
                $response = fernico_post('https://hcaptcha.com/siteverify', [
                    'secret'   => $secretKey,
                    'response' => (string) Request::POST('h-captcha-response'),
                ]);
                $data = json_decode((string) $response, true);
                return is_array($data) && !empty($data['success']);
        }

        return false;
    }

    static function random_float($min, $max)
    {
        return $min + lcg_value() * abs($max - $min);
    }

    /**
     * Build a URL into the active theme (or the application root).
     */
    static function makeLink($path = '', $template = false)
    {
        $base = fernico_getAbsURL();
        if ($template) {
            return $base . 'resources/' . Config::fetch('TEMPLATE_DIR') . '/' . ltrim($path, '/');
        }
        return $base . ltrim($path, '/');
    }

    /**
     * Pick a random ad of $type from the database.
     */
    static function showAd($type)
    {
        global $fernico_db;
        $type = (int) $type;
        $stmt = $fernico_db->prepare(
            'SELECT code FROM ads WHERE type = ? ORDER BY RAND() LIMIT 1'
        );
        $stmt->bind_param('i', $type);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? $row['code'] : '';
    }

    static function homeLink()
    {
        return fernico_getAbsURL();
    }

    /**
     * Lookup a row in the config table, with in-process caching.
     */
    static function loadSiteVar($variable)
    {
        global $fernico_db;
        if (isset(self::$data[$variable])) {
            return self::$data[$variable];
        }

        $stmt = $fernico_db->prepare(
            'SELECT value FROM config WHERE parameter = ? LIMIT 1'
        );
        $stmt->bind_param('s', $variable);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $value = $row ? $row['value'] : '';
        self::$data[$variable] = $value;
        return $value;
    }

    /**
     * Wipe the admin session and rotate the admin token. Called from
     * profile / logout flows.
     */
    static function destroyAdminSession()
    {
        global $fernico_db;
        $sessionTime = 60 * 60 * 24 * (int) Config::fetch('SESSION_DAYS');
        $newToken = bin2hex(random_bytes(32));

        if (!empty($_SESSION['admin_user_name'])) {
            $stmt = $fernico_db->prepare(
                'UPDATE admin_details SET token = ? WHERE user_name = ?'
            );
            $stmt->bind_param('ss', $newToken, $_SESSION['admin_user_name']);
            $stmt->execute();
            $stmt->close();
        }

        unset($_SESSION['admin_user_name']);
        setcookie(
            'admin_token',
            '',
            time() - $sessionTime * 2,
            '/',
            (string) Config::fetch('COOKIE_DOMAIN'),
            (bool) Config::fetch('SECURE'),
            true
        );
    }

    /**
     * Whether the current request carries a valid admin cookie.
     */
    static function isAdmin()
    {
        global $fernico_db;
        $token = (string) Request::COOKIE('admin_token', true);
        if ($token === '') {
            return false;
        }

        $stmt = $fernico_db->prepare(
            'SELECT user_name FROM admin_details WHERE token = ? LIMIT 1'
        );
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $_SESSION['admin_user_name'] = $row['user_name'];
            return true;
        }

        $sessionTime = 60 * 60 * 24 * (int) Config::fetch('SESSION_DAYS');
        setcookie(
            'admin_token',
            '',
            time() - $sessionTime * 2,
            '/',
            (string) Config::fetch('COOKIE_DOMAIN'),
            (bool) Config::fetch('SECURE'),
            true
        );
        return false;
    }

    static function setAdminRedirections()
    {
        if (!self::isAdmin()) {
            header('Location: ' . fernico_getAbsURL() . 'admin/login');
            fernico_destroy();
        }
    }

    /**
     * Modern admin password hashing built on password_hash().
     */
    static function adminPasswordHash($input)
    {
        return password_hash($input, PASSWORD_DEFAULT, ['cost' => 12]);
    }

    /**
     * Verify a plaintext password against the stored hash. Supports
     * legacy SHA-512 hashes for installs created with the old code.
     */
    static function adminPasswordVerify($input, $stored)
    {
        if (password_verify($input, $stored)) {
            return true;
        }

        // Fall back to the legacy custom hash so older installs keep
        // working until users are upgraded.
        return hash_equals($stored, self::generatePasswordHash($input));
    }

    /**
     * Legacy password hashing, retained only for backwards-compat with
     * existing admin rows. New code should prefer adminPasswordHash().
     */
    static function generatePasswordHash($input)
    {
        for ($i = 0; $i < 100; $i++) {
            $input = hash('sha256', $input);
        }
        return hash('sha512', $input);
    }

    static function footerText()
    {
        $words = ['Developed', 'Designed', 'Crafted', 'Made', 'Constructed', 'Created', 'Forged', 'Powered'];
        return $words[array_rand($words)];
    }

    static function mysqlQuery($query)
    {
        global $fernico_db;
        return $fernico_db->query($query);
    }

    static function grabNumericValue($string)
    {
        return intval(preg_replace('/[^0-9]+/', '', (string) $string), 10);
    }

    /**
     * Process the contact form: validate, send email, return a status
     * string suitable for surfacing to the user.
     */
    static function contactFormSubmit()
    {
        $name = (string) Request::POST('name', true);
        $emailAddress = (string) Request::POST('email_address', true);
        $message = (string) Request::POST('message', true);

        if (strlen($name) < 3) {
            return 'The name you entered is too short.';
        }
        if ($emailAddress === '' || !filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            return 'You entered an invalid email address.';
        }
        if (strlen($message) < 10) {
            return 'The message you entered is too short.';
        }

        $country = fernico_countryCode();
        $subject = $name . ' has left a message via the contact form';
        $sessionUser = $_SESSION['user_name'] ?? '(anonymous)';

        $body = '<p>Hello!</p>'
              . '<p><b>Name:</b> ' . htmlspecialchars($name) . '<br>'
              . '<b>Username:</b> ' . htmlspecialchars($sessionUser) . '<br>'
              . '<b>Email Address:</b> ' . htmlspecialchars($emailAddress) . '<br>'
              . '<b>IP Address:</b> ' . htmlspecialchars(fernico_getIPAddress()) . '<br>'
              . '<b>Country:</b> ' . htmlspecialchars($country) . '</p>'
              . '<hr><p>' . nl2br(htmlspecialchars($message)) . '</p>';

        $mail = new PHPMailer();

        if (Config::fetch('USE_SMTP')) {
            $mail->IsSMTP();
            $mail->SMTPAuth = (bool) Config::fetch('SMTP_AUTH');
            $encryption = Config::fetch('EMAIL_SMTP_ENCRYPTION');
            if (!empty($encryption)) {
                $mail->SMTPSecure = $encryption;
            }
            $mail->Host     = (string) Config::fetch('EMAIL_SMTP_HOST');
            $mail->Username = (string) Config::fetch('EMAIL_SMTP_USERNAME');
            $mail->Password = (string) Config::fetch('EMAIL_SMTP_PASSWORD');
            $mail->Port     = (int) Config::fetch('EMAIL_SMTP_PORT');
        } else {
            $mail->IsMail();
        }

        $mail->AddReplyTo($emailAddress, $name);
        $mail->SetFrom(
            (string) self::loadSiteVar('no_reply_email_address'),
            (string) self::loadSiteVar('website_name')
        );
        $mail->Subject = $subject;
        $mail->SMTPDebug = false;
        $mail->do_debug = 0;
        $mail->MsgHTML($body);
        $mail->AddAddress((string) self::loadSiteVar('contact_email_address'));
        $mail->Send();

        return "We've received your message. We'll be in touch shortly.";
    }

    static function encryptData($data, $key)
    {
        return openssl_encrypt($data, 'AES-256-ECB', $key);
    }

    static function decryptData($data, $key)
    {
        return openssl_decrypt($data, 'AES-256-ECB', $key);
    }
}
