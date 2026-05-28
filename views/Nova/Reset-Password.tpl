{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Header.tpl"}

<section class="container container-narrow">
    <div class="card card-elev mt-4">
        <h1 class="mb-2">Reset your password</h1>
        <p class="text-secondary mb-3">Enter your username and we'll email you a reset link.</p>

        {if isset($responseMessage) && $responseMessage != ''}
            <div class="alert alert-info">{$responseMessage|escape:'html'}</div>
        {/if}

        <form method="post" action="" novalidate>
            <input type="hidden" name="csrf_token" value="{$csrf_token|escape:'html'}">

            <div class="form-group">
                <label class="form-label" for="user_name">Username</label>
                <input id="user_name" name="user_name" type="text" class="form-control" required>
            </div>

            {if isset($captchaCode)}
                <div class="form-group">{$captchaCode}</div>
            {/if}

            <button type="submit" name="reset_password" value="1" class="btn btn-primary btn-block">Send reset link</button>
        </form>

        <p class="mt-3 mb-0 text-center text-secondary">
            <a href="{App::makeLink('account/login')}">Back to sign in</a>
        </p>
    </div>
</section>

{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Footer.tpl"}
