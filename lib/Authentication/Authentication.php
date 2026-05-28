<?php
/**
 * Authentication helper.
 *
 * Bug fixes vs. the original:
 *   - $stmt->affected_row -> affected_rows (the correct property name)
 *   - parameterised every UPDATE that previously interpolated the
 *     primary key
 *   - cookie writes opt-in to the secure / httponly flags
 *
 * @package Solnew
 */

if (!defined('FERNICO')) {
    http_response_code(403);
    exit('Forbidden');
}

class Authentication
{
    /** @var string */
    private $cookie_secret;

    /** @var int Cookie lifetime in seconds. */
    private $sessionTime;

    public function __construct()
    {
        global $fernico_db;

        $this->cookie_secret = (string) Config::fetch('COOKIE_SECRET');
        $this->sessionTime = 60 * 60 * 24 * (int) Config::fetch('SESSION_DAYS');

        // Capture an `?r=<username>` referral cookie when present.
        if (Request::GET('r')) {
            $token = (string) Request::GET('r', true);
            $_COOKIE['referral'] = $token;
            setcookie(
                'referral',
                $token,
                time() + 31556926,
                '/',
                (string) Config::fetch('COOKIE_DOMAIN'),
                (bool) Config::fetch('SECURE'),
                true
            );
        }

        // Auto-login from a remember-me cookie if no active session exists.
        if (Request::COOKIE('remember_me') !== null && !isset($_SESSION['user_logged_in'])) {
            $this->resumeFromCookie();
        }
    }

    private function resumeFromCookie()
    {
        global $fernico_db;

        $cookie = (string) Request::COOKIE('remember_me', true);
        $parts = explode(':', urldecode($cookie));
        if (count($parts) !== 3) {
            $this->forgetSession();
            return;
        }
        [$userId, $hash, $token] = $parts;

        // Tamper-evident: the hash binds the cookie token to the secret.
        $expected = hash('sha256', $token . $this->cookie_secret);
        if (!is_numeric($userId) || !hash_equals($expected, $hash)) {
            $this->forgetSession();
            return;
        }

        $stmt = $fernico_db->prepare(
            'SELECT user_id, user_name, user_email
               FROM users WHERE rememberme_token = ? LIMIT 1'
        );
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $this->forgetSession();
            return;
        }

        $_SESSION['user_id']        = $user['user_id'];
        $_SESSION['user_name']      = $user['user_name'];
        $_SESSION['user_email']     = $user['user_email'];
        $_SESSION['user_logged_in'] = 1;

        // Rotate the remember-me token to prevent replay.
        $newToken = bin2hex(random_bytes(32));
        $newHash  = hash('sha256', $newToken . $this->cookie_secret);
        $cookieValue = $user['user_id'] . ':' . $newHash . ':' . $newToken;

        setcookie(
            'remember_me',
            $cookieValue,
            time() + $this->sessionTime,
            '/',
            (string) Config::fetch('COOKIE_DOMAIN'),
            (bool) Config::fetch('SECURE'),
            true
        );

