<?php
/**
 * Fernico kernel - request lifecycle.
 *
 * Wires the database connection, parses the URL into controller/action/
 * arguments, instantiates the matching controller and dispatches.
 *
 * @package Fernico
 */

if (!defined('FERNICO')) {
    http_response_code(403);
    exit('Forbidden');
}

// Configuration must exist before any framework code runs.
if (!file_exists(FERNICO_PATH . '/config/config.php')) {
    // Hand off to the installer if we are uninstalled.
    if (file_exists(FERNICO_PATH . '/resources/Installer/index.php')) {
        require FERNICO_PATH . '/resources/Installer/index.php';
        exit;
    }
    http_response_code(500);
    exit('Configuration is missing. Please run the installer.');
}

require_once FERNICO_PATH . '/config/config.php';
require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Core.php';
require_once __DIR__ . '/Request.php';
require_once __DIR__ . '/AstridSession.php';
require_once __DIR__ . '/AstridController.php';
require_once __DIR__ . '/DependenciesLoader.php';
require_once FERNICO_PATH . '/functions/Loader.php';

class fernico
{
    /** @var object|null Currently-dispatched controller instance. */
    private $controller;

    /** @var array<int,string> URL segments after controller/action. */
    private $parameters = [];

    /** @var string|null Controller name as it appeared in the URL. */
    private $controller_name;

    /** @var string|null Action name as it appeared in the URL. */
    private $action_name;

    /** @var string|null Controller class name (with hyphens converted). */
    private $controller_class;

    public function __construct()
    {
        $this->startDatabaseConnection();
        $this->parseURL($this->retrievePath());
        $this->defineControllerActions();
        $this->loadController();
    }

    /**
     * Open the global mysqli connection used by the rest of the app.
     *
     * Stored in the well-known $fernico_db global to preserve the API
     * the existing controllers rely on.
     */
    private function startDatabaseConnection()
    {
        global $fernico_db;

        // Strict error reporting from mysqli during development; in
        // production fernico_criticalErrorHandler swallows the output.
        mysqli_report(MYSQLI_REPORT_OFF);

        $fernico_db = @new mysqli(
            Config::fetch('DATABASE_HOST'),
            Config::fetch('DATABASE_USER'),
            Config::fetch('DATABASE_PASSWORD'),
            Config::fetch('DATABASE_NAME')
        );

        if ($fernico_db->connect_errno) {
            fernico_reportError('DB connect: ' . $fernico_db->connect_error);
            http_response_code(503);
            exit('Database connection failed.');
        }

        $fernico_db->set_charset('utf8mb4');
    }

    /**
     * Pull the route from the rewritten ?param= query string.
     */
    private function retrievePath()
    {
        return Request::GET('param');
    }

    /**
     * Split the path into controller / action / extra parameters.
     *
     * Example: "page/affiliate-programme/foo/bar" becomes
     *   controller_name = "page",
     *   action_name     = "affiliate-programme",
     *   parameters      = ["foo", "bar"].
     */
    private function parseURL($url)
    {
        $url = trim((string) $url, '/');
        $url = filter_var($url, FILTER_SANITIZE_URL);
        $segments = $url === '' ? [] : explode('/', $url);

        $this->controller_name = $segments[0] ?? null;
        $this->action_name = $segments[1] ?? null;

        // Anything after position 1 becomes an action parameter.
        $this->parameters = array_slice($segments, 2);
    }

    /**
     * Apply defaults and translate URL hyphens to PHP-friendly names.
     */
    private function defineControllerActions()
    {
        if (!$this->controller_name) {
            $this->controller_name = Config::fetch('DEFAULT_CONTROLLER');
        }

        if (!$this->action_name) {
            $this->action_name = Config::fetch('DEFAULT_ACTION');
        }

        // Hyphens are illegal in PHP identifiers, so we map them to
        // double-underscore. e.g. "reset-password" -> "reset__password".
        $this->action_name = str_replace('-', '__', $this->action_name);

        $this->controller_name = $this->controller_name . 'Controller';
        $this->controller_class = str_replace('-', '__', $this->controller_name);

        define('ACTION_NAME', $this->action_name);
        define('CONTROLLER_NAME', $this->controller_name);
    }

    /**
     * Locate, instantiate and dispatch to the resolved controller. If
     * either the file or the action cannot be found we fall through to
     * the 404 controller.
     */
    private function loadController()
    {
        $path = FERNICO_PATH . '/controllers/' . $this->controller_name . '.php';

        if (!file_exists($path)) {
            $this->dispatch404();
            return;
        }

        require_once $path;

        if (!class_exists($this->controller_class)) {
            $this->dispatch404();
            return;
        }

        $this->controller = new $this->controller_class();

        if (!method_exists($this->controller, $this->action_name)) {
            $this->dispatch404();
            return;
        }

        if (!empty($this->parameters)) {
            call_user_func_array(
                [$this->controller, $this->action_name],
                $this->parameters
            );
        } else {
            $this->controller->{$this->action_name}();
        }
    }

    /**
     * Render the 404 page through its dedicated controller.
     */
    private function dispatch404()
    {
        require_once FERNICO_PATH . '/controllers/Error404Controller.php';
        $controller = new Error404Controller();
        $controller->error404();
    }
}
