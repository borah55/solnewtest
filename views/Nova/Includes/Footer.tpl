{* Solnew - Nova theme: shared page footer. *}
</main>

<footer class="site-footer">
    <div class="container">
        <div>
            &copy; {date("Y")} <strong>{App::loadSiteVar('website_name')|escape:'html'}</strong>. All rights reserved.
        </div>
        <div class="text-muted">
            {App::footerText()} with care.
        </div>
    </div>
</footer>

<script src="{App::makeLink('js/nova.js', true)}" defer></script>
</body>
</html>
