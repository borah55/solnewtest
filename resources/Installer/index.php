<?php
/**
 * Solnew installer wizard.
 *
 * Bug fixes vs. the original:
 *   * UPDATE config SET value = ? WHERE name = ?  (column is `parameter`)
 *   * rmdir(... 'installer') used a different case to the actual folder
 *   * sha1(time . mt_rand) replaced with random_bytes(32)
 *   * the "successfully installed" message had a stray space inside the
 *     class attribute ("alert alert - success")
 *   * the user could submit blank fields and trigger SQL warnings
 *
 * @package Solnew\Installer
 */

if (!defined('FERNICO')) {
    define('FERNICO_PATH', dirname(dirname(__DIR__)));
    define('FERNICO', true);
}

// If we are already installed, just bounce people back home.
if (file_exists(FERNICO_PATH . '/config/config.php')) {
    require_once FERNICO_PATH . '/config/config.php';
    if (defined('SCRIPT_INSTALLED')) {
        header('Location: /');
        exit;
    }
}

require_once __DIR__ . '/functions.php';

$siteUrl = installer_detect_url();

// ------------------------------ Pre-flight ----------------------------
$status = true;
$checks = [];

$checks[] = [
    'label'   => 'PHP version',
    'value'   => PHP_VERSION,
    'state'   => version_compare(PHP_VERSION, '7.1.0', '>=') ? 'ok' : 'err',
    'message' => version_compare(PHP_VERSION, '7.1.0', '>=') ? 'OK' : 'PHP 7.1+ required',
];

foreach (['config', 'resources', 'storage'] as $dir) {
    $path = FERNICO_PATH . '/' . $dir;
    $writable = is_writable($path);
    $checks[] = [
        'label'   => 'Writable: <code>/' . $dir . '/</code>',
        'value'   => '',
        'state'   => $writable ? 'ok' : 'err',
        'message' => $writable ? 'Writable' : 'Insufficient permission',
    ];
    if (!$writable) {
        $status = false;
    }
}

foreach (['mb_strtolower' => 'mbstring', 'curl_exec' => 'cURL', 'mysqli_stmt_get_result' => 'mysqlnd', 'random_bytes' => 'random_bytes', 'password_hash' => 'password_hash'] as $fn => $label) {
    $present = function_exists($fn);
    $checks[] = [
        'label'   => $label,
        'value'   => '',
        'state'   => $present ? 'ok' : 'err',
        'message' => $present ? 'Installed' : 'Not installed',
    ];
    if (!$present) {
        $status = false;
    }
}

if (!$status) {
    installer_header('Installation requirements');
    echo '<h1>Installation requirements</h1>';
    echo '<p>Please address the items marked in red, then refresh this page.</p>';
    echo '<div class="check-grid">';
    foreach ($checks as $c) {
        echo '<div class="check-row"><span>' . $c['label']
           . ($c['value'] ? ' &mdash; ' . htmlspecialchars($c['value']) : '')
           . '</span><span class="' . $c['state'] . '">' . $c['message'] . '</span></div>';
    }
    echo '</div>';
    installer_footer();
    exit;
}

