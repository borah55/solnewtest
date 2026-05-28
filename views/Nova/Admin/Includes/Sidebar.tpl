{* Reusable admin sidebar. The active page is highlighted via $activeAdmin. *}
<aside class="admin-sidebar">
    <div style="padding: 0.5rem 0.75rem 1rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted);">
        Admin panel
    </div>
    <a href="{App::makeLink('admin/home')}" {if isset($activeAdmin) && $activeAdmin == 'home'}class="active"{/if}>Dashboard</a>
    <a href="{App::makeLink('admin/users')}" {if isset($activeAdmin) && $activeAdmin == 'users'}class="active"{/if}>Users</a>
    <a href="{App::makeLink('admin/banned-users')}" {if isset($activeAdmin) && $activeAdmin == 'banned'}class="active"{/if}>Banned users</a>
    <a href="{App::makeLink('admin/ads')}" {if isset($activeAdmin) && $activeAdmin == 'ads'}class="active"{/if}>Ad slots</a>
    <a href="{App::makeLink('admin/settings')}" {if isset($activeAdmin) && $activeAdmin == 'settings'}class="active"{/if}>Site settings</a>
    <a href="{App::makeLink('admin/profile')}" {if isset($activeAdmin) && $activeAdmin == 'profile'}class="active"{/if}>Your profile</a>
    <div style="border-top: 1px solid var(--border-soft); margin: 0.5rem 0;"></div>
    <a href="{App::makeLink('admin/logout')}">Sign out</a>
</aside>
