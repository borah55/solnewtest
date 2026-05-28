{assign var="activeAdmin" value="profile"}
{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Header.tpl"}

<section class="container">
    <div class="admin-shell mt-3">
        {include file="{$smarty.const.FERNICO_PATH}/views/Nova/Admin/Includes/Sidebar.tpl"}

        <div>
            <h1 class="mb-2">Your profile</h1>
            <p class="text-secondary mb-3">Update your administrator credentials.</p>

            {if isset($responseMessage) && $responseMessage != ''}
                <div class="alert alert-info">{$responseMessage|escape:'html'}</div>
            {/if}

            <div class="card mb-4">
                <h3 class="mb-2">Change username</h3>
                <form method="post" action="">
                    <input type="hidden" name="csrf_token" value="{$csrf_token|escape:'html'}">
                    <div class="form-group">
                        <label class="form-label" for="new_user_name">New username</label>
                        <input id="new_user_name" name="new_user_name" type="text" class="form-control" minlength="3" maxlength="16" required>
                    </div>
                    <button type="submit" name="update_user_name" value="1" class="btn btn-primary">Update username</button>
                </form>
            </div>

            <div class="card">
                <h3 class="mb-2">Change password</h3>
                <form method="post" action="">
                    <input type="hidden" name="csrf_token" value="{$csrf_token|escape:'html'}">
                    <div class="form-group">
                        <label class="form-label" for="current_password">Current password</label>
                        <input id="current_password" name="current_password" type="password" class="form-control" autocomplete="current-password" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="new_password">New password</label>
                        <input id="new_password" name="new_password" type="password" class="form-control" minlength="6" maxlength="64" autocomplete="new-password" required>
                    </div>
                    <button type="submit" name="update_password" value="1" class="btn btn-primary">Update password</button>
                </form>
            </div>
        </div>
    </div>
</section>

{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Footer.tpl"}