// ------------------------------ Form submit ---------------------------
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $domain        = installer_clean_input($_POST['domain'] ?? '');
    $contactEmail  = installer_clean_input($_POST['contact_email'] ?? '');
    $dbHost        = installer_clean_input($_POST['db_host'] ?? '');
    $dbUser        = installer_clean_input($_POST['db_user'] ?? '');
    $dbPass        = (string) ($_POST['db_pass'] ?? '');
    $dbName        = installer_clean_input($_POST['db_name'] ?? '');
    $userName      = installer_clean_input($_POST['user_name'] ?? '');
    $password      = (string) ($_POST['password'] ?? '');

    if (!installer_is_valid_domain($domain)) {
        $error = 'The website domain is not valid.';
    } elseif (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'The contact email is not a valid email.';
    } elseif ($dbHost === '' || $dbUser === '' || $dbName === '') {
        $error = 'All database fields are required.';
    } elseif ($userName === '' || strlen($userName) > 16
              || preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $userName)) {
        $error = 'The admin username is missing or contains invalid characters.';
    } elseif (strlen($password) < 6) {
        $error = 'The admin password must be at least 6 characters.';
    } else {
        $con = @new mysqli($dbHost, $dbUser, $dbPass);
        if ($con->connect_errno) {
            $error = 'Database connection failed: ' . htmlspecialchars($con->connect_error);
        } else {
            $createSql = 'CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', $dbName)
                       . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
            $con->query($createSql);
            $con->select_db($dbName);
            $con->set_charset('utf8mb4');

            // Run the schema as a single multi-statement block so we
            // don't have to parse semicolons by hand.
            $sql = file_get_contents(__DIR__ . '/schema.sql');
            if ($con->multi_query($sql)) {
                while ($con->more_results() && $con->next_result()) {
                    if ($result = $con->store_result()) {
                        $result->free();
                    }
                }
            }

            // Seed the admin row.
            $passwordHash = installer_password_hash($password);
            $stmt = $con->prepare('INSERT INTO admin_details (user_name, password) VALUES (?, ?)');
            $stmt->bind_param('ss', $userName, $passwordHash);
            $stmt->execute();
            $stmt->close();

            // Note: the original installer used `WHERE name = ?`, but
            // the column is actually `parameter`. Fixed here.
            $noReplyEmail = 'no-reply@' . $domain;
            $stmt = $con->prepare("UPDATE config SET value = ? WHERE parameter = 'no_reply_email_address'");
            $stmt->bind_param('s', $noReplyEmail);
            $stmt->execute();
            $stmt->close();
            $stmt = $con->prepare("UPDATE config SET value = ? WHERE parameter = 'contact_email_address'");
            $stmt->bind_param('s', $contactEmail);
            $stmt->execute();
            $stmt->close();

            // Build the config.php file from the example, swapping in
            // the live values.
            $cookieDomain = '.' . $domain;
            $cookieSecret = bin2hex(random_bytes(32));

            $config = <<<PHP
<?php
/**
 * Solnew - generated configuration. Do not check this file into source
 * control. Edit values manually if needed; do not delete this file or
 * the installer will run again on next request.
 */

if (!defined('FERNICO')) {
    http_response_code(403);
    exit('Forbidden');
}

define('SCRIPT_INSTALLED', true);

\$fernico_db_settings = [
    'DATABASE_HOST'     => '{$dbHost}',
    'DATABASE_NAME'     => '{$dbName}',
    'DATABASE_USER'     => '{$dbUser}',
    'DATABASE_PASSWORD' => '{$dbPass}',
];

\$fernico_misc_settings = [
    'COOKIE_DOMAIN' => '{$cookieDomain}',
    'COOKIE_SECRET' => '{$cookieSecret}',
    'WEBSITE_URL'   => '',
];

\$fernico_core_settings = [
    'CONNECT_TO_DATABASE'      => true,
    'DEFAULT_CONTROLLER'       => 'homeIndex',
    'DEFAULT_ACTION'           => 'home',
    'ERROR_REPORTING'          => true,
    'ERROR_LOG_DATABASE'       => false,
    'TEMPLATE_DIR'             => 'Nova',
    'TEMPLATE_COMPILED_DIR'    => FERNICO_PATH . '/storage/cache/templates_c',
    'TEMPLATE_FORCE_COMPILE'   => false,
    'SESSION_NAME'             => 'solnew_session',
    'SECURE'                   => false,
    'HTTP_ONLY'                => true,
    'SESSION_DAYS'             => 30,
    'CONFIRMATION_CONTROLLER'  => 'account',
    'CONFIRMATION_ACTION'      => 'confirm_account',
    'RESET_PASSWORD_CONTROLLER'=> 'account',
    'RESET_PASSWORD_ACTION'    => 'confirm_password_change',
    'CHANGE_EMAIL_CONTROLLER'  => 'account',
    'CHANGE_EMAIL_ACTION'      => 'confirm_email_change',
];

\$ignore = [
    'GLOBALS', '_FILES', '_COOKIE', '_POST', '_GET', '_SERVER',
    '_ENV', 'ignore', 'php_errormsg', 'HTTP_RAW_POST_DATA',
    'http_response_header', 'argc', 'argv',
];
\$all = array_diff_key(get_defined_vars() + array_flip(\$ignore), array_flip(\$ignore));
\$global_fernico_settings = [];
foreach (\$all as \$k => \$v) {
    if (substr(\$k, 0, 8) === 'fernico_' && substr(\$k, -9) === '_settings') {
        \$global_fernico_settings = array_merge(\$global_fernico_settings, \$v);
    }
}
PHP;

            file_put_contents(FERNICO_PATH . '/config/config.php', $config);

            // Try to remove the installer so it cannot run twice. The
            // case mismatch in the original (-> 'installer') has been
            // fixed.
            installer_rrmdir(FERNICO_PATH . '/resources/Installer');

            $success = true;
        }
    }
}

