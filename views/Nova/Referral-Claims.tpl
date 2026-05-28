{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Header.tpl"}

<section class="container">
    <h1 class="mt-4 mb-2">Referral claims</h1>
    <p class="text-secondary mb-3">Each time a referred user successfully claims, you earn here.</p>

    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>From user</th>
                        <th>Amount</th>
                        <th>When</th>
                    </tr>
                </thead>
                <tbody>
                {foreach from=$items item=row}
                    <tr>
                        <td>{$row.user_name|escape:'html'}</td>
                        <td><strong>{$row.amount|escape:'html'}</strong> <span class="text-muted">{App::loadSiteVar('coin_abbreviation')|escape:'html'}</span></td>
                        <td class="text-muted">{App::beautifyTime($row.time)|escape:'html'}</td>
                    </tr>
                {foreachelse}
                    <tr><td colspan="3" class="text-center text-muted">No referral earnings yet.</td></tr>
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
