{{-- Sub-navigation for WhatsApp admin section --}}
<nav class="flex flex-wrap gap-1 mb-8 p-1.5 rounded-xl bg-muted/60 w-fit" role="tablist">
    <a href="{{ route('admin.whatsapp.settings') }}"
       class="px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.whatsapp.settings') ? 'bg-accent text-on-accent shadow-sm' : 'text-secondary hover:text-primary hover:bg-accent-overlay' }}">
        {{ __('app.whatsapp_settings_tab') }}
    </a>
    <a href="{{ route('admin.whatsapp.reminders') }}"
       class="px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.whatsapp.reminders') ? 'bg-accent text-on-accent shadow-sm' : 'text-secondary hover:text-primary hover:bg-accent-overlay' }}">
        {{ __('app.whatsapp_reminders_tab') }}
    </a>
    <a href="{{ route('admin.whatsapp.timetable') }}"
       class="px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.whatsapp.timetable') ? 'bg-accent text-on-accent shadow-sm' : 'text-secondary hover:text-primary hover:bg-accent-overlay' }}">
        {{ __('app.timetable_title') }}
    </a>
    <a href="{{ route('admin.whatsapp.cron') }}"
       class="px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.whatsapp.cron') ? 'bg-accent text-on-accent shadow-sm' : 'text-secondary hover:text-primary hover:bg-accent-overlay' }}">
        {{ __('app.whatsapp_cron_tab') }}
    </a>
</nav>
