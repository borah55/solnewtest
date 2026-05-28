{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Header.tpl"}

<section class="container container-narrow">
    <div class="card card-elev mt-4">
        <h1 class="mb-2">Choose a new password</h1>
        <p class="text-secondary mb-3">Pick something strong - at least 6 characters.</p>

        {if isset($responseMessage) && $responseMessage != ''}
            <div class="alert alert-info">{$responseMessage|escape:'html'}</div>
        {/if}

        <form method="post" action="" novalidate>
            <input type="hidden" name="csrf_token" value="{$csrf_token|escape:'html'}">

            <div class="form-group">
                <label class="form-label" for="password">New password</label>
                <input id="password" name="password" type="password" class="form-control" minlength="6" maxlength="64" autocomplete="new-password" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="password_repeat">Confirm new password</label>
                <input id="password_repeat" name="password_repeat" type="password" class="form-control" autocomplete="new-password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Save new password</button>
        </form>
    </div>
</section>

{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Footer.tpl"}
