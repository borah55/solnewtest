{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Header.tpl"}

<section class="container container-narrow">
    <div class="card card-elev mt-4">
        <h1 class="mb-2">Create an account</h1>
        <p class="text-secondary mb-3">Sign up to start claiming {App::loadSiteVar('coin_abbreviation')|escape:'html'} rewards.</p>

        {if isset($responseMessage) && $responseMessage != ''}
            <div class="alert alert-info">{$responseMessage|escape:'html'}</div>
        {/if}

        <form method="post" action="" novalidate>
            <input type="hidden" name="csrf_token" value="{$csrf_token|escape:'html'}">

            <div class="form-group">
                <label class="form-label" for="user_name">Username</label>
                <input id="user_name" name="user_name" type="text" class="form-control" minlength="3" maxlength="16" autocomplete="username" required>
                <div class="form-help">3 to 16 characters, no special symbols.</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email address</label>
                <input id="email" name="email" type="email" class="form-control" autocomplete="email" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="email_repeat">Confirm email address</label>
                <input id="email_repeat" name="email_repeat" type="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="address">Your FaucetPay {App::loadSiteVar('coin_abbreviation')|escape:'html'} address</label>
                <input id="address" name="address" type="text" class="form-control" autocomplete="off" required>
                <div class="form-help">Rewards will be sent to this address.</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input id="password" name="password" type="password" class="form-control" minlength="6" maxlength="64" autocomplete="new-password" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="password_repeat">Confirm password</label>
                <input id="password_repeat" name="password_repeat" type="password" class="form-control" autocomplete="new-password" required>
            </div>

            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="tos_agree" value="1" required>
                    I agree to the terms of service and privacy policy.
                </label>
            </div>

            {if isset($captchaCode)}
                <div class="form-group">{$captchaCode}</div>
            {/if}

            <button type="submit" name="register" value="1" class="btn btn-primary btn-block btn-lg">Create account</button>
        </form>

        <p class="mt-3 mb-0 text-center text-secondary">
            Already have an account? <a href="{App::makeLink('account/login')}">Sign in</a>
        </p>
    </div>
</section>

{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Footer.tpl"}
