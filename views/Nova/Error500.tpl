{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Header.tpl"}

<section class="container container-narrow text-center" style="padding: 5rem 1rem;">
    <div style="font-size: 6rem; font-weight: 700; line-height: 1; letter-spacing: -0.04em; background: linear-gradient(180deg, var(--danger), var(--text-secondary)); -webkit-background-clip: text; background-clip: text; color: transparent;">500</div>
    <h1 class="mt-3 mb-2">Something went wrong</h1>
    <p class="text-secondary mb-4">{if isset($message) && $message != ''}{$message|escape:'html'}{else}An unexpected error occurred. Please try again.{/if}</p>
    <a href="{fernico_getAbsURL()}" class="btn btn-primary">Back to home</a>
</section>

{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Footer.tpl"}
