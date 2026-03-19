{{-- Sub-navigation for Telegram admin section --}}
<nav class="flex flex-wrap gap-1 mb-8 p-1.5 rounded-xl bg-muted/60 w-fit" role="tablist">
    <a href="{{ route('admin.telegram.settings') }}"
       class="px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.telegram.settings') ? 'bg-accent text-on-accent shadow-sm' : 'text-secondary hover:text-primary hover:bg-accent-overlay' }}">
        {{ __('app.telegram_settings_tab') }}
    </a>
    <a href="{{ route('admin.telegram.users') }}"
       class="px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.telegram.users') ? 'bg-accent text-on-accent shadow-sm' : 'text-secondary hover:text-primary hover:bg-accent-overlay' }}">
        {{ __('app.telegram_linked_users_tab') }}
    </a>
</nav>
