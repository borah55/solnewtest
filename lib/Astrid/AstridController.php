<?php
/**
 * Base controller. All page controllers extend this class.
 *
 * Provides the renderTemplate() helper which wraps Smarty so individual
 * controllers don't have to deal with template path resolution.
 *
 * @package Fernico
 */

if (!defined('FERNICO')) {
    http_response_code(403);
    exit('Forbidden');
}

class AstridController extends AstridSession
{
    /**
     * Render a template through Smarty with the supplied options as
     * assigned variables.
     *
     * @param string $template Template path relative to the active theme.
     * @param array  $options  Variables to expose to the template.
     */
    public function renderTemplate($template, array $options = [])
    {
        $smarty = new Smarty();

        // Pop any one-shot flash message - it survives one redirect.
        $flash = isset($_SESSION['flash']) ? (string) $_SESSION['flash'] : '';
        unset($_SESSION['flash']);

        // Common variables that every page expects.
        $options = array_merge([
            'csrf_token' => fernico_generateAntiCSRFToken(),
            'site_url'   => fernico_getAbsURL(),
            'flash'      => $flash,
        ], $options);

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

        // Force compile only outside production - it is wasteful in prod.
        $smarty->force_compile = (bool) Config::fetch('TEMPLATE_FORCE_COMPILE');

        $smarty->display(
            FERNICO_PATH . '/views/' . Config::fetch('TEMPLATE_DIR') . '/' . $template
        );
    }
}
