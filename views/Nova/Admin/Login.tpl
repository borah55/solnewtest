{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Header.tpl"}

<section class="container container-narrow">
    <div class="card card-elev mt-4">
        <h1 class="mb-2">Administrator sign in</h1>
        <p class="text-secondary mb-3">This area is restricted.</p>

        {if isset($responseMessage) && $responseMessage != ''}
            <div class="alert alert-danger">{$responseMessage|escape:'html'}</div>
        {/if}

        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="{$csrf_token|escape:'html'}">

            <div class="form-group">
                <label class="form-label" for="user_name">Username</label>
                <input id="user_name" name="user_name" type="text" class="form-control" autocomplete="username" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input id="password" name="password" type="password" class="form-control" autocomplete="current-password" required>
            </div>

            <button type="submit" name="login" value="1" class="btn btn-primary btn-block">Sign in</button>
        </form>
    </div>
</section>

{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Footer.tpl"}
