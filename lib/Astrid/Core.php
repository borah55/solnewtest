<?php
/**
 * Framework helper functions.
 *
 * These free functions are available globally once the framework boots.
 * Anything that doesn't naturally fit on a class lives here.
 *
 * @package Fernico
 */

if (!defined('FERNICO')) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * Persist an error to the configured destination (DB or file).
 */
function fernico_reportError($error)
{
    global $fernico_db;

    if (!Config::fetch('ERROR_REPORTING')) {
        return;
    }

    $error = (string) $error;

    if (Config::fetch('ERROR_LOG_DATABASE') && isset($fernico_db) && $fernico_db instanceof mysqli) {
        $stmt = $fernico_db->prepare('INSERT INTO error_log (message) VALUES (?)');
        if ($stmt) {
            $stmt->bind_param('s', $error);
            $stmt->execute();
            $stmt->close();
        }
        return;
    }

    $logFile = FERNICO_PATH . '/storage/log/error.log';
    if (!is_dir(dirname($logFile))) {
        @mkdir(dirname($logFile), 0755, true);
    }

    $line = '[' . date('Y-m-d H:i:s') . '] ' . $error . PHP_EOL;
    @file_put_contents($logFile, $line, FILE_APPEND);
}

/**
 * Lightweight cURL GET wrapper. Returns the body or false on failure.
 */
function fernico_get($url)
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_USERAGENT      => 'Solnew/1.0 (+fernico)',
    ]);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * Lightweight cURL POST wrapper using application/x-www-form-urlencoded.
 */
function fernico_post($url, array $data = [])
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_USERAGENT      => 'Solnew/1.0 (+fernico)',
    ]);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

/**
 * Tear down the request cleanly. If passed an error string we log it
 * before exiting.
 */
function fernico_destroy($error = null)
{
    global $fernico_db;

    if ($error !== null) {
        fernico_reportError($error);
    }

    if (isset($fernico_db) && $fernico_db instanceof mysqli) {
        @$fernico_db->close();
    }

    exit;
}

/**
 * Read the framework version string from disk.
 */
function fernico_version()
{
    $file = FERNICO_PATH . '/lib/Astrid/Version.php';
    if (!is_readable($file)) {
        return 'unknown';
    }
    return trim((string) file_get_contents($file));
}

/**
 * Render a template manually (without going through a controller).
 */
function fernico_loadComponent($template_dir, $template, array $options = [])
{
    $smarty = new Smarty();

    $options = array_merge([
        'csrf_token' => fernico_generateAntiCSRFToken(),
        'site_url'   => fernico_getAbsURL(),
        'flash'      => isset($_SESSION['flash']) ? (string) $_SESSION['flash'] : '',
    ], $options);

    // Flash is one-shot.
    unset($_SESSION['flash']);

    foreach ($options as $key => $value) {
        $smarty->assign($key, $value);
    }

    if (defined('CSS_FIX')) {
        $smarty->assign('css_fix', CSS_FIX);
    }

    $compileDir = Config::fetch('TEMPLATE_COMPILED_DIR');
    if ($compileDir && !is_dir($compileDir)) {
        @mkdir($compileDir, 0755, true);
    }
    $smarty->setCompileDir($compileDir);
    $smarty->loadFilter('output', 'trimwhitespace');
    $smarty->display(FERNICO_PATH . '/views/' . $template_dir . '/' . $template);
}

/**
 * Non-fatal error handler. Just records the problem and lets PHP carry
 * on. The shutdown handler picks up anything fatal.
 */
function fernico_nonCriticalErrorHandler($err_no, $err_str, $err_file, $err_line)
{
    // Respect @suppression and the configured error_reporting() mask.
    if (!(error_reporting() & $err_no)) {
        return false;
    }
    fernico_reportError("[$err_file:$err_line] $err_str");
    return false; // Allow PHP's normal handler to also run.
}

/**
 * Shutdown function that catches truly fatal errors (parse errors etc.)
 * and routes them to the friendly fatal-error page.
 */
function fernico_criticalErrorHandler()
{
    $error = error_get_last();
    if (!$error) {
        return;
    }

    $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array($error['type'], $fatal, true)) {
        return;
    }

    fernico_reportError($error['message']);

    // Avoid recursion if the fatal error happened inside the controller.
    if (defined('FERNICO_FATAL_HANDLED')) {
        return;
    }
    define('FERNICO_FATAL_HANDLED', true);

    if (file_exists(FERNICO_PATH . '/controllers/fatalErrorController.php')) {
        fernico_callController('fatalError', 'errorHandler', [
            'A critical error occurred in the application. Terminating.',
            $error['message'],
        ]);
    }

    fernico_destroy();
}

/**
 * Dispatch to a controller manually. Only the fatal/error flows use
 * this; normal traffic goes through the kernel.
 */
function fernico_callController($name, $method, array $parameters = [])
{
    $name = $name . 'Controller';
    $path = FERNICO_PATH . '/controllers/' . $name . '.php';
    if (!file_exists($path)) {
        return;
    }
    require_once $path;

    if (!class_exists($name)) {
        return;
    }
    $controller = new $name();

    if (!method_exists($controller, $method)) {
        return;
    }

    if ($parameters) {
        call_user_func_array([$controller, $method], $parameters);
    } else {
        $controller->{$method}();
    }
}

