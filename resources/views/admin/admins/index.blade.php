@extends('layouts.admin')
@section('title', __('app.manage_admins'))

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4 sm:mb-6">
    <h1 class="text-xl sm:text-2xl font-bold text-primary">{{ __('app.manage_admins') }}</h1>
    <a href="{{ route('admin.admins.create') }}"
       class="inline-flex items-center justify-center px-4 py-2.5 sm:py-2 bg-accent text-on-accent rounded-xl sm:rounded-lg text-sm font-medium hover:bg-accent-hover transition active:scale-[0.98] touch-manipulation">
        + {{ __('app.add_admin') }}
    </a>
</div>

@if(session('success'))
    <div class="mb-4 p-3 bg-success-bg border border-success text-success rounded-xl text-sm">{{ session('success') }}</div>
@endif

{{-- Mobile: card stack --}}
<div class="space-y-3 sm:hidden">
    @foreach($users as $user)
        <div class="bg-card rounded-xl border border-border p-4 shadow-sm">
            <div class="flex flex-col gap-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-semibold text-primary">{{ $user->name }}</span>
                    @if($user->is_super_admin)
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-accent-secondary/20 text-accent-secondary">{{ __('app.super_admin') }}</span>
                    @endif
                </div>
                <div class="text-sm text-secondary">{{ $user->username }}</div>
                <div class="text-sm text-muted-text">{{ $user->email ?? '—' }}</div>
                <div class="text-sm text-secondary">{{ __('app.' . $user->role) }}</div>
                <div class="flex flex-wrap gap-3 pt-2 border-t border-border mt-2">
                    <a href="{{ route('admin.admins.show', $user) }}"
                       class="text-accent font-medium hover:underline active:opacity-80">{{ __('app.view') }}</a>
                    <a href="{{ route('admin.admins.edit', $user) }}"
                       class="text-accent font-medium hover:underline active:opacity-80">{{ __('app.edit') }}</a>
                    @if(!$user->is_super_admin)
                        <form method="POST" action="{{ route('admin.admins.destroy', $user) }}"
                              class="inline"
                              onsubmit="return confirm('{{ __('app.confirm_delete_admin') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-error font-medium hover:underline active:opacity-80">{{ __('app.delete') }}</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Desktop: table --}}
<div class="hidden sm:block bg-card rounded-xl shadow-sm border border-border overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[600px]">
            <thead class="bg-muted border-b border-border">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.username') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.name') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.email') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.role') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @foreach($users as $user)
                    <tr class="hover:bg-muted">
                        <td class="px-4 py-3 font-medium">
                            {{ $user->username }}
                            @if($user->is_super_admin)
                                <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium bg-accent-secondary/20 text-accent-secondary">{{ __('app.super_admin') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-muted-text">{{ $user->email ?? '—' }}</td>
                        <td class="px-4 py-3">{{ __('app.' . $user->role) }}</td>
                        <td class="px-4 py-3 flex flex-wrap gap-3">
                            <a href="{{ route('admin.admins.show', $user) }}" class="text-accent hover:underline">{{ __('app.view') }}</a>
                            <a href="{{ route('admin.admins.edit', $user) }}" class="text-accent hover:underline">{{ __('app.edit') }}</a>
                            @if(!$user->is_super_admin)
                                <form method="POST" action="{{ route('admin.admins.destroy', $user) }}" onsubmit="return confirm('{{ __('app.confirm_delete_admin') }}')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-error hover:underline">{{ __('app.delete') }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
