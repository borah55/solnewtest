<?php
/**
 * Admin controller.
 *
 * Bug fixes vs. the original:
 *   - All raw `WHERE user_name = '{$x}'` style queries replaced with
 *     prepared statements; previously trivially exploitable from any
 *     compromised admin session.
 *   - Admin password storage migrated to password_hash() / verify().
 *   - Settings page coin_information value is parsed safely (the prior
 *     code would happily INSERT random user input as DB values).
 *   - X-XSS-Protection header removed (officially deprecated).
 *
 * @package Solnew
 */

if (!defined('FERNICO')) {
    http_response_code(403);
    exit('Forbidden');
}

class adminController extends AstridController
{
    /** @var Authentication */
    public $auth;

    public function __construct()
    {
        require_once FERNICO_PATH . '/models/Bootstrapper.php';
        parent::__construct();
        $this->auth = new Authentication();
    }

    /**
     * CSRF guard for admin POSTs. Returns the error string when the
     * token is missing/invalid, null otherwise.
     */
    private function requireCsrf()
    {
        if (!Request::isPost()) {
            return null;
        }
        $token = Request::POST('csrf_token');
        if (!$token || !fernico_verifyAntiCSRFToken($token)) {
            return 'Session expired. Please reload the page and try again.';
        }
        return null;
    }

    /**
     * Fetch a single user by id, email, or username.
     *
     * @param string $needle  The identifier supplied by the admin.
     * @param string $columns SQL projection (must NOT include user input).
     *
     * @return array|null Associative row or null if no match.
     */
    private function findUser($needle, $columns)
    {
        global $fernico_db;
        $needle = trim((string) $needle);
        if ($needle === '') {
            return null;
        }

        if (ctype_digit($needle)) {
            $where = 'user_id = ?';
            $type = 'i';
            $value = (int) $needle;
        } elseif (filter_var($needle, FILTER_VALIDATE_EMAIL)) {
            $where = 'user_email = ?';
            $type = 's';
            $value = $needle;
        } else {
            $where = 'user_name = ?';
            $type = 's';
            $value = $needle;
        }

        $sql = "SELECT {$columns} FROM users WHERE {$where} LIMIT 1";
        $stmt = $fernico_db->prepare($sql);
        $stmt->bind_param($type, $value);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row;
    }

