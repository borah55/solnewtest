<?php
/**
 * Session bootstrapper used as the base for AstridController.
 *
 * Modern, PHP 7.1+ compatible session settings. The previous version
 * relied on `session.entropy_*` and `session.hash_*` ini values which
 * were removed in PHP 7.1 and silently break newer installs.
 *
 * @package Fernico
 */

if (!defined('FERNICO')) {
    http_response_code(403);
    exit('Forbidden');
}

class AstridSession
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return; // Already started by a parent request.
        }

        // Cookie hardening - use lax sameSite to keep OAuth-style
        // redirects working but block CSRF from third-party origins.
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');

        $sessionName = Config::fetch('SESSION_NAME') ?: 'fernico_session';
        $secure = (bool) Config::fetch('SECURE');
        $httpOnly = (bool) Config::fetch('HTTP_ONLY');

        $params = session_get_cookie_params();

        // PHP 7.3+ supports the SameSite option natively via an array.
        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params([
                'lifetime' => $params['lifetime'],
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $secure,
                'httponly' => $httpOnly,
                'samesite' => 'Lax',
            ]);
        } else {
            session_set_cookie_params(
                $params['lifetime'],
                $params['path'],
                $params['domain'],
                $secure,
                $httpOnly
            );
        }

        session_name($sessionName);
        session_start();

        // Periodic regeneration to mitigate fixation attacks.
        if (empty($_SESSION['__regen_at']) || $_SESSION['__regen_at'] < time() - 1800) {
            session_regenerate_id(true);
            $_SESSION['__regen_at'] = time();
        }
    }
}
