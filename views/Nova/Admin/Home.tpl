{assign var="activeAdmin" value="home"}
{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Header.tpl"}

<section class="container">
    <div class="admin-shell mt-3">
        {include file="{$smarty.const.FERNICO_PATH}/views/Nova/Admin/Includes/Sidebar.tpl"}

        <div>
            <h1 class="mb-2">Dashboard</h1>
            <p class="text-secondary mb-3">Find a user and edit, ban, or delete their account.</p>

            {if isset($responseMessage) && $responseMessage != ''}
                <div class="alert alert-info">{$responseMessage}</div>
            {/if}

            <div class="card mb-4">
                <h3 class="mb-2">Find a user</h3>
                <p class="text-secondary mb-3">Search by user ID, username, or email address.</p>
                <form method="post" action="" class="flex gap-3" style="flex-wrap: wrap;">
                    <input type="hidden" name="csrf_token" value="{$csrf_token|escape:'html'}">
                    <input type="text" name="user" class="form-control" placeholder="user@example.com or username or 12345" style="flex: 1; min-width: 220px;" required>
                    <button type="submit" name="edit_user" value="1" class="btn btn-primary">Edit</button>
                    <button type="submit" name="ban_unban_user" value="1" class="btn btn-secondary">Ban / Unban</button>
                    <button type="submit" name="delete_user" value="1" class="btn btn-danger" onclick="return confirm('Delete this user permanently?')">Delete</button>
                </form>
            </div>

            {if isset($showEditSection) && $showEditSection == true}
                <div class="card">
                    <h3 class="mb-3">Edit user #{$editData.user_id|escape:'html'}</h3>
                    <form method="post" action="">
                        <input type="hidden" name="csrf_token" value="{$csrf_token|escape:'html'}">
                        <input type="hidden" name="user_id" value="{$editData.user_id|escape:'html'}">

                        <div class="grid grid-2">
                            <div class="form-group">
                                <label class="form-label">Username</label>
                                <input type="text" name="user_name" class="form-control" value="{$editData.user_name|escape:'html'}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="user_email" class="form-control" value="{$editData.user_email|escape:'html'}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Verified</label>
                                <input type="number" name="user_verified" class="form-control" value="{$editData.user_verified|escape:'html'}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Claims made</label>
                                <input type="number" name="claims_made" class="form-control" value="{$editData.claims_made|escape:'html'}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Referred income</label>
                                <input type="text" name="referred_income" class="form-control" value="{$editData.referred_income|escape:'html'}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Referral income</label>
                                <input type="text" name="referral_income" class="form-control" value="{$editData.referral_income|escape:'html'}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Referral (user id)</label>
                                <input type="number" name="referral" class="form-control" value="{$editData.referral|escape:'html'}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Payout address</label>
                                <input type="text" name="address" class="form-control" value="{$editData.address|escape:'html'}">
                            </div>
                        </div>

                        <button type="submit" name="update_user" value="1" class="btn btn-primary">Save changes</button>
                    </form>
                </div>
            {/if}
        </div>
    </div>
</section>

{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Footer.tpl"}