    public function home()
    {
        global $fernico_db;
        App::setAdminRedirections();

        $opt = ['pageName' => 'Admin Dashboard'];

        // ---- update user --------------------------------------------------
        if (Request::POST('update_user')) {
            $err = $this->requireCsrf();
            if ($err) {
                $opt['responseMessage'] = $err;
            } else {
                $userId         = (int)    Request::POST('user_id', true);
                $userName       = (string) Request::POST('user_name', true);
                $userEmail      = (string) Request::POST('user_email', true);
                $userVerified   = (int)    Request::POST('user_verified', true);
                $claimsMade     = (int)    Request::POST('claims_made', true);
                $referredIncome = (float)  Request::POST('referred_income', true);
                $referralIncome = (float)  Request::POST('referral_income', true);
                $referral       = (int)    Request::POST('referral', true);
                $address        = (string) Request::POST('address', true);

                $stmt = $fernico_db->prepare(
                    'UPDATE users SET user_name = ?, user_email = ?,
                            user_verified = ?, claims_made = ?,
                            referred_income = ?, referral_income = ?,
                            referral = ?, address = ?
                      WHERE user_id = ?'
                );
                $stmt->bind_param(
                    'ssiiddisi',
                    $userName, $userEmail, $userVerified, $claimsMade,
                    $referredIncome, $referralIncome, $referral, $address, $userId
                );
                $stmt->execute();
                $stmt->close();
                $opt['responseMessage'] = 'Changes applied to the user account.';
            }
        }

        // ---- edit user (GET or POST) -------------------------------------
        $editTarget = Request::POST('edit_user') ? Request::POST('user', true) : Request::GET('edit_user', true);
        if ($editTarget) {
            $row = $this->findUser($editTarget,
                'COUNT(user_id) AS count, user_id, user_name, user_email,
                 user_verified, claims_made, referred_income,
                 referral_income, referral, address'
            );
            if ($row && (int) $row['count'] > 0) {
                $opt['showEditSection'] = true;
                $opt['editData'] = $row;
            } else {
                $opt['responseMessage'] = 'The user does not exist.';
            }
        }

        // ---- delete user (GET or POST) -----------------------------------
        $deleteTarget = Request::POST('delete_user') ? Request::POST('user', true) : Request::GET('delete_user', true);
        if ($deleteTarget) {
            $err = Request::isPost() ? $this->requireCsrf() : null;
            if ($err) {
                $opt['responseMessage'] = $err;
            } else {
                $row = $this->findUser($deleteTarget, 'COUNT(user_id) AS count, user_id, user_name');
                if ($row && (int) $row['count'] > 0) {
                    $stmt = $fernico_db->prepare('DELETE FROM users WHERE user_id = ?');
                    $userId = (int) $row['user_id'];
                    $stmt->bind_param('i', $userId);
                    $stmt->execute();
                    $stmt->close();
                    $opt['responseMessage'] =
                        'User <b>' . htmlspecialchars($row['user_name'], ENT_QUOTES) . '</b> has been deleted.';
                } else {
                    $opt['responseMessage'] = 'The user does not exist.';
                }
            }
        }

        // ---- ban / unban (GET or POST) -----------------------------------
        $banTarget = Request::POST('ban_unban_user') ? Request::POST('user', true) : Request::GET('ban_unban_user', true);
        if ($banTarget) {
            $err = Request::isPost() ? $this->requireCsrf() : null;
            if ($err) {
                $opt['responseMessage'] = $err;
            } else {
                $row = $this->findUser($banTarget,
                    'COUNT(user_id) AS count, user_id, user_name, account_status'
                );
                if ($row && (int) $row['count'] > 0) {
                    $newStatus = (int) $row['account_status'] === 1 ? 0 : 1;
                    $stmt = $fernico_db->prepare(
                        'UPDATE users SET account_status = ? WHERE user_id = ?'
                    );
                    $userId = (int) $row['user_id'];
                    $stmt->bind_param('ii', $newStatus, $userId);
                    $stmt->execute();
                    $stmt->close();

                    $verb = $newStatus === 0 ? 'banned' : 'unbanned';
                    $opt['responseMessage'] =
                        'User <b>' . htmlspecialchars($row['user_name'], ENT_QUOTES) . '</b> has been ' . $verb . '.';
                } else {
                    $opt['responseMessage'] = 'The user does not exist.';
                }
            }
        }

        $this->renderTemplate('Admin/Home.tpl', $opt);
    }

    public function banned__users()
    {
        $this->listUsers(true, 'Admin/Banned-Users.tpl', 'Banned Users');
    }

    public function users()
    {
        $this->listUsers(false, 'Admin/Users.tpl', 'Users');
    }

    /**
     * Shared paginated list for /admin/users and /admin/banned-users.
     */
    private function listUsers($bannedOnly, $template, $pageName)
    {
        global $fernico_db;
        App::setAdminRedirections();

        $where = $bannedOnly ? 'WHERE account_status = 0' : '';
        $records = 200;

        $count = $fernico_db->query("SELECT COUNT(user_id) AS id FROM users {$where}")->fetch_assoc();
        $totalRows = (int) $count['id'];
        $totalPages = max(1, (int) ceil($totalRows / $records));

        $reqPage = (int) Request::GET('offset', true);
        if ($reqPage < 1) {
            $reqPage = 1;
        }
        if ($reqPage > $totalPages) {
            $reqPage = $totalPages;
        }
        $offset = ($reqPage - 1) * $records;

        $cols = $bannedOnly
            ? 'user_id, user_name, user_email, registration_datetime, registration_ip'
            : 'user_id, user_name, user_email, registration_datetime, registration_ip, account_status';

        $sql = "SELECT {$cols} FROM users {$where} ORDER BY user_id DESC LIMIT ? OFFSET ?";
        $stmt = $fernico_db->prepare($sql);
        $stmt->bind_param('ii', $records, $offset);
        $stmt->execute();
        $rows = $stmt->get_result();

        $data = [];
        while ($r = $rows->fetch_assoc()) {
            $data[] = $r;
        }
        $stmt->close();

        $this->renderTemplate($template, [
            'pageName'    => $pageName,
            'items'       => $data,
            'req_page'    => $reqPage,
            'total_pages' => $totalPages,
        ]);
    }

