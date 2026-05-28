{assign var="activeAdmin" value="users"}
{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Header.tpl"}

<section class="container">
    <div class="admin-shell mt-3">
        {include file="{$smarty.const.FERNICO_PATH}/views/Nova/Admin/Includes/Sidebar.tpl"}

        <div>
            <h1 class="mb-2">Users</h1>
            <p class="text-secondary mb-3">All registered users, newest first.</p>

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
                                <th>Status</th>
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
                                <td>
                                    {if $u.account_status == 1}
                                        <span class="badge badge-success">Active</span>
                                    {else}
                                        <span class="badge badge-danger">Banned</span>
                                    {/if}
                                </td>
                                <td>
                                    <a class="btn btn-ghost" style="padding: 0.375rem 0.625rem; font-size: 0.8125rem;" href="?edit_user={$u.user_id|escape:'url'}">Edit</a>
                                </td>
                            </tr>
                        {foreachelse}
                            <tr><td colspan="7" class="text-center text-muted">No users yet.</td></tr>
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
