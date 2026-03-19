@extends('layouts.admin')
@section('title', __('app.telegram_linked_users_title'))

@section('content')
@include('admin.telegram._nav')

<div class="mb-8">
    <h1 class="text-2xl font-bold tracking-tight text-primary">{{ __('app.telegram_linked_users_title') }}</h1>
    <p class="text-sm text-muted-text mt-1.5">{{ __('app.telegram_linked_users_help') }}</p>
</div>

{{-- Summary cards --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <div class="bg-card rounded-xl border border-border p-5 shadow-sm">
        <p class="text-[10px] font-bold text-muted-text uppercase tracking-widest">{{ __('app.telegram_linked_members_count') }}</p>
        <p class="text-3xl font-black text-primary tabular-nums mt-1">{{ number_format($memberCount) }}</p>
    </div>
    <div class="bg-card rounded-xl border border-border p-5 shadow-sm">
        <p class="text-[10px] font-bold text-muted-text uppercase tracking-widest">{{ __('app.telegram_linked_staff_count') }}</p>
        <p class="text-3xl font-black text-primary tabular-nums mt-1">{{ number_format($staffCount) }}</p>
    </div>
    <div class="bg-card rounded-xl border border-border p-5 shadow-sm">
        <p class="text-[10px] font-bold text-muted-text uppercase tracking-widest">{{ __('app.telegram_linked_connections_total') }}</p>
        <p class="text-3xl font-black text-accent tabular-nums mt-1">{{ number_format($connectionTotal) }}</p>
        <p class="text-[11px] text-muted-text mt-2 leading-snug">{{ __('app.telegram_linked_connections_note') }}</p>
    </div>
</div>

@if(count($duplicateMemberChatIds))
    <div class="mb-6 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700">
        <p class="text-sm font-semibold text-amber-700 dark:text-amber-300">
            {{ __('app.telegram_duplicate_chat_ids_warning', ['count' => count($duplicateMemberChatIds)]) }}
        </p>
        <p class="text-xs text-amber-600 dark:text-amber-400 mt-1 font-mono break-all">
            {{ implode(', ', $duplicateMemberChatIds) }}
        </p>
    </div>
@endif

{{-- Staff (usually short list) --}}
<div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden mb-8">
    <div class="px-5 py-4 border-b border-border">
        <h2 class="text-sm font-semibold text-primary">{{ __('app.telegram_linked_staff_heading') }}</h2>
        <p class="text-xs text-muted-text mt-0.5">{{ __('app.telegram_linked_staff_sub') }}</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm whitespace-nowrap">
            <thead>
                <tr class="border-b border-border">
                    <th class="text-left px-5 py-3.5 font-medium text-muted-text text-xs uppercase tracking-wider">ID</th>
                    <th class="text-left px-5 py-3.5 font-medium text-muted-text text-xs uppercase tracking-wider">{{ __('app.name') }}</th>
                    <th class="text-left px-5 py-3.5 font-medium text-muted-text text-xs uppercase tracking-wider">{{ __('app.email') }}</th>
                    <th class="text-left px-5 py-3.5 font-medium text-muted-text text-xs uppercase tracking-wider">{{ __('app.role') }}</th>
                    <th class="text-left px-5 py-3.5 font-medium text-muted-text text-xs uppercase tracking-wider">{{ __('app.telegram_chat_id') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($staffLinked as $u)
                    <tr class="hover:bg-muted/40 transition-colors">
                        <td class="px-5 py-3.5 text-muted-text tabular-nums">{{ $u->id }}</td>
                        <td class="px-5 py-3.5 font-medium text-primary">{{ $u->name }}</td>
                        <td class="px-5 py-3.5 text-secondary">{{ $u->email ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-secondary">{{ $u->role }}</td>
                        <td class="px-5 py-3.5 font-mono text-xs text-secondary">{{ $u->telegram_chat_id }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-muted-text text-sm">{{ __('app.telegram_no_staff_linked') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Members --}}
<div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden">
    <div class="px-5 py-4 border-b border-border flex items-center justify-between gap-2">
        <div>
            <h2 class="text-sm font-semibold text-primary">{{ __('app.telegram_linked_members_heading') }}</h2>
            <p class="text-xs text-muted-text mt-0.5">{{ __('app.telegram_linked_members_sub') }}</p>
        </div>
        <span class="text-xs text-muted-text tabular-nums shrink-0">{{ $members->total() }} {{ __('app.members') }}</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm whitespace-nowrap">
            <thead>
                <tr class="border-b border-border">
                    <th class="text-left px-5 py-3.5 font-medium text-muted-text text-xs uppercase tracking-wider">ID</th>
                    <th class="text-left px-5 py-3.5 font-medium text-muted-text text-xs uppercase tracking-wider">{{ __('app.baptism_name') }}</th>
                    <th class="text-left px-5 py-3.5 font-medium text-muted-text text-xs uppercase tracking-wider">{{ __('app.language') }}</th>
                    <th class="text-left px-5 py-3.5 font-medium text-muted-text text-xs uppercase tracking-wider">{{ __('app.telegram_chat_id') }}</th>
                    <th class="text-left px-5 py-3.5 font-medium text-muted-text text-xs uppercase tracking-wider">{{ __('app.created_at') }}</th>
                    <th class="text-left px-5 py-3.5 font-medium text-muted-text text-xs uppercase tracking-wider">{{ __('app.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($members as $m)
                    <tr class="hover:bg-muted/40 transition-colors {{ in_array($m->telegram_chat_id, $duplicateMemberChatIds, true) ? 'bg-amber-50/50 dark:bg-amber-900/20' : '' }}">
                        <td class="px-5 py-3.5 text-muted-text tabular-nums">{{ $m->id }}</td>
                        <td class="px-5 py-3.5 font-medium text-primary">
                            {{ $m->baptism_name ?: '—' }}
                            @if(in_array($m->telegram_chat_id, $duplicateMemberChatIds, true))
                                <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">{{ __('app.duplicate') }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-secondary uppercase text-xs">{{ $m->locale }}</td>
                        <td class="px-5 py-3.5 font-mono text-xs text-secondary">{{ $m->telegram_chat_id }}</td>
                        <td class="px-5 py-3.5 text-secondary text-xs">{{ $m->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-5 py-3.5">
                            <a href="{{ route('admin.members.show', $m) }}" class="text-accent font-semibold text-xs hover:underline">{{ __('app.view') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-muted-text text-sm">{{ __('app.telegram_no_members_linked') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($members->hasPages())
        <div class="px-5 py-4 border-t border-border">
            {{ $members->links() }}
        </div>
    @endif
</div>
@endsection
