@extends('layouts.admin')

@section('title', __('app.audit_log'))

@section('content')
@php
    $formatAuditValue = static function (mixed $value): string {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    };
@endphp
<div class="flex flex-col gap-6">
    <div>
        <h1 class="text-2xl font-bold text-primary">{{ __('app.audit_log') }}</h1>
        <p class="text-sm text-muted-text mt-1">{{ __('app.audit_log_help') }}</p>
    </div>

    <form method="GET" action="{{ route('admin.audit.index') }}"
          class="bg-card rounded-xl border border-border shadow-sm p-4 sm:p-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-4">
        <div>
            <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.audit_admin_user') }}</label>
            <select name="admin_user_id"
                    class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
                <option value="">{{ __('app.all') }}</option>
                @foreach($admins as $admin)
                    <option value="{{ $admin->id }}" {{ $filters['admin_user_id'] === (string) $admin->id ? 'selected' : '' }}>
                        {{ $admin->name }} ({{ $admin->username }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.audit_action_route') }}</label>
            <select name="route_name"
                    class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
                <option value="">{{ __('app.all') }}</option>
                @foreach($routeOptions as $routeOption)
                    <option value="{{ $routeOption }}" {{ $filters['route_name'] === $routeOption ? 'selected' : '' }}>
                        {{ $routeOption }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.audit_method') }}</label>
            <select name="method"
                    class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
                <option value="">{{ __('app.all') }}</option>
                @foreach(['POST', 'PUT', 'PATCH', 'DELETE'] as $method)
                    <option value="{{ $method }}" {{ $filters['method'] === $method ? 'selected' : '' }}>
                        {{ $method }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.audit_target_type') }}</label>
            <select name="target_type"
                    class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
                <option value="">{{ __('app.all') }}</option>
                @foreach($targetTypeOptions as $targetType)
                    <option value="{{ $targetType }}" {{ $filters['target_type'] === $targetType ? 'selected' : '' }}>
                        {{ $targetType }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.audit_from_date') }}</label>
            <input type="date" name="from" value="{{ $filters['from'] }}"
                   class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.audit_to_date') }}</label>
            <input type="date" name="to" value="{{ $filters['to'] }}"
                   class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
        </div>

        <div class="md:col-span-2 xl:col-span-6 flex flex-wrap gap-3">
            <button type="submit"
                    class="px-4 py-2.5 bg-accent text-on-accent rounded-lg font-medium hover:bg-accent-hover transition">
                {{ __('app.filter') }}
            </button>
            <a href="{{ route('admin.audit.index') }}"
               class="px-4 py-2.5 bg-muted text-secondary rounded-lg font-medium hover:bg-border transition">
                {{ __('app.clear_filters') }}
            </a>
        </div>
    </form>

    <div class="space-y-3 lg:hidden">
        @forelse($logs as $log)
            @php($valueChanges = $log->meta['value_changes'] ?? [])
            <div class="bg-card rounded-xl border border-border shadow-sm p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-primary">{{ $log->action }}</p>
                        <p class="text-xs text-muted-text mt-1">{{ $log->created_at->format('d M Y H:i:s') }}</p>
                    </div>
                    <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $log->status_code >= 400 ? 'bg-error-bg text-error' : 'bg-success-bg text-success' }}">
                        {{ $log->status_code }}
                    </span>
                </div>

                <div class="text-sm space-y-1">
                    <p><span class="font-medium text-secondary">{{ __('app.audit_admin_user') }}:</span> {{ $log->adminUser?->name ?? __('app.deleted_admin_user') }}</p>
                    <p><span class="font-medium text-secondary">{{ __('app.audit_target') }}:</span> {{ $log->target_type ?? '—' }} @if($log->target_label)<span class="text-muted-text">({{ $log->target_label }})</span>@endif</p>
                    <p><span class="font-medium text-secondary">{{ __('app.audit_action_route') }}:</span> <span class="break-all">{{ $log->route_name ?? '—' }}</span></p>
                    <p><span class="font-medium text-secondary">{{ __('app.audit_method') }}:</span> {{ $log->method }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold text-muted-text uppercase tracking-wide mb-2">{{ __('app.audit_exact_changes') }}</p>
                    @if($valueChanges !== [])
                        <div class="space-y-2">
                            @foreach($valueChanges as $field => $change)
                                <div class="rounded-lg border border-border bg-surface p-3 text-xs">
                                    <p class="font-semibold text-primary">{{ $field }}</p>
                                    <p class="text-muted-text mt-1">{{ __('app.audit_before') }}: {{ $formatAuditValue($change['before'] ?? null) }}</p>
                                    <p class="text-muted-text">{{ __('app.audit_after') }}: {{ $formatAuditValue($change['after'] ?? null) }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="space-y-2">
                            <p class="text-sm text-muted-text">{{ __('app.audit_no_exact_changes') }}</p>
                            <div class="flex flex-wrap gap-2">
                                @forelse($log->changed_fields ?? [] as $field)
                                    <span class="px-2 py-1 rounded-md bg-muted text-secondary text-xs">{{ $field }}</span>
                                @empty
                                    <span class="text-sm text-muted-text">—</span>
                                @endforelse
                            </div>
                        </div>
                    @endif
                </div>

                <details class="rounded-lg border border-border bg-surface">
                    <summary class="px-3 py-2 text-sm font-medium text-secondary cursor-pointer">{{ __('app.audit_view_details') }}</summary>
                    <div class="px-3 pb-3 space-y-3">
                        <div>
                            <p class="text-xs font-semibold text-muted-text uppercase tracking-wide mb-1">{{ __('app.audit_request_summary') }}</p>
                            <pre class="text-xs text-secondary whitespace-pre-wrap break-words bg-card rounded-lg border border-border p-3 overflow-x-auto">{{ json_encode($log->request_summary ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-muted-text uppercase tracking-wide mb-1">{{ __('app.audit_meta') }}</p>
                            <pre class="text-xs text-secondary whitespace-pre-wrap break-words bg-card rounded-lg border border-border p-3 overflow-x-auto">{{ json_encode($log->meta ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>
                </details>
            </div>
        @empty
            <div class="bg-card rounded-xl border border-border shadow-sm p-6 text-sm text-muted-text">
                {{ __('app.audit_log_empty') }}
            </div>
        @endforelse
    </div>

    <div class="hidden lg:block bg-card rounded-xl border border-border shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[1100px]">
                <thead class="bg-muted border-b border-border">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.date') }}</th>
                        <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.audit_admin_user') }}</th>
                        <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.audit_action') }}</th>
                        <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.audit_target') }}</th>
                        <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.audit_changed_fields') }}</th>
                        <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.status') }}</th>
                        <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.audit_details') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($logs as $log)
                        @php($valueChanges = $log->meta['value_changes'] ?? [])
                        <tr class="hover:bg-muted/40 align-top">
                            <td class="px-4 py-3 whitespace-nowrap text-secondary">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-primary">{{ $log->adminUser?->name ?? __('app.deleted_admin_user') }}</div>
                                <div class="text-xs text-muted-text">{{ $log->adminUser?->username ?? '—' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-primary">{{ $log->action }}</div>
                                <div class="text-xs text-muted-text break-all">{{ $log->route_name ?? '—' }}</div>
                                <div class="text-xs text-muted-text">{{ $log->method }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-primary">{{ $log->target_type ?? '—' }}</div>
                                <div class="text-xs text-muted-text">ID: {{ $log->target_id ?? '—' }}</div>
                                @if($log->target_label)
                                    <div class="text-xs text-muted-text">{{ $log->target_label }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($valueChanges !== [])
                                    <div class="space-y-2 max-w-sm">
                                        @foreach($valueChanges as $field => $change)
                                            <div class="rounded-lg border border-border bg-surface p-2 text-xs">
                                                <p class="font-semibold text-primary">{{ $field }}</p>
                                                <p class="text-muted-text mt-1">{{ __('app.audit_before') }}: {{ $formatAuditValue($change['before'] ?? null) }}</p>
                                                <p class="text-muted-text">{{ __('app.audit_after') }}: {{ $formatAuditValue($change['after'] ?? null) }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="space-y-2 max-w-xs">
                                        <p class="text-xs text-muted-text">{{ __('app.audit_no_exact_changes') }}</p>
                                        <div class="flex flex-wrap gap-2">
                                            @forelse($log->changed_fields ?? [] as $field)
                                                <span class="px-2 py-1 rounded-md bg-muted text-secondary text-xs">{{ $field }}</span>
                                            @empty
                                                <span class="text-muted-text">—</span>
                                            @endforelse
                                        </div>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $log->status_code >= 400 ? 'bg-error-bg text-error' : 'bg-success-bg text-success' }}">
                                    {{ $log->status_code }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <details class="rounded-lg border border-border bg-surface">
                                    <summary class="px-3 py-2 text-sm font-medium text-secondary cursor-pointer">{{ __('app.audit_view_details') }}</summary>
                                    <div class="px-3 pb-3 space-y-3">
                                        <div>
                                            <p class="text-xs font-semibold text-muted-text uppercase tracking-wide mb-1">{{ __('app.audit_request_summary') }}</p>
                                            <pre class="text-xs text-secondary whitespace-pre-wrap break-words bg-card rounded-lg border border-border p-3 overflow-x-auto">{{ json_encode($log->request_summary ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold text-muted-text uppercase tracking-wide mb-1">{{ __('app.audit_meta') }}</p>
                                            <pre class="text-xs text-secondary whitespace-pre-wrap break-words bg-card rounded-lg border border-border p-3 overflow-x-auto">{{ json_encode($log->meta ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-muted-text">
                                {{ __('app.audit_log_empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        {{ $logs->links() }}
    </div>
</div>
@endsection
