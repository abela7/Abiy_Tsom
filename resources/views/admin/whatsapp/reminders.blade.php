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

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    {{-- By time --}}
    <div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden">
        <div class="px-4 py-3 border-b border-border">
            <h2 class="text-sm font-bold text-primary">{{ __('app.whatsapp_members_by_time') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-muted">
                    <tr>
                        <th class="text-left px-4 py-2 font-semibold text-secondary">{{ __('app.whatsapp_reminder_time') }}</th>
                        <th class="text-right px-4 py-2 font-semibold text-secondary">{{ __('app.count') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($byTime as $row)
                        <tr class="hover:bg-muted/50">
                            <td class="px-4 py-2 font-medium">{{ \Carbon\Carbon::parse($row->time)->format('H:i') }} {{ __('app.london_time') }}</td>
                            <td class="px-4 py-2 text-right font-bold text-accent">{{ $row->count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-8 text-center text-muted-text">{{ __('app.whatsapp_no_opted_in') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
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

        get editUrl() {
            return this.editId ? this.baseUrl + '/admin/whatsapp/reminders/' + this.editId : '#';
        },

        openEdit(id, baptismName, phone, time) {
            this.editId = id;
            this.editBaptismName = baptismName || '';
            this.editPhone = phone || '';
            this.editTime = time || '09:00';
            this.editOpen = true;
        }
    };
}
</script>
@endpush
