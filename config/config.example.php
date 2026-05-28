<?php
/**
 * Solnew configuration template.
 *
 * Copy to config.php and edit the database / cookie values; the rest of
 * the constants are sensible defaults that work out of the box.
 *
 * The installer at /resources/Installer/ writes this file automatically.
 */

if (!defined('FERNICO')) {
    http_response_code(403);
    exit('Forbidden');
}

// Marker that prevents the kernel from re-running the installer.
define('SCRIPT_INSTALLED', true);

$fernico_db_settings = [
    'DATABASE_HOST'     => 'localhost',
    'DATABASE_NAME'     => 'solnew',
    'DATABASE_USER'     => 'solnew_user',
    'DATABASE_PASSWORD' => 'change-me',
];

$fernico_misc_settings = [
    // Leading dot lets cookies span subdomains. Empty = current host only.
    'COOKIE_DOMAIN' => '',

    // Random secret used to derive the remember-me cookie hash. CHANGE.
    'COOKIE_SECRET' => 'change-this-to-a-long-random-string',

    // Force-set the public URL (with trailing slash). Leave empty to
    // auto-detect from request headers.
    'WEBSITE_URL'   => '',
];

$fernico_core_settings = [
    'CONNECT_TO_DATABASE'      => true,

    'DEFAULT_CONTROLLER'       => 'homeIndex',
    'DEFAULT_ACTION'           => 'home',

    'ERROR_REPORTING'          => true,
    'ERROR_LOG_DATABASE'       => false,

    // Active theme name; matches a directory in /views/.
    'TEMPLATE_DIR'             => 'Nova',
    'TEMPLATE_COMPILED_DIR'    => FERNICO_PATH . '/storage/cache/templates_c',
    'TEMPLATE_FORCE_COMPILE'   => false,

    'SESSION_NAME'             => 'solnew_session',
    'SECURE'                   => false, // set to true behind HTTPS
    'HTTP_ONLY'                => true,
    'SESSION_DAYS'             => 30,

    'CONFIRMATION_CONTROLLER'  => 'account',
    'CONFIRMATION_ACTION'      => 'confirm_account',
    'RESET_PASSWORD_CONTROLLER'=> 'account',
    'RESET_PASSWORD_ACTION'    => 'confirm_password_change',
    'CHANGE_EMAIL_CONTROLLER'  => 'account',
    'CHANGE_EMAIL_ACTION'      => 'confirm_email_change',
];

/*
 * The block below stitches every $fernico_*_settings array into one
 * global lookup table. Add your own arrays following the
 * fernico_<name>_settings naming convention to inject extra keys.
 */

$ignore = [
    'GLOBALS', '_FILES', '_COOKIE', '_POST', '_GET', '_SERVER',
    '_ENV', 'ignore', 'php_errormsg', 'HTTP_RAW_POST_DATA',
    'http_response_header', 'argc', 'argv',
];

$all_settings_found = array_diff_key(
    get_defined_vars() + array_flip($ignore),
    array_flip($ignore)
);

$global_fernico_settings = [];

foreach ($all_settings_found as $key => $value) {
    if (substr($key, 0, 8) === 'fernico_' && substr($key, -9) === '_settings') {
        $global_fernico_settings = array_merge($global_fernico_settings, $value);
    }
}
