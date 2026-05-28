{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Header.tpl"}

<section class="container container-narrow">
    <div class="card card-elev mt-4">
        <h1 class="mb-2">Sign in</h1>
        <p class="text-secondary mb-3">Welcome back. Enter your credentials to continue.</p>

        {if isset($responseMessage) && $responseMessage != ''}
            <div class="alert alert-info">{$responseMessage|escape:'html'}</div>
        {/if}

        <form method="post" action="" novalidate>
            <input type="hidden" name="csrf_token" value="{$csrf_token|escape:'html'}">

            <div class="form-group">
                <label class="form-label" for="user_name">Username</label>
                <input id="user_name" name="user_name" type="text" class="form-control" autocomplete="username" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input id="password" name="password" type="password" class="form-control" autocomplete="current-password" required>
            </div>

            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="remember_me" value="1"> Stay signed in on this device
                </label>
            </div>

            {if isset($captchaCode)}
                <div class="form-group">{$captchaCode}</div>
            {/if}

            <button type="submit" name="login" value="1" class="btn btn-primary btn-block btn-lg">Sign in</button>
        </form>

        <div class="divider"></div>

        <div class="flex justify-between text-secondary" style="font-size: 0.9375rem;">
            <a href="{App::makeLink('account/reset-password')}">Forgot password?</a>
            <a href="{App::makeLink('account/resend-email')}">Resend activation email</a>
        </div>

        <p class="mt-3 mb-0 text-center text-secondary">
            Don't have an account? <a href="{App::makeLink('account/register')}">Create one</a>
        </p>
    </div>
</section>

{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Footer.tpl"}
