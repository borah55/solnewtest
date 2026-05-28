<?php
/**
 * Astrid Framework Bootstrapper.
 *
 * Loads core framework components in the right order. Called once from
 * the front controller.
 *
 * @package Fernico
 */

if (!defined('FERNICO')) {
    http_response_code(403);
    exit('Forbidden');
}

// Load the kernel which itself pulls in Config, Core, Request,
// AstridSession, AstridController and the user-defined plugin loader.
require_once __DIR__ . '/App.php';
