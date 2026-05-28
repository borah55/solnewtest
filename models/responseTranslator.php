<?php
/**
 * Translates internal status codes to user-facing strings.
 *
 * @package Solnew
 */

if (!defined('FERNICO')) {
    http_response_code(403);
    exit('Forbidden');
}

class ResponseTranslator
{
    /**
     * Lookup table of response code → friendly message.
     *
     * @return string The message, or the original code as a fallback.
     */
    public static function lang($code = '')
    {
        $codes = [
            'ER_CAPTCHA_INVALID'                       => 'The captcha you entered is not valid.',
            'ER_USER_NAME_SHORT'                       => 'The username you entered is too short.',
            'ER_USER_NAME_LONG'                        => 'The username may not be longer than 16 characters.',
            'ER_USER_NAME_CONTAINS_SPECIAL_CHARACTERS' => 'The username may not contain special characters.',
            'ER_PASSWORD_SHORT'                        => 'The password must be at least 6 characters.',
            'ER_PASSWORD_LONG'                         => 'The password may not be longer than 64 characters.',
            'ER_USER_NOT_FOUND'                        => 'The username/password you entered is not valid.',
            'ER_PASSWORD_INCORRECT'                    => 'The username/password you entered is not valid.',
            'ER_ACCOUNT_NOT_VERIFIED'                  => 'You have not confirmed your email address yet.',
            'ER_TOO_MANY_ATTEMPTS'                     => 'Too many failed attempts. Please try again in 15 minutes.',
            'ER_EMAIL_BLANK'                           => 'You left the email address blank.',
            'ER_EMAIL_INVALID'                         => 'The email address you entered is not valid.',
            'ER_PASSWORD_REPEATING_NOT_MATCHING'       => 'The two passwords do not match.',
            'ER_USER_ALREADY_EXISTS'                   => 'That username is already taken.',
            'ER_EMAIL_ALREADY_EXISTS'                  => 'That email address is already registered.',
            'ER_USER_NAME_INVALID'                     => 'The username you entered does not exist.',
            'ER_SAME_EMAIL'                            => 'The email you entered is already on your account.',
            'ER_INVALID_EMAIL_CHANGE_LINK'             => 'The email-change link is not valid or has expired.',
            'LOGIN_SUCCESS'                            => 'You have been signed in successfully.',
            'REGISTER_SUCCESS_NO_ACTIVATION_EMAIL'     => 'Your account was created. You may sign in now.',
            'REGISTER_SUCCESS_ACTIVATION_EMAIL_SENT'   => 'Your account was created. Please confirm your email address; check the spam folder if you can\'t see the message.',
            'SUCCESS_RESET_LINK_SENT'                  => 'A password reset link has been sent to your email. Check the spam folder if you can\'t see the message.',
            'SUCCESS_ACTIVATION_EMAIL_RESENT'          => 'The activation email has been resent. Check the spam folder if you can\'t see the message.',
            'SUCCESS_EMAIL_CONFIRMED'                  => 'Your email has been confirmed. You may sign in now.',
            'ER_WRONG_ACTIVATION_LINK'                 => 'The activation link is not valid or has expired.',
            'SUCCESS_EMAIL_CHANGED'                    => 'Your email was changed successfully.',
            'IS_VALID_RESET_LINK'                      => '',
            'IS_NOT_VALID_RESET_LINK'                  => 'The reset link is not valid or has expired.',
            'PASSWORD_RESET_SUCCESSFUL'                => 'Your password has been reset successfully.',
            'SUCCESS_EMAIL_CHANGE_EMAIL_SENT'          => 'Please confirm your new email address by clicking the link sent to your inbox.',
            'USER_NOT_LOGGED_IN'                       => 'You must be signed in to perform this action.',
            'PASSWORD_SUCCESSFULLY_CHANGED'            => 'Your password has been changed.',
            'ALREADY_ACCOUNT'                          => 'It looks like you already have an account with us.',
            'ER_ACCOUNT_BANNED'                        => 'Your account has been suspended.',
        ];

        return $codes[$code] ?? (string) $code;
    }

    public static function respCode($code)
    {
        return self::lang(strtoupper(trim((string) $code)));
    }
}
