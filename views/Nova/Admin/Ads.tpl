{assign var="activeAdmin" value="ads"}
{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Header.tpl"}

<section class="container">
    <div class="admin-shell mt-3">
        {include file="{$smarty.const.FERNICO_PATH}/views/Nova/Admin/Includes/Sidebar.tpl"}

        <div>
            <h1 class="mb-2">Ad slots</h1>
            <p class="text-secondary mb-3">Configure HTML banners shown around the site.</p>

            {if isset($responseMessage) && $responseMessage != ''}
                <div class="alert alert-info">{$responseMessage|escape:'html'}</div>
            {/if}

            <div class="card mb-4">
                <h3 class="mb-2">Add a new ad</h3>
                <form method="post" action="">
                    <input type="hidden" name="csrf_token" value="{$csrf_token|escape:'html'}">
                    <div class="form-group">
                        <label class="form-label">Slot</label>
                        <select name="size" class="form-control">
                            <option value="1">Sidebar (160 x 600)</option>
                            <option value="2">Top banner (728 x 90)</option>
                            <option value="3">Inline banner (468 x 60)</option>
                            <option value="4">Inline banner #2 (468 x 60)</option>
                            <option value="5">Square (300 x 250)</option>
                            <option value="6">Sidebar #2 (160 x 600)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ad HTML</label>
                        <textarea name="code" class="form-control" rows="6" required></textarea>
                    </div>
                    <button type="submit" name="submit" value="1" class="btn btn-primary">Add ad</button>
                </form>
            </div>

            <div class="card" style="padding: 0;">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Slot</th>
                                <th>Preview</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        {foreach from=$items item=ad}
                            <tr>
                                <td>{$ad.id|escape:'html'}</td>
                                <td>{$ad.type|escape:'html'}</td>
                                <td><code style="font-size: 0.8125rem;">{$ad.code|escape:'html'|truncate:80}</code></td>
                                <td>
                                    <a class="btn btn-danger" style="padding: 0.375rem 0.625rem; font-size: 0.8125rem;" href="?d={$ad.id|escape:'url'}" onclick="return confirm('Delete this ad?')">Delete</a>
                                </td>
                            </tr>
                        {foreachelse}
                            <tr><td colspan="4" class="text-center text-muted">No ads configured.</td></tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Footer.tpl"}
