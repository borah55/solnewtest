/* Solnew - Nova theme JS
 *
 * Lightweight, dependency-free. Drives:
 *   - mobile nav toggle
 *   - light/dark theme switcher (persisted in localStorage)
 *   - copy-to-clipboard buttons
 *   - faucet cooldown countdown
 *   - flash auto-dismiss
 */
(function () {
    "use strict";

    var $  = function (s, ctx) { return (ctx || document).querySelector(s); };
    var $$ = function (s, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(s)); };

    // -------------------- Theme switcher --------------------
    function applyTheme(theme) {
        if (theme === "light") {
            document.documentElement.setAttribute("data-theme", "light");
        } else {
            document.documentElement.removeAttribute("data-theme");
        }
    }

    function initTheme() {
        var stored = null;
        try { stored = localStorage.getItem("nova-theme"); } catch (e) {}

        if (stored === "light" || stored === "dark") {
            applyTheme(stored);
        } else if (window.matchMedia && window.matchMedia("(prefers-color-scheme: light)").matches) {
            applyTheme("light");
        }

        var btn = $(".theme-toggle");
        if (!btn) return;

        btn.addEventListener("click", function () {
            var next = document.documentElement.getAttribute("data-theme") === "light" ? "dark" : "light";
            applyTheme(next);
            try { localStorage.setItem("nova-theme", next); } catch (e) {}
        });
    }

    // -------------------- Mobile nav toggle --------------------
    function initNavToggle() {
        var toggle = $(".nav-toggle");
        var nav = $(".nav");
        if (!toggle || !nav) return;

        toggle.addEventListener("click", function () {
            var open = nav.classList.toggle("is-open");
            toggle.setAttribute("aria-expanded", open ? "true" : "false");
        });

        // Close menu when a nav link is clicked.
        $$(".nav a").forEach(function (link) {
            link.addEventListener("click", function () { nav.classList.remove("is-open"); });
        });
    }

    // -------------------- Copy to clipboard --------------------
    function initCopyButtons() {
        $$("[data-copy]").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var target = btn.getAttribute("data-copy");
                var input = target.charAt(0) === "#"
                    ? document.getElementById(target.slice(1))
                    : null;
                var value = input ? input.value : target;

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(value).then(function () {
                        flashLabel(btn, "Copied!");
                    }).catch(fallbackCopy);
                } else {
                    fallbackCopy();
                }

                function fallbackCopy() {
                    if (!input) return;
                    input.select();
                    try {
                        document.execCommand("copy");
                        flashLabel(btn, "Copied!");
                    } catch (e) {}
                }
            });
        });
    }

    function flashLabel(btn, msg) {
        var original = btn.innerHTML;
        btn.innerHTML = msg;
        setTimeout(function () { btn.innerHTML = original; }, 1500);
    }

    // -------------------- Faucet cooldown countdown --------------------
    function initCountdown() {
        var el = $("[data-countdown]");
        if (!el) return;

        var endTs = parseInt(el.getAttribute("data-countdown"), 10);
        if (isNaN(endTs)) return;

        function tick() {
            var diff = endTs - Math.floor(Date.now() / 1000);
            if (diff <= 0) {
                el.textContent = "Ready!";
                var btn = $("[data-claim-button]");
                if (btn) btn.classList.remove("is-disabled");
                return;
            }
            var m = Math.floor(diff / 60);
            var s = diff % 60;
            el.textContent = (m < 10 ? "0" + m : m) + ":" + (s < 10 ? "0" + s : s);
            setTimeout(tick, 1000);
        }
        tick();
    }

    // -------------------- Flash auto-dismiss --------------------
    function initFlash() {
        $$(".alert[data-autodismiss]").forEach(function (el) {
            setTimeout(function () {
                el.style.transition = "opacity 400ms";
                el.style.opacity = "0";
                setTimeout(function () { el.remove(); }, 450);
            }, 6000);
        });
    }

    // -------------------- Boot --------------------
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", boot);
    } else {
        boot();
    }

    function boot() {
        initTheme();
        initNavToggle();
        initCopyButtons();
        initCountdown();
        initFlash();
    }
}());
