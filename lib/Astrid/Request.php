<?php
/**
 * Thin convenience wrapper around superglobals.
 *
 * Provides safe getters with optional XSS sanitisation and a few helpers
 * for HTTP method detection.
 *
 * @package Fernico
 */

if (!defined('FERNICO')) {
    http_response_code(403);
    exit('Forbidden');
}

class Request
{
    /**
     * Strip script/style tags and ad-hoc HTML; not a substitute for
     * proper output escaping, but useful as a defence-in-depth filter.
     */
    public static function cleanInput($input)
    {
        if (!is_string($input)) {
            return $input;
        }

        $patterns = [
            '@<script[^>]*?>.*?</script>@si',
            '@<[\/\!]*?[^<>]*?>@si',
            '@<style[^>]*?>.*?</style>@siU',
            '@<![\s\S]*?--[ \t\n\r]*>@',
        ];

        return strip_tags(preg_replace($patterns, '', $input));
    }

    public static function POST($key, $filtered = false)
    {
        if (!isset($_POST[$key])) {
            return null;
        }
        return $filtered ? self::cleanInput($_POST[$key]) : $_POST[$key];
    }

    public static function GET($key, $filtered = false)
    {
        if (!isset($_GET[$key])) {
            return null;
        }
        return $filtered ? self::cleanInput($_GET[$key]) : $_GET[$key];
    }

    public static function COOKIE($key, $filtered = false)
    {
        if (!isset($_COOKIE[$key])) {
            return null;
        }
        return $filtered ? self::cleanInput($_COOKIE[$key]) : $_COOKIE[$key];
    }

    public static function method()
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function isPost()
    {
        return self::method() === 'POST';
    }
}