function fernico_showLoadedFunctions()
{
    global $global_fernico_plugins;
    return $global_fernico_plugins;
}

/**
 * Register a callable to fire when a function is invoked. Hooks are
 * stored in $fernico_hooks_registered and dispatched by
 * fernico_executeHooks().
 */
function fernico_registerHook($function, $callback, array $parameters = [])
{
    global $fernico_hooks_registered;

    if (!is_array($fernico_hooks_registered)) {
        $fernico_hooks_registered = [];
    }

    $fernico_hooks_registered[] = [
        'onFunction'     => $function,
        'callFunction'   => $callback,
        'withParameters' => $parameters,
    ];
}

/**
 * Walk the hook list and call any callbacks attached to whichever
 * function this is invoked from.
 */
function fernico_executeHooks()
{
    global $fernico_hooks_registered;

    if (!is_array($fernico_hooks_registered) || !$fernico_hooks_registered) {
        return null;
    }

    $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? null;
    if (!$caller) {
        return null;
    }

    foreach ($fernico_hooks_registered as $hook) {
        if ($hook['onFunction'] !== $caller) {
            continue;
        }
        if (is_array($hook['withParameters'])) {
            return call_user_func_array($hook['callFunction'], $hook['withParameters']);
        }
        return ($hook['callFunction'])($hook['withParameters']);
    }

    return null;
}

/**
 * Generate a fresh CSRF token, store it in the session, and return it.
 * Existing tokens are reused for the lifetime of the session to avoid
 * breaking forms in multiple browser tabs.
 */
function fernico_generateAntiCSRFToken()
{
    if (empty($_SESSION['fernico_token'])) {
        $_SESSION['fernico_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['fernico_token'];
}

/**
 * Constant-time CSRF token comparison.
 */
function fernico_verifyAntiCSRFToken($input_token)
{
    if (empty($_SESSION['fernico_token']) || !is_string($input_token)) {
        return false;
    }
    return hash_equals($_SESSION['fernico_token'], $input_token);
}

/**
 * Build the absolute URL of the application's root, with a trailing
 * slash. Honours the WEBSITE_URL config override when set.
 *
 * The previous version's scheme detection was inverted (it set the
 * scheme to https when the port was *not* 80). This version derives
 * the scheme from HTTPS / HTTP_X_FORWARDED_PROTO / SERVER_PORT.
 */
function fernico_getAbsURL()
{
    $configured = Config::fetch('WEBSITE_URL');
    if (!empty($configured)) {
        return rtrim($configured, '/') . '/';
    }

    // Forwarded protocol (CDN/proxy in front of us).
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $scheme = strtolower(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]);
    } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $scheme = 'https';
    } elseif (!empty($_SERVER['REQUEST_SCHEME'])) {
        $scheme = $_SERVER['REQUEST_SCHEME'];
    } elseif (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
        $scheme = 'https';
    } else {
        $scheme = 'http';
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host . '/';
}

/**
 * Best-effort visitor IP extraction. Honours common proxy headers but
 * skips RFC1918 / loopback / link-local.
 */
function fernico_getIPAddress()
{
    $candidates = [
        $_SERVER['HTTP_CLIENT_IP'] ?? null,
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
        $_SERVER['HTTP_X_FORWARDED'] ?? null,
        $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] ?? null,
        $_SERVER['HTTP_FORWARDED_FOR'] ?? null,
        $_SERVER['HTTP_FORWARDED'] ?? null,
    ];

    foreach ($candidates as $header) {
        if (!$header) {
            continue;
        }
        // Some headers contain a comma-separated chain - pick the first
        // public IP in the list.
        foreach (explode(',', $header) as $candidate) {
            $candidate = trim($candidate);
            if (fernico_validateIPAddress($candidate)) {
                return $candidate;
            }
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Validate a public-routable IPv4 / IPv6 address.
 */
function fernico_validateIPAddress($ip)
{
    if (!is_string($ip) || strtolower($ip) === 'unknown' || $ip === '') {
        return false;
    }

    return (bool) filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    );
}

/**
 * Resolve the visitor's two-letter ISO country code. Tries Cloudflare's
 * header first (zero-cost) then falls back to ipinfo.io. Returns the
 * empty string when nothing succeeds.
 */
function fernico_countryCode()
{
    $cf = Request::cleanInput($_SERVER['HTTP_CF_IPCOUNTRY'] ?? '');
    if ($cf && $cf !== 'XX') {
        return $cf;
    }

    $ip = fernico_getIPAddress();
    $json = fernico_get('https://ipinfo.io/' . urlencode($ip) . '/json');
    if (!$json) {
        return '';
    }

    $data = json_decode($json, true);
    return is_array($data) && !empty($data['country']) ? $data['country'] : '';
}

// Register the framework's error handlers.
set_error_handler('fernico_nonCriticalErrorHandler', E_ALL);
register_shutdown_function('fernico_criticalErrorHandler');
