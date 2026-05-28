<?php
/**
 * Solnew - Modern PHP Faucet Application
 *
 * Front controller / entry point. All HTTP traffic is routed to this file
 * via the .htaccess rewrite rule. Defines the application root, loads
 * configuration, and hands off control to the Fernico kernel.
 *
 * @package   Solnew
 * @license   MIT
 */

// Application root path. All other paths derive from this.
define('FERNICO_PATH', __DIR__);

// Marker constant used by every framework file to detect direct access.
define('FERNICO', true);

// Bootstrap the framework.
require __DIR__ . '/lib/Astrid/init.php';

// Off we go.
new fernico();