    public function ads()
    {
        global $fernico_db;
        App::setAdminRedirections();

        $opt = ['pageName' => 'Ads'];

        if (Request::POST('submit')) {
            $err = $this->requireCsrf();
            if ($err) {
                $opt['responseMessage'] = $err;
            } else {
                $size = (int) Request::POST('size');
                $code = (string) Request::POST('code');
                $stmt = $fernico_db->prepare('INSERT INTO ads (type, code) VALUES (?, ?)');
                $stmt->bind_param('is', $size, $code);
                $stmt->execute();
                $stmt->close();
                $opt['responseMessage'] = 'Ad added.';
            }
        }

        if (Request::GET('d')) {
            $id = (int) Request::GET('d');
            $stmt = $fernico_db->prepare('DELETE FROM ads WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $opt['responseMessage'] = 'Ad deleted.';
        }

        $opt['items'] = $fernico_db->query('SELECT * FROM ads ORDER BY id DESC');
        $this->renderTemplate('Admin/Ads.tpl', $opt);
    }

    public function profile()
    {
        global $fernico_db;
        App::setAdminRedirections();

        $opt = ['pageName' => 'Admin Profile'];

        if (Request::POST('update_user_name') && App::isAdmin()) {
            $err = $this->requireCsrf();
            if ($err) {
                $opt['responseMessage'] = $err;
            } else {
                $userName = (string) Request::POST('new_user_name', true);
                if (strlen($userName) < 3) {
                    $opt['responseMessage'] = 'The username needs to be at least 3 characters.';
                } elseif (strlen($userName) > 16) {
                    $opt['responseMessage'] = 'The username must be 16 characters or less.';
                } elseif (preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $userName)) {
                    $opt['responseMessage'] = 'The username may not contain special characters.';
                } else {
                    $stmt = $fernico_db->prepare(
                        'UPDATE admin_details SET user_name = ? WHERE user_name = ?'
                    );
                    $stmt->bind_param('ss', $userName, $_SESSION['admin_user_name']);
                    $stmt->execute();
                    $stmt->close();
                    App::destroyAdminSession();
                    $opt['responseMessage'] = 'Username changed. Please log in again.';
                }
            }
        }

        if (Request::POST('update_password') && App::isAdmin()) {
            $err = $this->requireCsrf();
            if ($err) {
                $opt['responseMessage'] = $err;
            } else {
                $current = (string) Request::POST('current_password');
                $new = (string) Request::POST('new_password');

                $stmt = $fernico_db->prepare(
                    'SELECT password FROM admin_details WHERE user_name = ? LIMIT 1'
                );
                $stmt->bind_param('s', $_SESSION['admin_user_name']);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (strlen($new) < 6) {
                    $opt['responseMessage'] = 'New password must be at least 6 characters.';
                } elseif (strlen($new) > 64) {
                    $opt['responseMessage'] = 'New password may not exceed 64 characters.';
                } elseif (!$row || !App::adminPasswordVerify($current, $row['password'])) {
                    $opt['responseMessage'] = 'Current password does not match.';
                } else {
                    $newHash = App::adminPasswordHash($new);
                    $stmt = $fernico_db->prepare(
                        'UPDATE admin_details SET password = ? WHERE user_name = ?'
                    );
                    $stmt->bind_param('ss', $newHash, $_SESSION['admin_user_name']);
                    $stmt->execute();
                    $stmt->close();
                    App::destroyAdminSession();
                    $opt['responseMessage'] = 'Password changed. Please log in again.';
                }
            }
        }

        $this->renderTemplate('Admin/Profile.tpl', $opt);
    }

