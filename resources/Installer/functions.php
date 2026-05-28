<?php
/**
 * Helper functions used by the installer wizard.
 *
 * @package Solnew\Installer
 */

if (!defined('FERNICO')) {
    http_response_code(403);
    exit('Forbidden');
}

function installer_is_valid_domain($domain)
{
    return (bool) preg_match(
        '/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i',
        $domain
    ) && preg_match('/^.{1,253}$/', $domain)
      && preg_match('/^[^\.]{1,63}(\.[^\.]{1,63})*$/', $domain);
}

function installer_clean_input($input)
{
    if (!is_string($input)) {
        return $input;
    }
    $input = preg_replace('/[\x00-\x1F\x7F]/u', '', $input);
    return trim(strip_tags($input));
}

function installer_detect_url()
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $scheme = strtolower(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]);
    } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $scheme = 'https';
    } elseif (!empty($_SERVER['REQUEST_SCHEME'])) {
        $scheme = $_SERVER['REQUEST_SCHEME'];
    } else {
        $scheme = 'http';
    }
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/';
}

function installer_rrmdir($dir)
{
    if (!is_dir($dir)) {
        return;
    }
    foreach (scandir($dir) as $object) {
        if ($object === '.' || $object === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $object;
        if (is_dir($path)) {
            installer_rrmdir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

/**
 * Render the wizard's <head> + opening <body> + minimal styling.
 */
function installer_header($pageName)
{
    echo '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>' . htmlspecialchars($pageName) . ' | Solnew Installer</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
    :root { color-scheme: dark; }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        background: #0a0e1a;
        color: #f1f5f9;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        padding: 3rem 1.25rem;
    }
    .wrap {
        max-width: 720px;
        margin: 0 auto;
        background: #111827;
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 16px;
        padding: 2.5rem;
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }
    h1 { margin: 0 0 0.5rem; font-size: 1.75rem; letter-spacing: -0.02em; }
    h2 { margin: 1.5rem 0 0.75rem; font-size: 1.125rem; color: #94a3b8; font-weight: 600; }
    p { color: #94a3b8; margin: 0 0 1.5rem; line-height: 1.55; }
    .check-grid { display: grid; gap: 0.5rem; margin: 1.5rem 0; }
    .check-row {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 1rem;
        background: #1f2937;
        border-radius: 8px;
        font-size: 0.9375rem;
    }
    .ok { color: #10b981; font-weight: 600; }
    .warn { color: #f59e0b; font-weight: 600; }
    .err { color: #ef4444; font-weight: 600; }
    .form-row { margin-bottom: 1rem; }
    label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.375rem; color: #cbd5e1; }
    input[type="text"], input[type="password"], input[type="email"] {
        width: 100%;
        background: #1f2937;
        color: #f1f5f9;
        border: 1px solid rgba(255,255,255,0.12);
        border-radius: 6px;
        padding: 0.625rem 0.875rem;
        font-size: 0.9375rem;
        font-family: inherit;
    }
    input:focus { outline: none; border-color: #38bdf8; box-shadow: 0 0 0 3px rgba(56,189,248,0.15); }
    button {
        background: #38bdf8;
        color: #0a0e1a;
        border: none;
        border-radius: 6px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        font-size: 0.9375rem;
        cursor: pointer;
        margin-top: 0.5rem;
    }
    button:hover { background: #0ea5e9; }
    .alert {
        padding: 0.875rem 1.125rem;
        border-radius: 8px;
        font-size: 0.9375rem;
        margin-bottom: 1.5rem;
    }
    .alert-success { background: rgba(16,185,129,0.12); color: #10b981; border: 1px solid rgba(16,185,129,0.3); }
    .alert-danger  { background: rgba(239,68,68,0.12);  color: #ef4444; border: 1px solid rgba(239,68,68,0.3); }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    @media (max-width: 540px) { .grid-2 { grid-template-columns: 1fr; } }
    code { background: #1f2937; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.875rem; }
</style>
</head>
<body>
<div class="wrap">';
}

function installer_footer()
{
    echo '</div></body></html>';
}

/**
 * Modern password hashing for admin accounts. The framework's
 * App::adminPasswordVerify() understands these hashes.
 */
function installer_password_hash($input)
{
    return password_hash($input, PASSWORD_DEFAULT, ['cost' => 12]);
}
