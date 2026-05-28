<?php
/**
 * Home page controller.
 *
 * Lists the latest 15 successful claims and powers the marketing page.
 *
 * @package Solnew
 */

if (!defined('FERNICO')) {
    http_response_code(403);
    exit('Forbidden');
}

class homeIndexController extends AstridController
{
    /** @var Authentication */
    public $auth;

    public function __construct()
    {
        require_once FERNICO_PATH . '/models/Bootstrapper.php';
        parent::__construct();
        $this->auth = new Authentication();
    }

    public function home()
    {
        global $fernico_db;

        $claims = $fernico_db->query(
            'SELECT id, user_name, amount_credited, time
               FROM claims_registered
              ORDER BY id DESC
              LIMIT 15'
        );

        $this->renderTemplate('Home.tpl', [
            'pageName'          => App::loadSiteVar('website_homepage_title') ?: 'Home',
            'claims_registered' => $claims,
        ]);
    }
}
