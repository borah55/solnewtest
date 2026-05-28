{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Header.tpl"}

<section class="container container-narrow">
    <h1 class="mt-4 mb-2">Account settings</h1>
    <p class="text-secondary mb-4">Update your payout address, email, and password.</p>

    <div class="card mb-4">
        <h3 class="mb-2">{App::loadSiteVar('coin_abbreviation')|escape:'html'} payout address</h3>
        <p class="text-secondary mb-3">Rewards are sent to this address via FaucetPay.</p>

        {if isset($changeAddressDetailsMessage) && $changeAddressDetailsMessage != ''}
            <div class="alert alert-info">{$changeAddressDetailsMessage|escape:'html'}</div>
        {/if}

        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="{$csrf_token|escape:'html'}">
            <div class="form-group">
                <label class="form-label" for="address">Payout address</label>
                <input id="address" name="address" type="text" class="form-control" value="{$address|escape:'html'}" required>
            </div>
            <button type="submit" name="change_address_details" value="1" class="btn btn-primary">Update address</button>
        </form>
    </div>

    <div class="card mb-4">
        <h3 class="mb-2">Change email</h3>
        <p class="text-secondary mb-3">We'll email a confirmation link to your new address.</p>

        {if isset($changeEmailMessage) && $changeEmailMessage != ''}
            <div class="alert alert-info">{$changeEmailMessage|escape:'html'}</div>
        {/if}

        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="{$csrf_token|escape:'html'}">
            <div class="form-group">
                <label class="form-label" for="email">New email address</label>
                <input id="email" name="email" type="email" class="form-control" required>
            </div>
            <button type="submit" name="change_email_details" value="1" class="btn btn-primary">Send confirmation</button>
        </form>
    </div>

    <div class="card">
        <h3 class="mb-2">Change password</h3>
        <p class="text-secondary mb-3">Pick a strong password (at least 6 characters).</p>

        {if isset($changePasswordDetailsMessage) && $changePasswordDetailsMessage != ''}
            <div class="alert alert-info">{$changePasswordDetailsMessage|escape:'html'}</div>
        {/if}

        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="{$csrf_token|escape:'html'}">
            <div class="form-group">
                <label class="form-label" for="password">New password</label>
                <input id="password" name="password" type="password" class="form-control" minlength="6" maxlength="64" autocomplete="new-password" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="password_repeat">Confirm password</label>
                <input id="password_repeat" name="password_repeat" type="password" class="form-control" autocomplete="new-password" required>
            </div>
            <button type="submit" name="change_password_details" value="1" class="btn btn-primary">Change password</button>
        </form>
    </div>
</section>

{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Footer.tpl"}
