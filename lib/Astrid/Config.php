<?php
/**
 * Static configuration accessor.
 *
 * The installer writes a config.php file that populates the
 * $global_fernico_settings global. All consumers reach for values via
 * Config::fetch('KEY').
 *
 * @package Fernico
 */

if (!defined('FERNICO')) {
    http_response_code(403);
    exit('Forbidden');
}

class Config
{
    /**
     * Look up a configuration key.
     *
     * @return mixed The stored value or null when the key is missing.
     */
    public static function fetch($key)
    {
        global $global_fernico_settings;

        if (!is_array($global_fernico_settings)) {
            return null;
        }

        return $global_fernico_settings[$key] ?? null;
    }

    /**
     * Override or add a configuration value at runtime.
     */
    public static function set($key, $value)
    {
        global $global_fernico_settings;

        if (!is_array($global_fernico_settings)) {
            $global_fernico_settings = [];
        }

        $global_fernico_settings[$key] = $value;
    }
}