    public function login()
    {
        global $fernico_db;
        $opt = ['pageName' => 'Administrator Login'];

        if (Request::POST('login') && !App::isAdmin()) {
            $err = $this->requireCsrf();
            if ($err) {
                $opt['responseMessage'] = $err;
            } else {
                $userName = (string) Request::POST('user_name');
                $password = (string) Request::POST('password');

                $stmt = $fernico_db->prepare(
                    'SELECT id, password FROM admin_details WHERE user_name = ? LIMIT 1'
                );
                $stmt->bind_param('s', $userName);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($row && App::adminPasswordVerify($password, $row['password'])) {
                    $token = bin2hex(random_bytes(32));
                    $sessionTime = 60 * 60 * 24 * (int) Config::fetch('SESSION_DAYS');

                    $stmt = $fernico_db->prepare(
                        'UPDATE admin_details SET token = ? WHERE id = ?'
                    );
                    $stmt->bind_param('si', $token, $row['id']);
                    $stmt->execute();
                    $stmt->close();

                    $_SESSION['admin_user_name'] = $userName;
                    setcookie(
                        'admin_token',
                        $token,
                        time() + $sessionTime,
                        '/',
                        (string) Config::fetch('COOKIE_DOMAIN'),
                        (bool) Config::fetch('SECURE'),
                        true
                    );

                    header('Location: ' . fernico_getAbsURL() . 'admin/home');
                    fernico_destroy();
                }
                $opt['responseMessage'] = 'Invalid login credentials.';
            }
        }

        if (App::isAdmin()) {
            header('Location: ' . fernico_getAbsURL() . 'admin/home');
            fernico_destroy();
        }

        $this->renderTemplate('Admin/Login.tpl', $opt);
    }

    public function settings()
    {
        global $fernico_db;
        App::setAdminRedirections();

        $opt = ['pageName' => 'Settings'];

        if (Request::POST('update')) {
            $err = $this->requireCsrf();
            if ($err) {
                $opt['responseMessage'] = $err;
            } else {
                $stmt = $fernico_db->prepare(
                    'UPDATE config SET value = ? WHERE parameter = ?'
                );
                foreach ($_POST as $key => $value) {
                    if ($key === 'csrf_token' || $key === 'update') {
                        continue;
                    }
                    if ($key === 'coin_information') {
                        $units = explode('-', (string) $value, 2);
                        if (count($units) === 2) {
                            $abbrev = $units[0];
                            $name = $units[1];

                            $a = 'coin_abbreviation';
                            $stmt->bind_param('ss', $abbrev, $a);
                            $stmt->execute();

                            $b = 'coin_name';
                            $stmt->bind_param('ss', $name, $b);
                            $stmt->execute();
                        }
                        continue;
                    }
                    $stringValue = is_array($value) ? json_encode($value) : (string) $value;
                    $stmt->bind_param('ss', $stringValue, $key);
                    $stmt->execute();
                }
                $stmt->close();
                $opt['responseMessage'] = 'Settings updated.';
            }
        }

        $contents = json_decode((string) fernico_post('https://faucetpay.io/page/currs'), true);
        $opt['coins'] = is_array($contents) && isset($contents['currencies_names'])
            ? $contents['currencies_names']
            : [];

        $this->renderTemplate('Admin/Settings.tpl', $opt);
    }

    public function logout()
    {
        App::setAdminRedirections();
        App::destroyAdminSession();
        header('Location: ' . fernico_getAbsURL() . 'admin/login');
        fernico_destroy();
    }
}
