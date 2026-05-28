<?php
/**
 * 404 handler.
 *
 * @package Solnew
 */

if (!defined('FERNICO')) {
    http_response_code(403);
    exit('Forbidden');
}

class Error404Controller extends AstridController
{
    /** @var Authentication */
    public $auth;

    public function __construct()
    {
        require_once FERNICO_PATH . '/models/Bootstrapper.php';
        parent::__construct();
        $this->auth = new Authentication();
    }

    public function error404()
    {
        http_response_code(404);
        fernico_loadComponent(
            Config::fetch('TEMPLATE_DIR'),
            'Error404.tpl',
            ['pageName' => 'Page Not Found']
        );
    }
}
