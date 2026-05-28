<?php
/**
 * Vendor library bootstrap and PSR-style autoloader.
 *
 * Pulls in Smarty (which has its own autoloader) and registers an
 * autoloader for the few hand-rolled libraries that ship with this
 * project.
 *
 * Note: this codebase ships with the Smarty `sysplugins/` directory at
 * /lib/sysplugins/ rather than /lib/Smarty/sysplugins/ (the default
 * Smarty layout). We define SMARTY_SYSPLUGINS_DIR before loading
 * Smarty.class.php so the includes resolve correctly.
 *
 * @package Fernico
 */

if (!defined('FERNICO')) {
    http_response_code(403);
    exit('Forbidden');
}

// Point Smarty at our actual sysplugins layout.
if (!defined('SMARTY_SYSPLUGINS_DIR')) {
    if (is_dir(FERNICO_PATH . '/lib/Smarty/sysplugins')) {
        define('SMARTY_SYSPLUGINS_DIR', FERNICO_PATH . '/lib/Smarty/sysplugins/');
    } else {
        define('SMARTY_SYSPLUGINS_DIR', FERNICO_PATH . '/lib/sysplugins/');
    }
}

require_once FERNICO_PATH . '/lib/Smarty/Smarty.class.php';

spl_autoload_register(function ($class) {
    $map = [
        'PHPMailer'      => '/lib/PHPMailer/index.php',
        'Authentication' => '/lib/Authentication/Authentication.php',
    ];

    if (isset($map[$class])) {
        $path = FERNICO_PATH . $map[$class];
        if (file_exists($path)) {
            require_once $path;
        }
    }
});
