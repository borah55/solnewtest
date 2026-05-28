<?php
/**
 * Fatal-error handler.
 *
 * Rendered by the shutdown function in lib/Astrid/Core.php when an
 * unrecoverable error escapes a regular controller.
 *
 * @package Solnew
 */

if (!defined('FERNICO')) {
    http_response_code(403);
    exit('Forbidden');
}

class fatalErrorController extends AstridController
{
    /** @var Authentication */
    public $auth;

    public function __construct()
    {
        require_once FERNICO_PATH . '/models/Bootstrapper.php';
        parent::__construct();
        $this->auth = new Authentication();
    }

    public function errorHandler($err_text, $error_msg)
    {
        http_response_code(500);
        fernico_loadComponent(
            Config::fetch('TEMPLATE_DIR'),
            'Error500.tpl',
            [
                'message'       => $err_text,
                'error_message' => $error_msg,
                'pageName'      => 'Internal Error Occurred',
            ]
        );
    }
}
