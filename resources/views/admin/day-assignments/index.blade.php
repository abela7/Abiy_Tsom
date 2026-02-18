@extends('layouts.admin')
@section('title', __('app.day_assignments'))

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-primary">{{ __('app.day_assignments') }}</h1>
    <p class="text-sm text-muted-text mt-1">{{ __('app.day_assignments_help') }}</p>
</div>

@if(!$season)
    <p class="text-muted-text">{{ __('app.no_active_season') }} <a href="{{ route('admin.seasons.create') }}" class="text-accent hover:underline">{{ __('app.create_one_first') }}</a></p>
@elseif($contents->isEmpty())
    <p class="text-muted-text">{{ __('app.day_assignments_scaffold_first') }} <a href="{{ route('admin.daily.index') }}" class="text-accent hover:underline">{{ __('app.daily_content') }}</a>.</p>
@else
    <div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-muted border-b border-border">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.day_label') }}</th>
                        <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.date_label') }}</th>
                        <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.week_label') }}</th>
                        <th class="text-left px-4 py-3 font-semibold text-secondary min-w-[200px]">{{ __('app.assigned_writer') }}</th>
                        <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.send') }}</th>
                        <th class="w-12"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach($contents as $content)
                    <tr class="hover:bg-muted/50" x-data="{
                        assignedId: @js($content->assigned_to_id),
                        assignedName: @js($content->assignedTo?->name),
                        hasWhatsApp: @js(!empty($content->assignedTo?->whatsapp_phone)),
                        saving: false,
                        sendSaving: false,
                        sendResult: null,
                        sendMsg: '',
                        sendUrl: @js(route('admin.day-assignments.send-reminder', $content)),
                        async save() {
                            this.saving = true;
                            try {
                                const res = await fetch('{{ route('admin.day-assignments.update', $content) }}', {
                                    method: 'PATCH',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({ assigned_to_id: this.assignedId || null })
                                });
                                const data = await res.json();
                                if (data.success) {
                                    this.assignedName = data.assigned_name;
                                    this.hasWhatsApp = data.has_whatsapp;
                                }
                            } finally {
                                this.saving = false;
                            }
                        },
                        async sendReminder() {
                            if (!this.assignedId || !this.hasWhatsApp) return;
                            this.sendSaving = true;
                            this.sendResult = null;
                            this.sendMsg = '';
                            try {
                                const res = await fetch(this.sendUrl, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    }
                                });
                                const data = await res.json();
                                this.sendResult = data.success ? 'success' : 'error';
                                this.sendMsg = data.message || '';
                            } catch (e) {
                                this.sendResult = 'error';
                                this.sendMsg = '{{ __('app.writer_reminder_send_failed') }}';
                            } finally {
                                this.sendSaving = false;
                            }
                        }
                    }">
                        <td class="px-4 py-3 font-bold text-accent">{{ $content->day_number }}</td>
                        <td class="px-4 py-3 text-secondary">{{ $content->date->format('M d') }}</td>
                        <td class="px-4 py-3 text-secondary">{{ optional($content->weeklyTheme)->name_en ?: '-' }}</td>
                        <td class="px-4 py-2">
                            <select x-model="assignedId"
                                    :disabled="saving"
                                    @change="save()"
                                    class="w-full max-w-[220px] px-3 py-1.5 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none text-sm">
                                <option value="">— {{ __('app.not_assigned') }} —</option>
                                @foreach($assignableUsers as $user)
                                    <option value="{{ $user->id }}">
                                        {{ $user->name }} ({{ $user->role }}){{ $user->whatsapp_phone ? ' ✓' : ' — ' . __('app.no_whatsapp') }}
                                    </option>
                                @endforeach
                            </select>
                            <span x-show="assignedName && !hasWhatsApp" class="text-amber-600 text-xs ml-1" x-text="'{{ __('app.no_whatsapp') }}'"></span>
                        </td>
                        <td class="px-4 py-2">
                            <template x-if="assignedId && hasWhatsApp">
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                            @click="sendReminder()"
                                            :disabled="sendSaving"
                                            class="px-3 py-1.5 bg-green-600 text-white rounded-lg text-xs font-medium hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1">
                                        <svg x-show="sendSaving" class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span x-text="sendSaving ? '...' : '{{ __('app.send_writer_reminder') }}'"></span>
                                    </button>
                                    <span x-show="sendResult" x-transition class="text-xs"
                                          :class="sendResult === 'success' ? 'text-green-600' : 'text-red-600'"
                                          x-text="sendMsg"></span>
                                </div>
                            </template>
                            <span x-show="!assignedId || !hasWhatsApp" class="text-muted-text text-xs">—</span>
                        </td>
                        <td class="px-4 py-2">
                            <span x-show="saving" class="inline-block w-4 h-4 border-2 border-accent border-t-transparent rounded-full animate-spin"></span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-muted-text mt-4">{{ __('app.day_assignments_reminder_note') }}</p>
@endif
@endsection
