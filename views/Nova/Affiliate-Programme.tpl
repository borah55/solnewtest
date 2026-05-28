{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Header.tpl"}

<section class="container">
    <h1 class="mt-4 mb-2">Affiliate programme</h1>
    <p class="text-secondary mb-4">
        Earn <strong>{App::loadSiteVar('referral_percentage')|escape:'html'}%</strong> on every claim made by users you refer.
        Share your link below; rewards are paid to your FaucetPay wallet automatically.
    </p>

    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
        <div class="stat">
            <div class="stat-label">Your earnings</div>
            <div class="stat-value">{$u.referral_income|escape:'html'}</div>
            <div class="stat-trend">{App::loadSiteVar('coin_abbreviation')|escape:'html'} earned via referrals</div>
        </div>

        <div class="card">
            <h3 class="mb-2">Your referral link</h3>
            <p class="text-secondary mb-3">Share this URL on social media, blogs, or directly with friends.</p>

            <div class="copy-box">
                <input id="referral-link" type="text" readonly value="{fernico_getAbsURL()}?r={$smarty.session.user_name|escape:'url'}">
                <button type="button" class="btn" data-copy="#referral-link">Copy</button>
            </div>

            <div class="flex gap-3 mt-3" style="flex-wrap: wrap;">
                <a class="btn btn-secondary" href="{App::makeLink('page/referred-users')}">Referred users</a>
                <a class="btn btn-secondary" href="{App::makeLink('page/referral-claims')}">Referral claims</a>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <h3 class="mb-2">How it works</h3>
        <ol class="text-secondary" style="padding-left: 1.25rem;">
            <li>Share your unique referral link.</li>
            <li>When someone signs up using your link, they're tagged as your referral.</li>
            <li>Every time they make a claim, you earn {App::loadSiteVar('referral_percentage')|escape:'html'}% on top of their reward, paid by us - not from their share.</li>
            <li>Earnings land in your FaucetPay wallet alongside your normal claims.</li>
        </ol>
    </div>
</section>

{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Footer.tpl"}
