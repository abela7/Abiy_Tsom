@extends('layouts.admin')
@section('title', __('app.whatsapp_reminders_tab'))

@section('content')
@include('admin.whatsapp._nav')

<div class="mb-6">
    <h1 class="text-2xl font-bold text-primary">{{ __('app.whatsapp_reminders_tab') }}</h1>
    <p class="text-sm text-muted-text mt-1">{{ __('app.whatsapp_reminders_help') }}</p>
</div>

{{-- Stats --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <p class="text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.whatsapp_opted_in_total') }}</p>
        <p class="text-2xl font-black text-accent mt-1">{{ number_format($totalOptedIn) }}</p>
    </div>
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <p class="text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.whatsapp_time_slots') }}</p>
        <p class="text-2xl font-black text-accent-secondary mt-1">{{ number_format($byTime->count()) }}</p>
    </div>
</div>

{{-- Timetable --}}
<div class="mb-6" x-data="{ slotMembers: @js($membersByTime->mapWithKeys(fn ($members, $time) => [$time => $members->map(fn ($m) => ['id' => $m->id, 'baptism_name' => $m->baptism_name, 'phone' => maskPhone($m->whatsapp_phone ?? ''), 'last_sent' => $m->whatsapp_last_sent_date?->format('Y-m-d')])->values()->all()])->all()) }">
    <h2 class="text-lg font-bold text-primary mb-2">{{ __('app.timetable_title') }}</h2>
    <p class="text-sm text-muted-text mb-4">{{ __('app.timetable_help') }}</p>

    @if($byTime->isEmpty())
        <p class="text-muted-text">{{ __('app.whatsapp_no_opted_in') }}</p>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            @foreach($byTime as $row)
                @php
                    $timeDisplay = \Carbon\Carbon::parse($row->time)->format('H:i');
                @endphp
                <button type="button"
                        @click="$dispatch('open-slot', { timeDisplay: @js($timeDisplay), members: slotMembers[@js($row->time)] || [] })"
                        class="block p-4 rounded-xl border-2 border-border hover:border-accent hover:bg-accent/5 text-left transition focus:outline-none focus:ring-2 focus:ring-accent">
                    <span class="text-lg font-bold text-primary">{{ $timeDisplay }}</span>
                    <span class="text-xs text-muted-text block mt-0.5">{{ __('app.london_time') }}</span>
                    <span class="inline-block mt-2 px-2 py-0.5 rounded-md bg-accent/20 text-accent text-xs font-bold">{{ $row->count }}</span>
                </button>
            @endforeach
        </div>
    @endif
</div>

{{-- Members list + Edit modal --}}
<div x-data="reminderActions()">
{{-- Members list --}}
<div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden">
    <div class="px-4 py-3 border-b border-border">
        <h2 class="text-sm font-bold text-primary">{{ __('app.whatsapp_members_list') }}</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-muted">
                <tr>
                    <th class="text-left px-4 py-2 font-semibold text-secondary">{{ __('app.baptism_name') }}</th>
                    <th class="text-left px-4 py-2 font-semibold text-secondary">{{ __('app.whatsapp_phone') }}</th>
                    <th class="text-left px-4 py-2 font-semibold text-secondary">{{ __('app.whatsapp_reminder_time') }}</th>
                    <th class="text-left px-4 py-2 font-semibold text-secondary">{{ __('app.whatsapp_last_sent') }}</th>
                    <th class="text-left px-4 py-2 font-semibold text-secondary">{{ __('app.registered') }}</th>
                    <th class="text-left px-4 py-2 font-semibold text-secondary">{{ __('app.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($members as $m)
                    <tr class="hover:bg-muted/50">
                        <td class="px-4 py-2 font-medium">{{ $m->baptism_name ?: '—' }}</td>
                        <td class="px-4 py-2 font-mono text-xs">{{ $m->whatsapp_phone ? maskPhone($m->whatsapp_phone) : '—' }}</td>
                        <td class="px-4 py-2">{{ $m->whatsapp_reminder_time ? \Carbon\Carbon::parse($m->whatsapp_reminder_time)->format('H:i') : '—' }} {{ __('app.london_time') }}</td>
                        <td class="px-4 py-2">{{ $m->whatsapp_last_sent_date ? $m->whatsapp_last_sent_date->format('Y-m-d') : __('app.never') }}</td>
                        <td class="px-4 py-2 text-muted-text">{{ $m->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-2">
                            <div class="flex items-center gap-2">
                                <button type="button"
                                        @click="openEdit({{ $m->id }}, @js($m->baptism_name), @js($m->whatsapp_phone), @js($m->whatsapp_reminder_time ? \Carbon\Carbon::parse($m->whatsapp_reminder_time)->format('H:i') : ''))"
                                        class="text-accent hover:underline text-xs font-medium">
                                    {{ __('app.edit') }}
                                </button>
                                <form method="POST" action="{{ route('admin.whatsapp.reminders.disable', $m) }}" class="inline"
                                      x-data @submit.prevent="if (confirm('{{ __('app.reminder_disable_confirm') }}')) $el.submit()">
                                    @csrf
                                    <button type="submit" class="text-amber-600 hover:underline text-xs font-medium">
                                        {{ __('app.disable_reminder') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.whatsapp.reminders.destroy', $m) }}" class="inline"
                                      x-data @submit.prevent="if (confirm('{{ __('app.member_delete_confirm') }}')) $el.submit()">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline text-xs font-medium">
                                        {{ __('app.delete') }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-muted-text">{{ __('app.whatsapp_no_opted_in') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($members->hasPages())
        <div class="px-4 py-3 border-t border-border">
            {{ $members->links() }}
        </div>
    @endif
</div>

{{-- Slot modal: members for selected time with Send Reminder --}}
<div x-show="slotOpen"
     x-cloak
     @open-slot.window="slotOpen = true; slotTimeDisplay = $event.detail.timeDisplay; slotMembers = $event.detail.members"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-overlay"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    <div @click.away="slotOpen = false"
         x-show="slotOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="w-full max-w-md max-h-[85vh] flex flex-col bg-card rounded-2xl shadow-2xl border border-border overflow-hidden">
        <div class="px-4 py-3 border-b border-border shrink-0">
            <h3 class="text-lg font-bold text-primary" x-text="slotTimeDisplay + ' ' + '{{ addslashes(__('app.london_time')) }}'"></h3>
            <p class="text-xs text-muted-text" x-text="slotMembers.length + ' {{ __('app.timetable_member_count') }}'"></p>
        </div>
        <div class="flex-1 overflow-y-auto p-4 space-y-3">
            <template x-for="m in slotMembers" :key="m.id">
                <div class="flex items-center justify-between gap-3 p-3 rounded-xl bg-muted/50 border border-border">
                    <div class="min-w-0">
                        <p class="font-semibold text-primary truncate" x-text="m.baptism_name || '—'"></p>
                        <p class="text-xs text-muted-text font-mono" x-text="m.phone || '—'"></p>
                        <p class="text-xs text-muted-text" x-text="m.last_sent || '{{ __('app.never') }}'"></p>
                    </div>
                    <button type="button"
                            @click="sendReminder(m.id)"
                            :disabled="sendingId === m.id"
                            class="shrink-0 px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2"
                            :class="sendingId === m.id ? 'bg-muted text-muted-text cursor-wait' : 'bg-green-600 text-white hover:bg-green-500'">
                        <svg x-show="sendingId !== m.id" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        <svg x-show="sendingId === m.id" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <span x-text="sendingId === m.id ? '{{ __('app.sending') }}' : '{{ __('app.send_reminder') }}'"></span>
                    </button>
                </div>
            </template>
        </div>
        <div class="px-4 py-3 border-t border-border shrink-0 flex justify-end">
            <p x-show="slotMsg" x-text="slotMsg" class="text-sm mr-3" :class="slotMsgError ? 'text-error' : 'text-success'"></p>
            <button type="button" @click="slotOpen = false"
                    class="px-4 py-2 bg-muted text-secondary rounded-lg font-medium hover:bg-muted/80 transition">
                {{ __('app.close') }}
            </button>
        </div>
    </div>
</div>

{{-- Edit modal --}}
<div x-show="editOpen"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-overlay"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    <div @click.away="editOpen = false"
         x-show="editOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="w-full max-w-md bg-card rounded-2xl shadow-2xl border border-border p-6">
        <h3 class="text-lg font-bold text-primary mb-4">{{ __('app.edit_reminder') }}</h3>

        <form method="POST" :action="editUrl" x-ref="editForm">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <div>
                    <label for="edit_baptism_name" class="block text-sm font-semibold text-secondary mb-1.5">
                        {{ __('app.baptism_name') }}
                    </label>
                    <input type="text"
                           id="edit_baptism_name"
                           name="baptism_name"
                           x-model="editBaptismName"
                           required
                           maxlength="255"
                           class="w-full px-3 py-2.5 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
                </div>

                <div>
                    <label for="edit_whatsapp_phone" class="block text-sm font-semibold text-secondary mb-1.5">
                        {{ __('app.whatsapp_phone') }}
                    </label>
                    <input type="tel"
                           id="edit_whatsapp_phone"
                           name="whatsapp_phone"
                           x-model="editPhone"
                           placeholder="07123456789"
                           required
                           maxlength="20"
                           class="w-full px-3 py-2.5 border border-border rounded-lg bg-card text-primary font-mono focus:ring-2 focus:ring-accent outline-none">
                </div>

                <div>
                    <label for="edit_reminder_time" class="block text-sm font-semibold text-secondary mb-1.5">
                        {{ __('app.whatsapp_reminder_time') }}
                    </label>
                    <input type="time"
                           id="edit_reminder_time"
                           name="whatsapp_reminder_time"
                           x-model="editTime"
                           required
                           class="w-full px-3 py-2.5 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="submit" class="flex-1 py-2.5 bg-accent text-on-accent rounded-lg font-semibold hover:bg-accent-hover transition">
                    {{ __('app.save') }}
                </button>
                <button type="button"
                        @click="editOpen = false"
                        class="flex-1 py-2.5 bg-muted text-secondary rounded-lg font-semibold hover:bg-muted/80 transition">
                    {{ __('app.cancel') }}
                </button>
            </div>
        </form>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
function reminderActions() {
    return {
        editOpen: false,
        editId: null,
        editBaptismName: '',
        editPhone: '',
        editTime: '',
        baseUrl: '{{ url('/') }}',

        slotOpen: false,
        slotTimeDisplay: '',
        slotMembers: [],
        sendingId: null,
        slotMsg: '',
        slotMsgError: false,

        get editUrl() {
            return this.editId ? this.baseUrl + '/admin/whatsapp/reminders/' + this.editId : '#';
        },

        openEdit(id, baptismName, phone, time) {
            this.editId = id;
            this.editBaptismName = baptismName || '';
            this.editPhone = phone || '';
            this.editTime = time || '09:00';
            this.editOpen = true;
        },

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
