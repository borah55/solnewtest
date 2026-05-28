{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Header.tpl"}

<section class="container">
    <div class="flex justify-between items-center mt-3 mb-3" style="flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 class="mb-0">Faucet</h1>
            <p class="text-secondary mb-0">Welcome back, {$smarty.session.user_name|escape:'html'}.</p>
        </div>
        <span class="badge badge-success">Signed in</span>
    </div>

    <div class="grid" style="grid-template-columns: 1fr; gap: 2rem;">
        <div>
            {if isset($responseMessage) && $responseMessage != ''}
                <div class="alert alert-info">{$responseMessage|escape:'html'}</div>
            {/if}

            <div class="card card-elev">
                <div class="text-center">
                    <div class="text-secondary" style="text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Reward per claim</div>
                    <div style="font-size: 3rem; font-weight: 700; line-height: 1; letter-spacing: -0.02em; margin-bottom: 0.5rem;">
                        {$winAmt|escape:'html'}
                        <span style="font-size: 1.25rem; color: var(--text-secondary); font-weight: 400;">{App::loadSiteVar('coin_abbreviation')|escape:'html'}</span>
                    </div>
                    <p class="text-secondary mb-3">Available every {App::loadSiteVar('faucet_time_limit')|escape:'html'} minutes.</p>
                </div>

                <form method="post" action="" class="mt-3">
                    <input type="hidden" name="csrf_token" value="{$csrf_token|escape:'html'}">

                    {if isset($captchaCode) && $captchaCode != ''}
                        <div class="form-group" style="display: flex; justify-content: center;">
                            {$captchaCode}
                        </div>
                    {/if}

                    <button type="submit" name="claim" value="1" class="btn btn-primary btn-block btn-lg" data-claim-button>
                        Claim my reward
                    </button>
                </form>
            </div>
        </div>

        <div>
            <h3 class="mb-3">Your recent claims</h3>
            <div class="card" style="padding: 0;">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Amount</th>
                                <th>When</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$claims_registered item=claim}
                                <tr>
                                    <td>{$claim.id|escape:'html'}</td>
                                    <td><strong>{$claim.amount_credited|escape:'html'}</strong> <span class="text-muted">{App::loadSiteVar('coin_abbreviation')|escape:'html'}</span></td>
                                    <td class="text-muted">{App::beautifyTime($claim.time)|escape:'html'}</td>
                                </tr>
                            {foreachelse}
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No claims yet. Make your first claim above.</td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Footer.tpl"}
