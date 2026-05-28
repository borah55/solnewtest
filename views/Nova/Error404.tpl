{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Header.tpl"}

<section class="container container-narrow text-center" style="padding: 5rem 1rem;">
    <div style="font-size: 6rem; font-weight: 700; line-height: 1; letter-spacing: -0.04em; background: linear-gradient(180deg, var(--accent), var(--text-secondary)); -webkit-background-clip: text; background-clip: text; color: transparent;">404</div>
    <h1 class="mt-3 mb-2">Page not found</h1>
    <p class="text-secondary mb-4">The page you were looking for doesn't exist or has moved.</p>
    <a href="{fernico_getAbsURL()}" class="btn btn-primary">Back to home</a>
</section>

{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Footer.tpl"}
