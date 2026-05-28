{assign var="activeAdmin" value="banned"}
{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Header.tpl"}

<section class="container">
    <div class="admin-shell mt-3">
        {include file="{$smarty.const.FERNICO_PATH}/views/Nova/Admin/Includes/Sidebar.tpl"}

        <div>
            <h1 class="mb-2">Banned users</h1>
            <p class="text-secondary mb-3">Accounts that are currently suspended.</p>

            <div class="card" style="padding: 0;">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Joined</th>
                                <th>IP</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        {foreach from=$items item=u}
                            <tr>
                                <td>{$u.user_id|escape:'html'}</td>
                                <td>{$u.user_name|escape:'html'}</td>
                                <td>{$u.user_email|escape:'html'}</td>
                                <td class="text-muted">{$u.registration_datetime|escape:'html'}</td>
                                <td class="text-muted">{$u.registration_ip|escape:'html'}</td>
                                <td><a class="btn btn-ghost" style="padding: 0.375rem 0.625rem; font-size: 0.8125rem;" href="{App::makeLink('admin/home')}?ban_unban_user={$u.user_id|escape:'url'}">Unban</a></td>
                            </tr>
                        {foreachelse}
                            <tr><td colspan="6" class="text-center text-muted">No banned users.</td></tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>

            {if $total_pages > 1}
                <nav class="pagination" aria-label="Pagination">
                    {section name=p start=1 loop=$total_pages+1 step=1}
                        {if $smarty.section.p.index == $req_page}
                            <span class="active">{$smarty.section.p.index}</span>
                        {else}
                            <a href="?offset={$smarty.section.p.index}">{$smarty.section.p.index}</a>
                        {/if}
                    {/section}
                </nav>
            {/if}
        </div>
    </div>
</section>

{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Footer.tpl"}
