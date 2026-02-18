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
                        <th class="text-left px-4 py-3 font-semibold text-secondary w-12"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach($contents as $content)
                    <tr class="hover:bg-muted/50" x-data="{
                        assignedId: @js($content->assigned_to_id),
                        assignedName: @js($content->assignedTo?->name),
                        hasWhatsApp: @js(!empty($content->assignedTo?->whatsapp_phone)),
                        saving: false,
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
