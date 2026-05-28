{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Header.tpl"}

<section class="container container-narrow">
    <div class="card card-elev mt-4">
        <h1 class="mb-2">Contact us</h1>
        <p class="text-secondary mb-3">Got a question or feedback? Drop us a line.</p>

        {if isset($responseMessage) && $responseMessage != ''}
            <div class="alert alert-info">{$responseMessage|escape:'html'}</div>
        {/if}

        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="{$csrf_token|escape:'html'}">
            <input type="hidden" name="contactForm" value="1">

            <div class="form-group">
                <label class="form-label" for="name">Your name</label>
                <input id="name" name="name" type="text" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="email_address">Email</label>
                <input id="email_address" name="email_address" type="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="message">Message</label>
                <textarea id="message" name="message" class="form-control" rows="6" minlength="10" required></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Send message</button>
        </form>
    </div>
</section>

{include file="{$smarty.const.FERNICO_PATH}/views/Nova/Includes/Footer.tpl"}
