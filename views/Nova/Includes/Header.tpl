{* Solnew - Nova theme: shared page chrome (head + nav). *}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="dark light">
    <title>{$pageName|escape:'html'} | {App::loadSiteVar('website_name')|escape:'html'}</title>
    <meta name="description" content="{App::loadSiteVar('website_name')|escape:'html'} - earn cryptocurrency rewards. Claim {App::loadSiteVar('coin_abbreviation')|escape:'html'} every {App::loadSiteVar('faucet_time_limit')|escape:'html'} minutes.">
    <link rel="stylesheet" href="{App::makeLink('css/nova.css', true)}">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0' x2='1' y1='0' y2='1'%3E%3Cstop offset='0' stop-color='%2338bdf8'/%3E%3Cstop offset='1' stop-color='%23a855f7'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect x='2' y='2' width='28' height='28' rx='8' fill='url(%23g)'/%3E%3C/svg%3E">
</head>
<body>
<a class="skip-link" href="#main">Skip to main content</a>

<header class="site-header">
    <div class="container">
        <a href="{fernico_getAbsURL()}" class="brand">
            <span class="brand-mark"></span>
            {App::loadSiteVar('website_name')|escape:'html'}
        </a>

        <button type="button" class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>

        <nav class="nav" aria-label="Primary">
            {if App::userLoggedIn()}
                <a href="{App::makeLink('page/dashboard')}">Faucet</a>
                <a href="{App::makeLink('page/affiliate-programme')}">Affiliate</a>
                <a href="{App::makeLink('account/settings')}">Account</a>
                <a href="{App::makeLink('page/contact')}">Contact</a>
                {if App::isAdmin()}
                    <a href="{App::makeLink('admin/home')}">Admin</a>
                {/if}
                <a href="{App::makeLink('account/logout')}">Sign out</a>
            {else}
                <a href="{App::makeLink('page/contact')}">Contact</a>
                <a href="{App::makeLink('account/login')}">Sign in</a>
                <a class="btn btn-primary" href="{App::makeLink('account/register')}">Sign up</a>
            {/if}

            <button type="button" class="theme-toggle" aria-label="Toggle theme">
                <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
                <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="5"/>
                    <line x1="12" y1="1" x2="12" y2="3"/>
                    <line x1="12" y1="21" x2="12" y2="23"/>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                    <line x1="1" y1="12" x2="3" y2="12"/>
                    <line x1="21" y1="12" x2="23" y2="12"/>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                </svg>
            </button>
        </nav>
    </div>
</header>

<main id="main">

{if !empty($flash)}
    <div class="container mt-3">
        <div class="alert alert-info" data-autodismiss>{$flash|escape:'html'}</div>
    </div>
{/if}
