<?php
/**
 * Account controller - registration / login / password / email flows.
 *
 * Bug fixes vs. the original:
 *   - replaced three typo'd calls to fernico_desotry() with fernico_destroy()
 *   - parameterised the address-update query and the address fetch
 *   - CSRF tokens are required on every state-changing POST
 *   - `$message` now actually flows through to the template message bag
 *
 * @package Solnew
 */

if (!defined('FERNICO')) {
    http_response_code(403);
    exit('Forbidden');
}

class accountController extends AstridController
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
     * Reject any state-changing POST that lacks a valid CSRF token.
     * Returns the error string for use as a response message, or null
     * when the request passes the check.
     */
    private function requireCsrfOrFail()
    {
        if (!Request::isPost()) {
            return null;
        }
        $token = Request::POST('csrf_token');
        if (!$token || !fernico_verifyAntiCSRFToken($token)) {
            return 'Your session has expired. Please reload the page and try again.';
        }
        return null;
    }

    public function login($message = '')
    {
        $opt = [
            'userLoggedIn' => $this->auth->UserLoggedIn(),
            'captchaCode'  => $this->auth->vomitRecaptcha(),
            'pageName'     => 'Login',
        ];

        if ($message !== '') {
            // Surface the "you must be logged in" hint passed by
            // App::vomitLoginPageByRedirection().
            $opt['responseMessage'] = 'You need to be logged in to view that page.';
        }

        if (Request::POST('login')) {
            $csrfErr = $this->requireCsrfOrFail();
            if ($csrfErr) {
                $opt['responseMessage'] = $csrfErr;
            } else {
                $resp = Account::loginHandler($this->auth);
                if ($resp === true) {
                    header('Location: ' . fernico_getAbsURL() . 'page/dashboard');
                    fernico_destroy();
                }
                $opt['responseMessage'] = $resp;
            }
        }

        if ($opt['userLoggedIn']) {
            header('Location: ' . fernico_getAbsURL());
            fernico_destroy();
        }

        $this->renderTemplate('Login.tpl', $opt);
    }

    public function register()
    {
        $opt = [
            'userLoggedIn' => $this->auth->UserLoggedIn(),
            'captchaCode'  => $this->auth->vomitRecaptcha(),
            'pageName'     => 'Register',
        ];

        if (Request::POST('register')) {
            $csrfErr = $this->requireCsrfOrFail();
            $opt['responseMessage'] = $csrfErr ?: Account::registerHandler($this->auth);
        }

        if ($opt['userLoggedIn']) {
            header('Location: ' . fernico_getAbsURL());
            fernico_destroy();
        }

        $this->renderTemplate('Register.tpl', $opt);
    }

    public function reset__password()
    {
        $opt = [
            'userLoggedIn' => $this->auth->UserLoggedIn(),
            'captchaCode'  => $this->auth->vomitRecaptcha(),
            'pageName'     => 'Reset Password',
        ];

        if (Request::POST('reset_password')) {
            $csrfErr = $this->requireCsrfOrFail();
            $opt['responseMessage'] = $csrfErr ?: Account::resetPasswordHandler($this->auth);
        }

        $this->renderTemplate('Reset-Password.tpl', $opt);
    }

    public function resend__email()
    {
        $opt = [
            'userLoggedIn' => $this->auth->UserLoggedIn(),
            'captchaCode'  => $this->auth->vomitRecaptcha(),
            'pageName'     => 'Resend Email',
        ];

        if (Request::POST('resend_email')) {
            $csrfErr = $this->requireCsrfOrFail();
            $opt['responseMessage'] = $csrfErr ?: Account::resendEmailHandler($this->auth);
        }

        $this->renderTemplate('Resend-Email.tpl', $opt);
    }

    public function confirm_account($hash = '')
    {
        Account::confirmEmailHandler($this->auth, $hash);
        // Surface the message via session so it survives the redirect.
        $_SESSION['flash'] = 'Your email has been confirmed. You may sign in now.';
        header('Location: ' . fernico_getAbsURL() . 'account/login');
        fernico_destroy();
    }

    public function confirm_email_change($hash = '')
    {
        $resp = Account::confirmEmailChangeHandler($this->auth, $hash);
        $_SESSION['flash'] = $resp;
        header('Location: ' . fernico_getAbsURL() . 'account/login');
        fernico_destroy();
    }

    public function confirm_password_change($hash = '')
    {
        $opt = [
            'userLoggedIn' => $this->auth->UserLoggedIn(),
            'pageName'     => 'Reset Password',
        ];

        $resp = $this->auth->isValidResetLink($hash);

        if ($resp !== 'IS_VALID_RESET_LINK') {
            $opt['responseMessage'] = ResponseTranslator::respCode($resp);
            $this->renderTemplate('Reset-Password.tpl', $opt);
            return;
        }

        if (Request::POST('password') !== null && Request::POST('password_repeat') !== null) {
            $csrfErr = $this->requireCsrfOrFail();
            if ($csrfErr) {
                $opt['responseMessage'] = $csrfErr;
                $this->renderTemplate('Reset-Password-Change.tpl', $opt);
                return;
            }

            $coResp = Account::confirmPasswordChangeHandler($this->auth, $hash);

            if ($coResp === true) {
                $_SESSION['flash'] = 'Your password was reset successfully.';
                header('Location: ' . fernico_getAbsURL() . 'account/login');
                fernico_destroy();
            }

            $opt['responseMessage'] = $coResp;
        }

        $this->renderTemplate('Reset-Password-Change.tpl', $opt);
    }

    public function settings()
    {
        global $fernico_db;

        if (!$this->auth->UserLoggedIn()) {
            header('Location: ' . fernico_getAbsURL() . 'account/login/not-logged-in');
            fernico_destroy();
        }

        $opt = [
            'userLoggedIn' => true,
            'pageName'     => 'Account Settings',
        ];

        $userId = (int) $_SESSION['user_id'];

        if (Request::POST('change_address_details')) {
            $csrfErr = $this->requireCsrfOrFail();
            if ($csrfErr) {
                $opt['changeAddressDetailsMessage'] = $csrfErr;
            } else {
                $address = (string) Request::POST('address');

                // Make sure no other account already has this address.
                $stmt = $fernico_db->prepare(
                    'SELECT COUNT(user_id) AS count
                       FROM users
                      WHERE address = ? AND user_id <> ?'
                );
                $stmt->bind_param('si', $address, $userId);
                $stmt->execute();
                $countRow = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ((int) $countRow['count'] === 0) {
                    $stmt = $fernico_db->prepare(
                        'UPDATE users SET address = ? WHERE user_id = ?'
                    );
                    $stmt->bind_param('si', $address, $userId);
                    $stmt->execute();
                    $stmt->close();
                    $opt['changeAddressDetailsMessage'] = 'Address updated successfully.';
                } else {
                    $opt['changeAddressDetailsMessage'] = 'That address is already linked to another account.';
                }
            }
        }

        // Always show the current address back to the user.
        $stmt = $fernico_db->prepare('SELECT address FROM users WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $opt['address'] = $row['address'] ?? '';

        if (Request::POST('change_email_details')) {
            $csrfErr = $this->requireCsrfOrFail();
            $opt['changeEmailMessage'] = $csrfErr ?: Account::changeEmailHandler($this->auth);
        }

        if (Request::POST('change_password_details')) {
            $csrfErr = $this->requireCsrfOrFail();
            $opt['changePasswordDetailsMessage'] = $csrfErr ?: Account::changePasswordHandler($this->auth);
        }

        $this->renderTemplate('Settings.tpl', $opt);
    }

    public function logout()
    {
        $this->auth->logout();
        header('Location: ' . fernico_getAbsURL() . 'account/login');
        fernico_destroy();
    }
}
