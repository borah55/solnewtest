<?php
/**
 * Thin glue between accountController and the Authentication class.
 *
 * Bug fixes vs. the original:
 *   - removed the dead FaucetHub.io address pre-check in registerHandler
 *     (FaucetHub closed in 2020); we now only require terms-of-service
 *     agreement and a matching email pair.
 *
 * @package Solnew
 */

if (!defined('FERNICO')) {
    http_response_code(403);
    exit('Forbidden');
}

class Account
{
    static function loginHandler($authMod)
    {
        if (!Request::POST('login')
            || !Request::POST('user_name')
            || !Request::POST('password')) {
            return 'Please fill in all the fields.';
        }

        $response = $authMod->login(
            (string) Request::POST('user_name'),
            (string) Request::POST('password')
        );

        if ($response === 'LOGIN_SUCCESS') {
            return true;
        }
        return ResponseTranslator::respCode($response);
    }

    static function registerHandler($authMod)
    {
        if (!Request::POST('register')) {
            return '';
        }

        if (!Request::POST('tos_agree')) {
            return 'You need to agree to our terms and conditions to register.';
        }

        if (Request::POST('email') !== Request::POST('email_repeat')) {
            return 'Your email addresses do not match. Please try again.';
        }

        $response = $authMod->register(
            (string) Request::POST('user_name'),
            (string) Request::POST('email'),
            (string) Request::POST('password'),
            (string) Request::POST('password_repeat')
        );

        return ResponseTranslator::respCode($response);
    }

    static function resetPasswordHandler($authMod)
    {
        if (!Request::POST('reset_password')) {
            return '';
        }
        return ResponseTranslator::respCode(
            $authMod->sendPasswordResetEmail((string) Request::POST('user_name'))
        );
    }

    static function resendEmailHandler($authMod)
    {
        if (!Request::POST('resend_email')) {
            return '';
        }
        return ResponseTranslator::respCode(
            $authMod->resendActivationEmail((string) Request::POST('user_name'))
        );
    }

    static function confirmEmailHandler($authMod, $hash)
    {
        return ResponseTranslator::respCode($authMod->confirmEmail((string) $hash));
    }

    static function confirmEmailChangeHandler($authMod, $hash)
    {
        return ResponseTranslator::respCode($authMod->confirmEmailChange((string) $hash));
    }

    static function confirmPasswordChangeHandler($authMod, $hash)
    {
        if (Request::POST('password') === null
            || Request::POST('password_repeat') === null) {
            return 'Please fill in both password fields.';
        }

        $response = $authMod->setNewPassword(
            (string) $hash,
            (string) Request::POST('password'),
            (string) Request::POST('password_repeat')
        );

        if ($response === 'PASSWORD_RESET_SUCCESSFUL') {
            return true;
        }
        return ResponseTranslator::respCode($response);
    }

    static function changeEmailHandler($authMod)
    {
        return ResponseTranslator::respCode(
            $authMod->changeEmail((string) Request::POST('email'))
        );
    }

    static function changePasswordHandler($authMod)
    {
        return ResponseTranslator::respCode(
            $authMod->changePassword(
                (string) Request::POST('password'),
                (string) Request::POST('password_repeat')
            )
        );
    }
}
