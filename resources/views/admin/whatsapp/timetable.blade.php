@extends('layouts.admin')
@section('title', __('app.timetable_title') . ' - ' . __('app.app_name'))

@section('content')
@include('admin.whatsapp._nav')

<div class="min-h-[60vh]" x-data="timetablePage()">
    {{-- Header --}}
    <div class="mb-6 sm:mb-8">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-primary tracking-tight">{{ __('app.timetable_title') }}</h1>
                <p class="text-sm sm:text-base text-muted-text mt-1.5 max-w-xl">{{ __('app.timetable_help') }}</p>
            </div>
            <div class="flex gap-3">
                <div class="px-4 py-3 rounded-xl bg-accent/10 border border-accent/20 min-w-[7rem]">
                    <p class="text-[10px] sm:text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.whatsapp_opted_in_total') }}</p>
                    <p class="text-xl sm:text-2xl font-black text-accent mt-0.5">{{ number_format($totalOptedIn) }}</p>
                </div>
                <div class="px-4 py-3 rounded-xl bg-muted/80 border border-border min-w-[7rem]">
                    <p class="text-[10px] sm:text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.whatsapp_time_slots') }}</p>
                    <p class="text-xl sm:text-2xl font-black text-secondary mt-0.5">{{ number_format($byTime->count()) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Empty state --}}
    @if($byTime->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 sm:py-24 px-4 text-center">
            <div class="w-16 h-16 rounded-2xl bg-muted flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h2 class="text-lg font-bold text-primary mb-2">{{ __('app.whatsapp_no_opted_in') }}</h2>
            <p class="text-sm text-muted-text max-w-sm">{{ __('app.timetable_help') }}</p>
            <a href="{{ route('admin.whatsapp.reminders') }}"
               class="mt-6 inline-flex items-center gap-2 px-5 py-2.5 bg-accent text-on-accent rounded-xl font-semibold text-sm hover:bg-accent-hover transition">
                {{ __('app.whatsapp_reminders_tab') }}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    @else
        {{-- Timetable grid: mobile 2 cols, tablet 3–4, desktop 5–6 --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 sm:gap-4"
             x-data="{ slotData: @js($membersByTime->mapWithKeys(fn ($members, $time) => [$time => $members->map(fn ($m) => ['id' => $m->id, 'baptism_name' => $m->baptism_name, 'phone' => maskPhone($m->whatsapp_phone ?? ''), 'last_sent' => $m->whatsapp_last_sent_date?->format('Y-m-d')])->values()->all()])->all()) }">
            @foreach($byTime as $row)
                @php $timeDisplay = \Carbon\Carbon::parse($row->time)->format('H:i'); @endphp
                <button type="button"
                        @click="$dispatch('open-slot', { timeDisplay: @js($timeDisplay), members: slotData[@js($row->time)] || [] })"
                        class="group relative block w-full aspect-square min-h-[5.5rem] sm:min-h-[6.5rem] p-4 rounded-2xl border-2 border-border bg-card hover:border-accent hover:bg-accent/5 hover:shadow-lg hover:shadow-accent/10 active:scale-[0.98] transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 text-left">
                    <span class="text-xl sm:text-2xl font-bold text-primary block" x-text="'{{ $timeDisplay }}'">{{ $timeDisplay }}</span>
                    <span class="text-[10px] sm:text-xs text-muted-text block mt-0.5">{{ __('app.london_time') }}</span>
                    <span class="absolute bottom-3 right-3 sm:bottom-4 sm:right-4 inline-flex items-center justify-center min-w-[1.75rem] h-7 px-2 rounded-lg bg-accent/15 text-accent font-bold text-sm group-hover:bg-accent group-hover:text-on-accent transition-colors">
                        {{ $row->count }}
                    </span>
                </button>
            @endforeach
        </div>
    @endif

    {{-- Slot modal: members list + Send Reminder --}}
    <div x-show="slotOpen"
         x-cloak
         @open-slot.window="slotOpen = true; slotTimeDisplay = $event.detail.timeDisplay; slotMembers = $event.detail.members || []"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
         role="dialog"
         aria-modal="true"
         :aria-label="slotTimeDisplay ? (slotTimeDisplay + ' — ' + slotMembers.length + ' {{ __('app.timetable_member_count') }}') : ''">
        {{-- Backdrop --}}
        <div @click="slotOpen = false"
             class="absolute inset-0 bg-overlay"
             aria-hidden="true"></div>

        {{-- Panel: bottom sheet on mobile, centered card on desktop --}}
        <div class="relative w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl sm:shadow-2xl sm:border sm:border-border bg-card max-h-[85vh] sm:max-h-[80vh] flex flex-col animate-slide-up sm:animate-none overflow-hidden pb-[env(safe-area-inset-bottom,0px)] sm:pb-0"
             @click.stop>
            <div class="shrink-0 px-4 sm:px-6 py-4 border-b border-border">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-primary" x-text="slotTimeDisplay + ' ' + '{{ addslashes(__('app.london_time')) }}'"></h3>
                        <p class="text-xs text-muted-text mt-0.5" x-text="slotMembers.length + ' {{ __('app.timetable_member_count') }}'"></p>
                    </div>
                    <button type="button"
                            @click="slotOpen = false"
                            class="p-2 rounded-xl text-muted-text hover:bg-muted hover:text-primary transition touch-manipulation"
                            :aria-label="'{{ __('app.close') }}'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto overscroll-contain p-4 sm:p-6 space-y-3">
                <template x-for="m in slotMembers" :key="m.id">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 rounded-xl bg-muted/50 border border-border hover:border-accent/30 transition">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-primary truncate" x-text="m.baptism_name || '—'"></p>
                            <p class="text-xs text-muted-text font-mono mt-0.5" x-text="m.phone || '—'"></p>
                            <p class="text-[10px] sm:text-xs text-muted-text mt-1" x-text="m.last_sent ? m.last_sent : '{{ __('app.never') }}'"></p>
                        </div>
                        <button type="button"
                                @click="sendReminder(m.id)"
                                :disabled="sendingId === m.id"
                                class="shrink-0 w-full sm:w-auto px-4 py-3 rounded-xl text-sm font-bold transition flex items-center justify-center gap-2 touch-manipulation min-h-[2.75rem]"
                                :class="sendingId === m.id ? 'bg-muted text-muted-text cursor-wait' : 'bg-green-600 text-white hover:bg-green-500 active:scale-[0.98] shadow-lg shadow-green-600/20'">
                            <svg x-show="sendingId !== m.id" class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            <svg x-show="sendingId === m.id" class="w-5 h-5 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <span x-text="sendingId === m.id ? '{{ __('app.sending') }}' : '{{ __('app.send_reminder') }}'"></span>
                        </button>
                    </div>
                </template>
            </div>

            <div class="shrink-0 px-4 sm:px-6 py-3 border-t border-border flex items-center justify-between gap-3 bg-muted/30">
                <p x-show="slotMsg" x-text="slotMsg" class="text-sm flex-1 truncate" :class="slotMsgError ? 'text-error' : 'text-success'"></p>
                <button type="button"
                        @click="slotOpen = false"
                        class="px-4 py-2 rounded-xl bg-muted text-secondary font-semibold text-sm hover:bg-muted/80 transition shrink-0">
                    {{ __('app.close') }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
@keyframes slide-up {
    from { transform: translateY(100%); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.animate-slide-up {
    animation: slide-up 0.3s ease-out;
}
</style>
@endpush

@push('scripts')
<script>
function timetablePage() {
    return {
        slotOpen: false,
        slotTimeDisplay: '',
        slotMembers: [],
        sendingId: null,
        slotMsg: '',
        slotMsgError: false,
        baseUrl: '{{ url('/') }}',

        async sendReminder(memberId) {
            this.sendingId = memberId;
            this.slotMsg = '';
            try {
                const res = await fetch(this.baseUrl + '/admin/whatsapp/reminders/' + memberId + '/send', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({}),
                });
                const data = await res.json();
                this.slotMsg = data.message || (data.sent ? '{{ __("app.timetable_reminder_sent") }}' : '{{ __("app.whatsapp_test_failed") }}');
                this.slotMsgError = !data.success || !data.sent;
                if (data.success && data.sent) {
                    const m = this.slotMembers.find(x => x.id === memberId);
                    if (m) m.last_sent = new Date().toISOString().slice(0, 10);
                }
            } catch (e) {
                this.slotMsg = '{{ __("app.whatsapp_test_failed") }}';
                this.slotMsgError = true;
            } finally {
                this.sendingId = null;
                setTimeout(() => { this.slotMsg = ''; }, 4000);
            }
        }
    };
}
</script>
@endpush
@endsection
