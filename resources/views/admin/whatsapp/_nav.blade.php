{{-- Sub-navigation for WhatsApp admin section --}}
<nav class="flex flex-wrap gap-1 mb-6 border-b border-border pb-4">
    <a href="{{ route('admin.whatsapp.settings') }}"
       class="px-4 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.whatsapp.settings') ? 'bg-accent text-on-accent' : 'text-secondary hover:bg-muted' }}">
        {{ __('app.whatsapp_settings_tab') }}
    </a>
    <a href="{{ route('admin.whatsapp.reminders') }}"
       class="px-4 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.whatsapp.reminders') ? 'bg-accent text-on-accent' : 'text-secondary hover:bg-muted' }}">
        {{ __('app.whatsapp_reminders_tab') }}
    </a>
    <a href="{{ route('admin.whatsapp.timetable') }}"
       class="px-4 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.whatsapp.timetable') ? 'bg-accent text-on-accent' : 'text-secondary hover:bg-muted' }}">
        {{ __('app.timetable_title') }}
    </a>
    <a href="{{ route('admin.whatsapp.cron') }}"
       class="px-4 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.whatsapp.cron') ? 'bg-accent text-on-accent' : 'text-secondary hover:bg-muted' }}">
        {{ __('app.whatsapp_cron_tab') }}
    </a>
</nav>
