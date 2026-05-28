{assign var="activeAdmin" value="settings"}
{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Header.tpl"}

<section class="container">
    <div class="admin-shell mt-3">
        {include file="{$smarty.const.FERNICO_PATH}/views/Nova/Admin/Includes/Sidebar.tpl"}

        <div>
            <h1 class="mb-2">Site settings</h1>
            <p class="text-secondary mb-3">All values map to rows in the <code>config</code> table.</p>

            {if isset($responseMessage) && $responseMessage != ''}
                <div class="alert alert-info">{$responseMessage|escape:'html'}</div>
            {/if}

            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="{$csrf_token|escape:'html'}">

                <div class="card mb-4">
                    <h3 class="mb-3">General</h3>
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label class="form-label">Website name</label>
                            <input name="website_name" class="form-control" value="{App::loadSiteVar('website_name')|escape:'html'}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Homepage title</label>
                            <input name="website_homepage_title" class="form-control" value="{App::loadSiteVar('website_homepage_title')|escape:'html'}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Contact email</label>
                            <input name="contact_email_address" class="form-control" value="{App::loadSiteVar('contact_email_address')|escape:'html'}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">No-reply email</label>
                            <input name="no_reply_email_address" class="form-control" value="{App::loadSiteVar('no_reply_email_address')|escape:'html'}">
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <h3 class="mb-3">Faucet</h3>
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label class="form-label">Currency (FaucetPay)</label>
                            <select name="coin_information" class="form-control">
                                <option value="{App::loadSiteVar('coin_abbreviation')|escape:'html'}-{App::loadSiteVar('coin_name')|escape:'html'}">
                                    Current: {App::loadSiteVar('coin_abbreviation')|escape:'html'} ({App::loadSiteVar('coin_name')|escape:'html'})
                                </option>
                                {foreach from=$coins item=name key=abbrev}
                                    <option value="{$abbrev|escape:'html'}-{$name|escape:'html'}">{$abbrev|escape:'html'} - {$name|escape:'html'}</option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Reward per claim</label>
                            <input name="faucet_reward" class="form-control" value="{App::loadSiteVar('faucet_reward')|escape:'html'}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cooldown (minutes)</label>
                            <input name="faucet_time_limit" type="number" class="form-control" value="{App::loadSiteVar('faucet_time_limit')|escape:'html'}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Referral percentage</label>
                            <input name="referral_percentage" type="number" class="form-control" value="{App::loadSiteVar('referral_percentage')|escape:'html'}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">FaucetPay API key</label>
                            <input name="faucetpay_api_key" class="form-control" value="{App::loadSiteVar('faucetpay_api_key')|escape:'html'}">
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <h3 class="mb-3">Captcha</h3>
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label class="form-label">Provider</label>
                            <select name="captcha_used" class="form-control">
                                <option value="0" {if App::loadSiteVar('captcha_used') == '0'}selected{/if}>Disabled</option>
                                <option value="1" {if App::loadSiteVar('captcha_used') == '1'}selected{/if}>Google reCAPTCHA</option>
                                <option value="3" {if App::loadSiteVar('captcha_used') == '3'}selected{/if}>hCaptcha</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Site key</label>
                            <input name="site_key" class="form-control" value="{App::loadSiteVar('site_key')|escape:'html'}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Secret key</label>
                            <input name="secret_key" class="form-control" value="{App::loadSiteVar('secret_key')|escape:'html'}">
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <h3 class="mb-3">Shortlink</h3>
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label class="form-label">Provider</label>
                            <select name="shortlink_preference" class="form-control">
                                <option value="0" {if App::loadSiteVar('shortlink_preference') == '0'}selected{/if}>Disabled</option>
                                <option value="1" {if App::loadSiteVar('shortlink_preference') == '1'}selected{/if}>ouo.io</option>
                                <option value="2" {if App::loadSiteVar('shortlink_preference') == '2'}selected{/if}>shorte.st</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">ouo.io API key</label>
                            <input name="ouo_api_key" class="form-control" value="{App::loadSiteVar('ouo_api_key')|escape:'html'}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">shorte.st API token</label>
                            <input name="shortest_api_token" class="form-control" value="{App::loadSiteVar('shortest_api_token')|escape:'html'}">
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <h3 class="mb-3">Email (SMTP)</h3>
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label class="form-label">Use SMTP</label>
                            <select name="use_smtp" class="form-control">
                                <option value="true" {if App::loadSiteVar('use_smtp') == 'true'}selected{/if}>Yes</option>
                                <option value="false" {if App::loadSiteVar('use_smtp') == 'false'}selected{/if}>No (use mail())</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email confirmation required</label>
                            <select name="email_confirmation" class="form-control">
                                <option value="true" {if App::loadSiteVar('email_confirmation') == 'true'}selected{/if}>Yes</option>
                                <option value="false" {if App::loadSiteVar('email_confirmation') == 'false'}selected{/if}>No</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">SMTP host</label>
                            <input name="email_smtp_host" class="form-control" value="{App::loadSiteVar('email_smtp_host')|escape:'html'}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">SMTP port</label>
                            <input name="email_smtp_port" type="number" class="form-control" value="{App::loadSiteVar('email_smtp_port')|escape:'html'}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">SMTP encryption</label>
                            <select name="email_smtp_encryption" class="form-control">
                                <option value="ssl" {if App::loadSiteVar('email_smtp_encryption') == 'ssl'}selected{/if}>SSL</option>
                                <option value="tls" {if App::loadSiteVar('email_smtp_encryption') == 'tls'}selected{/if}>TLS</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">SMTP auth</label>
                            <select name="smtp_auth" class="form-control">
                                <option value="true" {if App::loadSiteVar('smtp_auth') == 'true'}selected{/if}>Yes</option>
                                <option value="false" {if App::loadSiteVar('smtp_auth') == 'false'}selected{/if}>No</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">SMTP username</label>
                            <input name="email_smtp_username" class="form-control" value="{App::loadSiteVar('email_smtp_username')|escape:'html'}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">SMTP password</label>
                            <input name="email_smtp_password" type="password" class="form-control" value="{App::loadSiteVar('email_smtp_password')|escape:'html'}">
                        </div>
                    </div>
                </div>

                <button type="submit" name="update" value="1" class="btn btn-primary btn-lg">Save settings</button>
            </form>
        </div>
    </div>
</section>

{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Footer.tpl"}
