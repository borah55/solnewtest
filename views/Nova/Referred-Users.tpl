{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Header.tpl"}

<section class="container">
    <h1 class="mt-4 mb-2">Referred users</h1>
    <p class="text-secondary mb-3">Everyone who signed up via your referral link.</p>

    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Joined</th>
                        <th>You've earned</th>
                    </tr>
                </thead>
                <tbody>
                {foreach from=$items item=row}
                    <tr>
                        <td>{$row.user_name|escape:'html'}</td>
                        <td class="text-muted">{$row.registration_datetime|escape:'html'}</td>
                        <td><strong>{$row.referred_income|escape:'html'}</strong> <span class="text-muted">{App::loadSiteVar('coin_abbreviation')|escape:'html'}</span></td>
                    </tr>
                {foreachelse}
                    <tr><td colspan="3" class="text-center text-muted">No referrals yet.</td></tr>
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
</section>

{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Footer.tpl"}