        $stmt = $fernico_db->prepare(
            'UPDATE users SET rememberme_token = ? WHERE user_id = ?'
        );
        $stmt->bind_param('si', $newToken, $user['user_id']);
        $stmt->execute();
        $stmt->close();
    }

    private function forgetSession()
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        unset($_COOKIE['remember_me']);
        setcookie(
            'remember_me',
            '',
            time() - $this->sessionTime * 2,
            '/',
            (string) Config::fetch('COOKIE_DOMAIN'),
            (bool) Config::fetch('SECURE'),
            true
        );
    }

    public function logout()
    {
        $this->forgetSession();
        return 'SUCCESS';
    }

    public function login($user_name, $password)
    {
        global $fernico_db;

        $user_name = Request::cleanInput((string) $user_name);
        $password  = (string) $password;

        $stmt = $fernico_db->prepare(
            'SELECT user_id, user_name, password_hash, account_status,
                    user_email, user_verified, failed_logins, last_failed_login
               FROM users WHERE user_name = ? LIMIT 1'
        );
        $stmt->bind_param('s', $user_name);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!App::verifyCaptcha()) {
            return 'ER_CAPTCHA_INVALID';
        }
        if (strlen($user_name) < 3) {
            return 'ER_USER_NAME_SHORT';
        }
        if (strlen($user_name) > 16) {
            return 'ER_USER_NAME_LONG';
        }
        if (preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $user_name)) {
            return 'ER_USER_NAME_CONTAINS_SPECIAL_CHARACTERS';
        }
        if (strlen($password) < 6) {
            return 'ER_PASSWORD_SHORT';
        }
        if (strlen($password) > 64) {
            return 'ER_PASSWORD_LONG';
        }
        if (!$data) {
            return 'ER_USER_NOT_FOUND';
        }

        // Lock-out check before reporting "wrong password" to prevent
        // attackers from probing the account state.
        if ((int) $data['failed_logins'] >= 6 && (int) $data['last_failed_login'] > (time() - 900)) {
            return 'ER_TOO_MANY_ATTEMPTS';
        }

        if (!password_verify($password, $data['password_hash'])) {
            $now = time();
            $stmt = $fernico_db->prepare(
                'UPDATE users
                    SET failed_logins = failed_logins + 1,
                        last_failed_login = ?
                  WHERE user_id = ?'
            );
            $stmt->bind_param('ii', $now, $data['user_id']);
            $stmt->execute();
            $stmt->close();
            return 'ER_PASSWORD_INCORRECT';
        }

        if ((int) $data['user_verified'] === 0) {
            return 'ER_ACCOUNT_NOT_VERIFIED';
        }
        if ((int) $data['account_status'] === 0) {
            return 'ER_ACCOUNT_BANNED';
        }

        // Establish the logged-in session.
        $_SESSION['user_id']        = $data['user_id'];
        $_SESSION['user_name']      = $data['user_name'];
        $_SESSION['user_email']     = $data['user_email'];
        $_SESSION['user_logged_in'] = 1;

        $rememberToken = null;
        if (Request::POST('remember_me') !== null) {
            $rememberToken = bin2hex(random_bytes(32));
            $hash = hash('sha256', $rememberToken . $this->cookie_secret);
            $cookieValue = $data['user_id'] . ':' . $hash . ':' . $rememberToken;
            setcookie(
                'remember_me',
                $cookieValue,
                time() + $this->sessionTime,
                '/',
                (string) Config::fetch('COOKIE_DOMAIN'),
                (bool) Config::fetch('SECURE'),
                true
            );
        }

        $datetime = date('Y-m-d H:i:s');
        $stmt = $fernico_db->prepare(
            'UPDATE users
                SET last_logged_in = ?,
                    failed_logins = 0,
                    rememberme_token = ?
              WHERE user_id = ?'
        );
        $stmt->bind_param('ssi', $datetime, $rememberToken, $data['user_id']);
        $stmt->execute();
        $stmt->close();

        return 'LOGIN_SUCCESS';
    }

    public function verifyCaptcha()
    {
        // Delegate to App::verifyCaptcha() which handles all 3 providers.
        return App::verifyCaptcha();
    }

    public function register($user_name, $user_email, $password, $password_repeat)
    {
        global $fernico_db;

        $user_name       = Request::cleanInput((string) $user_name);
        $user_email      = Request::cleanInput((string) $user_email);
        $password        = (string) $password;
        $password_repeat = (string) $password_repeat;
        $referral        = (string) Request::COOKIE('referral', true);
        $address         = (string) Request::POST('address', true);

        // Validate the referrer (if any).
        $confirmRef = ['count' => 0, 'user_id' => 0];
        if ($referral !== '') {
            $stmt = $fernico_db->prepare(
                'SELECT user_id FROM users WHERE user_name = ? LIMIT 1'
            );
            $stmt->bind_param('s', $referral);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                $confirmRef = ['count' => 1, 'user_id' => $row['user_id']];
            }
        }

        // Username/email uniqueness checks.
        $stmt = $fernico_db->prepare('SELECT 1 FROM users WHERE user_name = ? LIMIT 1');
        $stmt->bind_param('s', $user_name);
        $stmt->execute();
        $userExists = (bool) $stmt->get_result()->fetch_row();
        $stmt->close();

        $stmt = $fernico_db->prepare('SELECT 1 FROM users WHERE user_email = ? LIMIT 1');
        $stmt->bind_param('s', $user_email);
        $stmt->execute();
        $emailExists = (bool) $stmt->get_result()->fetch_row();
        $stmt->close();

        if (!App::verifyCaptcha()) {
            return 'ER_CAPTCHA_INVALID';
        }
        if (strlen($user_name) < 3) {
            return 'ER_USER_NAME_SHORT';
        }
        if (strlen($user_name) > 16) {
            return 'ER_USER_NAME_LONG';
        }
        if (preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $user_name)) {
            return 'ER_USER_NAME_CONTAINS_SPECIAL_CHARACTERS';
        }
        if ($user_email === '') {
            return 'ER_EMAIL_BLANK';
        }
        if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
            return 'ER_EMAIL_INVALID';
        }
        if (strlen($password) < 6) {
            return 'ER_PASSWORD_SHORT';
        }
        if (strlen($password) > 64) {
            return 'ER_PASSWORD_LONG';
        }
        if ($password !== $password_repeat) {
            return 'ER_PASSWORD_REPEATING_NOT_MATCHING';
        }
        if ($userExists) {
            return 'ER_USER_ALREADY_EXISTS';
        }
        if ($emailExists) {
            return 'ER_EMAIL_ALREADY_EXISTS';
        }

        $emailConfirmation = App::loadSiteVar('email_confirmation') === 'true';
        if ($emailConfirmation) {
            $userVerified = 0;
            $activationHash = bin2hex(random_bytes(32));
            $activationLink = fernico_getAbsURL()
                . Config::fetch('CONFIRMATION_CONTROLLER') . '/'
                . Config::fetch('CONFIRMATION_ACTION') . '/'
                . $activationHash;

            $subject = 'Confirm your email at ' . App::loadSiteVar('website_name');
            $body = (string) @file_get_contents(__DIR__ . '/ActivationEmail.txt');
            $body = stripslashes($body);
            $body = str_replace(
                ['{$activation_link}', '{$website_name}'],
                [$activationLink, App::loadSiteVar('website_name')],
                $body
            );
            $this->sendMail($user_email, $subject, $body);
        } else {
            $userVerified = 1;
            $activationHash = null;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
        $registrationDatetime = date('Y-m-d H:i:s');
        $registrationIp = fernico_getIPAddress();
        $referralId = (int) ($confirmRef['count'] > 0 ? $confirmRef['user_id'] : 0);

        $stmt = $fernico_db->prepare(
            'INSERT INTO users
                (user_name, user_email, password_hash, user_verified,
                 activation_hash, registration_datetime, registration_ip,
                 referral, address)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'sssisssis',
            $user_name, $user_email, $passwordHash, $userVerified,
            $activationHash, $registrationDatetime, $registrationIp,
            $referralId, $address
        );
        $stmt->execute();
        $stmt->close();

        $fernico_db->query("UPDATE config SET value = value + 1 WHERE parameter = 'stats_Total_Users'");

        return $emailConfirmation
            ? 'REGISTER_SUCCESS_ACTIVATION_EMAIL_SENT'
            : 'REGISTER_SUCCESS_NO_ACTIVATION_EMAIL';
    }

    public function sendMail($email, $subject, $body)
    {
        $mail = new PHPMailer();

        $useSmtp = App::loadSiteVar('use_smtp') === 'true';
        $smtpAuth = App::loadSiteVar('smtp_auth') === 'true';

        if ($useSmtp) {
            $mail->IsSMTP();
            $mail->SMTPAuth   = $smtpAuth;
            $mail->SMTPSecure = (string) App::loadSiteVar('email_smtp_encryption');
            $mail->Host       = (string) App::loadSiteVar('email_smtp_host');
            $mail->Username   = (string) App::loadSiteVar('email_smtp_username');
            $mail->Password   = (string) App::loadSiteVar('email_smtp_password');
            $mail->Port       = (int) App::loadSiteVar('email_smtp_port');
        } else {
            $mail->IsMail();
        }

        $mail->SetFrom(
            (string) App::loadSiteVar('no_reply_email_address'),
            (string) App::loadSiteVar('website_name')
        );
        $mail->Subject = $subject;
        $mail->SMTPDebug = false;
        $mail->do_debug = 0;
        $mail->MsgHTML($body);
        $mail->AddAddress($email);
        $mail->Send();
    }

    public function confirmEmail($activation_hash)
    {
        global $fernico_db;

        $stmt = $fernico_db->prepare(
            'SELECT user_id FROM users
              WHERE activation_hash = ? AND user_verified = 0 LIMIT 1'
        );
        $stmt->bind_param('s', $activation_hash);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return 'ER_WRONG_ACTIVATION_LINK';
        }

        $stmt = $fernico_db->prepare(
            'UPDATE users SET activation_hash = NULL, user_verified = 1
              WHERE user_id = ?'
        );
        $stmt->bind_param('i', $row['user_id']);
        $stmt->execute();
        $stmt->close();
        return 'SUCCESS_EMAIL_CONFIRMED';
    }

    public function sendPasswordResetEmail($user_name)
    {
        global $fernico_db;
        $user_name = Request::cleanInput((string) $user_name);

        $stmt = $fernico_db->prepare(
            'SELECT user_id, user_email FROM users WHERE user_name = ? LIMIT 1'
        );
        $stmt->bind_param('s', $user_name);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!App::verifyCaptcha()) {
            return 'ER_CAPTCHA_INVALID';
        }
        if (strlen($user_name) < 3) {
            return 'ER_USER_NAME_SHORT';
        }
        if (strlen($user_name) > 16) {
            return 'ER_USER_NAME_LONG';
        }
        if (!$user) {
            return 'ER_USER_NAME_INVALID';
        }

        $resetHash = bin2hex(random_bytes(32));
        $resetLink = fernico_getAbsURL()
            . Config::fetch('RESET_PASSWORD_CONTROLLER') . '/'
            . Config::fetch('RESET_PASSWORD_ACTION') . '/'
            . $resetHash;

        $stmt = $fernico_db->prepare(
            'UPDATE users SET reset_hash = ? WHERE user_id = ?'
        );
        $stmt->bind_param('si', $resetHash, $user['user_id']);
        $stmt->execute();
        $stmt->close();

        $subject = 'Password reset link from ' . App::loadSiteVar('website_name');
        $body = (string) @file_get_contents(__DIR__ . '/PasswordResetEmail.txt');
        $body = stripslashes($body);
        $body = str_replace(
            ['{$resetLink}', '{$website_name}'],
            [$resetLink, App::loadSiteVar('website_name')],
            $body
        );
        $this->sendMail($user['user_email'], $subject, $body);
        return 'SUCCESS_RESET_LINK_SENT';
    }

    public function isValidResetLink($reset_hash)
    {
        global $fernico_db;
        $reset_hash = (string) $reset_hash;
        if ($reset_hash === '') {
            return 'IS_NOT_VALID_RESET_LINK';
        }

        $stmt = $fernico_db->prepare(
            "SELECT 1 FROM users
              WHERE reset_hash = ? AND reset_hash <> '' LIMIT 1"
        );
        $stmt->bind_param('s', $reset_hash);
        $stmt->execute();
        $found = (bool) $stmt->get_result()->fetch_row();
        $stmt->close();

        return $found ? 'IS_VALID_RESET_LINK' : 'IS_NOT_VALID_RESET_LINK';
    }

    public function setNewPassword($hash, $password, $password_repeat)
    {
        global $fernico_db;

        $hash = Request::cleanInput((string) $hash);
        $password = (string) $password;
        $password_repeat = (string) $password_repeat;

        $stmt = $fernico_db->prepare(
            "SELECT user_id FROM users
              WHERE reset_hash = ? AND reset_hash <> '' LIMIT 1"
        );
        $stmt->bind_param('s', $hash);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            return 'IS_NOT_VALID_RESET_LINK';
        }
        if (strlen($password) < 6) {
            return 'ER_PASSWORD_SHORT';
        }
        if (strlen($password) > 64) {
            return 'ER_PASSWORD_LONG';
        }
        if ($password !== $password_repeat) {
            return 'ER_PASSWORD_REPEATING_NOT_MATCHING';
        }

        $newHash = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
        $stmt = $fernico_db->prepare(
            'UPDATE users SET password_hash = ?, reset_hash = NULL WHERE user_id = ?'
        );
        $stmt->bind_param('si', $newHash, $user['user_id']);
        $stmt->execute();
        $stmt->close();
        return 'PASSWORD_RESET_SUCCESSFUL';
    }

    public function resendActivationEmail($user_name)
    {
        global $fernico_db;
        $user_name = Request::cleanInput((string) $user_name);

        $stmt = $fernico_db->prepare(
            'SELECT user_id, user_email, activation_hash
               FROM users
              WHERE user_name = ? AND user_verified = 0 LIMIT 1'
        );
        $stmt->bind_param('s', $user_name);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!App::verifyCaptcha()) {
            return 'ER_CAPTCHA_INVALID';
        }
        if (strlen($user_name) < 3) {
            return 'ER_USER_NAME_SHORT';
        }
        if (strlen($user_name) > 16) {
            return 'ER_USER_NAME_LONG';
        }
        if (!$user) {
            return 'ER_USER_NAME_INVALID';
        }

        $activationLink = fernico_getAbsURL()
            . Config::fetch('CONFIRMATION_CONTROLLER') . '/'
            . Config::fetch('CONFIRMATION_ACTION') . '/'
            . $user['activation_hash'];
        $subject = 'Confirm your email at ' . App::loadSiteVar('website_name');
        $body = (string) @file_get_contents(__DIR__ . '/ActivationEmail.txt');
        $body = stripslashes($body);
        $body = str_replace(
            ['{$activation_link}', '{$website_name}'],
            [$activationLink, App::loadSiteVar('website_name')],
            $body
        );
        $this->sendMail($user['user_email'], $subject, $body);

        return 'SUCCESS_ACTIVATION_EMAIL_RESENT';
    }

    public function changePassword($password, $password_repeat)
    {
        global $fernico_db;

        if (!$this->UserLoggedIn()) {
            return 'USER_NOT_LOGGED_IN';
        }
        if (strlen((string) $password) < 6) {
            return 'ER_PASSWORD_SHORT';
        }
        if (strlen((string) $password) > 64) {
            return 'ER_PASSWORD_LONG';
        }
        if ($password !== $password_repeat) {
            return 'ER_PASSWORD_REPEATING_NOT_MATCHING';
        }

        $newHash = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
        $stmt = $fernico_db->prepare(
            'UPDATE users SET password_hash = ? WHERE user_id = ?'
        );
        $userId = (int) $_SESSION['user_id'];
        $stmt->bind_param('si', $newHash, $userId);
        $stmt->execute();
        $stmt->close();
        return 'PASSWORD_SUCCESSFULLY_CHANGED';
    }

    public function AdminPowers()
    {
        global $fernico_db;
        if (!$this->UserLoggedIn()) {
            return false;
        }

        $stmt = $fernico_db->prepare(
            'SELECT admin_powers FROM users WHERE user_id = ? LIMIT 1'
        );
        $userId = (int) $_SESSION['user_id'];
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row && (int) $row['admin_powers'] === 1;
    }

    public function UserLoggedIn()
    {
        return !empty($_SESSION['user_name'])
            && (int) ($_SESSION['user_logged_in'] ?? 0) === 1;
    }

    public function changeEmail($user_email)
    {
        global $fernico_db;

        if ($user_email === '') {
            return 'ER_EMAIL_BLANK';
        }
        if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
            return 'ER_EMAIL_INVALID';
        }
        if ($user_email === ($_SESSION['user_email'] ?? '')) {
            return 'ER_SAME_EMAIL';
        }

        $confirmCode = bin2hex(random_bytes(32));
        $changeLink = fernico_getAbsURL()
            . Config::fetch('CHANGE_EMAIL_CONTROLLER') . '/'
            . Config::fetch('CHANGE_EMAIL_ACTION') . '/'
            . $confirmCode;

        // Try to UPDATE first, INSERT if no row exists.
        $userId = (int) $_SESSION['user_id'];
        $stmt = $fernico_db->prepare(
            'UPDATE email_updates SET email = ?, confirm_code = ? WHERE user_id = ?'
        );
        $stmt->bind_param('ssi', $user_email, $confirmCode, $userId);
        $stmt->execute();
        $affected = $stmt->affected_rows; // <-- correct property name
        $stmt->close();

        if ($affected === 0) {
            $stmt = $fernico_db->prepare(
                'INSERT INTO email_updates (user_id, email, confirm_code)
                      VALUES (?, ?, ?)'
            );
            $stmt->bind_param('iss', $userId, $user_email, $confirmCode);
            $stmt->execute();
            $stmt->close();
        }

        $subject = 'Confirm your email change';
        $body = (string) @file_get_contents(__DIR__ . '/EmailChange.txt');
        $body = stripslashes($body);
        $body = str_replace(
            ['{$change_link}', '{$website_name}'],
            [$changeLink, App::loadSiteVar('website_name')],
            $body
        );
        $this->sendMail($user_email, $subject, $body);
        return 'SUCCESS_EMAIL_CHANGE_EMAIL_SENT';
    }

    public function confirmEmailChange($hash)
    {
        global $fernico_db;
        $hash = Request::cleanInput((string) $hash);

        $stmt = $fernico_db->prepare(
            'SELECT id, user_id, email FROM email_updates WHERE confirm_code = ? LIMIT 1'
        );
        $stmt->bind_param('s', $hash);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return 'ER_INVALID_EMAIL_CHANGE_LINK';
        }

        $_SESSION['user_email'] = $row['email'];

        $stmt = $fernico_db->prepare(
            'UPDATE users SET user_email = ? WHERE user_id = ?'
        );
        $stmt->bind_param('si', $row['email'], $row['user_id']);
        $stmt->execute();
        $stmt->close();

        $stmt = $fernico_db->prepare('DELETE FROM email_updates WHERE id = ?');
        $stmt->bind_param('i', $row['id']);
        $stmt->execute();
        $stmt->close();

        return 'SUCCESS_EMAIL_CHANGED';
    }

    /**
     * Render the captcha widget. Delegated to App::getCaptcha so the
     * provider switch lives in one place.
     */
    public function vomitRecaptcha()
    {
        return App::getCaptcha();
    }
}