// ------------------------------ Render --------------------------------
installer_header('Installer');
echo '<h1>Install Solnew</h1>';

if (isset($success) && $success === true) {
    echo '<div class="alert alert-success">';
    echo '<strong>Success!</strong> Solnew is installed. Redirecting to the admin login in 5 seconds&hellip;';
    echo '</div>';
    echo '<p>If you are not redirected, <a href="' . htmlspecialchars($siteUrl) . 'admin/login">click here</a>.</p>';
    echo '<script>setTimeout(function(){window.location="' . htmlspecialchars($siteUrl) . 'admin/login";},5000);</script>';
    installer_footer();
    exit;
}

echo '<p>Fill in the details below to set up your faucet. The installer will create the database tables and your admin account.</p>';

if ($error !== '') {
    echo '<div class="alert alert-danger">' . $error . '</div>';
}

$default = function ($key, $fallback = '') {
    return isset($_POST[$key]) ? htmlspecialchars($_POST[$key], ENT_QUOTES) : $fallback;
};

echo '<form method="post" action="">';
echo '<h2>Site</h2>';
echo '<div class="grid-2">';
echo '<div class="form-row"><label for="domain">Website domain</label><input id="domain" type="text" name="domain" value="' . $default('domain', htmlspecialchars((string) ($_SERVER['HTTP_HOST'] ?? ''), ENT_QUOTES)) . '" required></div>';
echo '<div class="form-row"><label for="contact_email">Contact email</label><input id="contact_email" type="email" name="contact_email" value="' . $default('contact_email') . '" required></div>';
echo '</div>';

echo '<h2>Database</h2>';
echo '<div class="grid-2">';
echo '<div class="form-row"><label for="db_host">Host</label><input id="db_host" type="text" name="db_host" value="' . $default('db_host', 'localhost') . '" required></div>';
echo '<div class="form-row"><label for="db_name">Name</label><input id="db_name" type="text" name="db_name" value="' . $default('db_name') . '" required></div>';
echo '<div class="form-row"><label for="db_user">User</label><input id="db_user" type="text" name="db_user" value="' . $default('db_user') . '" required></div>';
echo '<div class="form-row"><label for="db_pass">Password</label><input id="db_pass" type="password" name="db_pass"></div>';
echo '</div>';

echo '<h2>Administrator account</h2>';
echo '<div class="grid-2">';
echo '<div class="form-row"><label for="user_name">Username</label><input id="user_name" type="text" name="user_name" value="' . $default('user_name') . '" required></div>';
echo '<div class="form-row"><label for="password">Password</label><input id="password" type="password" name="password" required></div>';
echo '</div>';

echo '<button type="submit" name="submit" value="1">Install</button>';
echo '</form>';

installer_footer();
