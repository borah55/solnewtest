{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Header.tpl"}

<section class="hero">
    <div class="container">
        <h1>Earn {App::loadSiteVar('coin_abbreviation')|escape:'html'} every {App::loadSiteVar('faucet_time_limit')|escape:'html'} minutes</h1>
        <p class="lead">
            {App::loadSiteVar('website_name')|escape:'html'} is a high-paying {App::loadSiteVar('coin_name')|escape:'html'} faucet.
            Solve a captcha, click through a shortlink, and your reward lands directly in your FaucetPay wallet.
        </p>

        {if !App::userLoggedIn()}
            <div class="hero-cta">
                <a href="{App::makeLink('account/register')}" class="btn btn-primary btn-lg">Create your account</a>
                <a href="{App::makeLink('account/login')}" class="btn btn-ghost btn-lg">Sign in</a>
            </div>
        {else}
            <div class="hero-cta">
                <a href="{App::makeLink('page/dashboard')}" class="btn btn-primary btn-lg">Go to faucet</a>
            </div>
        {/if}
    </div>
</section>

<section class="container section">
    <div class="grid grid-3">
        <div class="card">
            <h3>1. Create an account</h3>
            <p class="text-secondary">
                Sign up in seconds with just a username, email and password. Add your FaucetPay address so we know where to send your rewards.
            </p>
        </div>
        <div class="card">
            <h3>2. Solve a captcha</h3>
            <p class="text-secondary">
                A quick captcha keeps the faucet pool safe from bots. It takes a couple of seconds and means real users get paid more.
            </p>
        </div>
        <div class="card">
            <h3>3. Get paid instantly</h3>
            <p class="text-secondary">
                Once you click through the shortlink your {App::loadSiteVar('coin_abbreviation')|escape:'html'} arrives in your FaucetPay wallet, no waiting.
            </p>
        </div>
    </div>
</section>

<section class="container section">
    <div class="grid grid-3">
        <div class="stat">
            <div class="stat-label">Registered users</div>
            <div class="stat-value">{number_format((int) App::loadSiteVar('stats_Total_Users'))}</div>
        </div>
        <div class="stat">
            <div class="stat-label">Claims made</div>
            <div class="stat-value">{number_format((int) App::loadSiteVar('stats_Claims_Made'))}</div>
        </div>
        <div class="stat">
            <div class="stat-label">Total paid out</div>
            <div class="stat-value">{number_format((float) App::loadSiteVar('stats_Amount_Claimed'), 8)}</div>
            <div class="stat-trend">{App::loadSiteVar('coin_abbreviation')|escape:'html'}</div>
        </div>
    </div>
</section>

<section class="container section">
    <div class="flex justify-between items-center mb-3">
        <h2 class="mb-0">Latest claims</h2>
        <span class="badge">Live</span>
    </div>

    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Amount</th>
                        <th>When</th>
                    </tr>
                </thead>
                <tbody>
                {foreach from=$claims_registered item=claim}
                    <tr>
                        <td>{$claim.id|escape:'html'}</td>
                        <td>{$claim.user_name|escape:'html'}</td>
                        <td><strong>{$claim.amount_credited|escape:'html'}</strong> <span class="text-muted">{App::loadSiteVar('coin_abbreviation')|escape:'html'}</span></td>
                        <td class="text-muted">{App::beautifyTime($claim.time)|escape:'html'}</td>
                    </tr>
                {foreachelse}
                    <tr>
                        <td colspan="4" class="text-center text-muted">No claims yet. Be the first!</td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</section>

{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Footer.tpl"}
