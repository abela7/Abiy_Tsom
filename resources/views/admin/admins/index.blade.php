@extends('layouts.admin')
@section('title', __('app.manage_admins'))

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-primary">{{ __('app.manage_admins') }}</h1>
    <a href="{{ route('admin.admins.create') }}" class="px-4 py-2 bg-accent text-on-accent rounded-lg text-sm font-medium hover:bg-accent-hover transition">+ {{ __('app.add_admin') }}</a>
</div>

@if(session('success'))
    <div class="mb-4 p-3 bg-success-bg border border-success text-success rounded-lg text-sm">{{ session('success') }}</div>
@endif

<div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden">
    <table class="w-full text-sm">
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
                    <td class="px-4 py-3 capitalize">{{ $user->role }}</td>
                    <td class="px-4 py-3 flex flex-wrap gap-3">
                        <a href="{{ route('admin.admins.show', $user) }}" class="text-accent hover:underline">{{ __('app.view') }}</a>
                        <a href="{{ route('admin.admins.edit', $user) }}" class="text-accent hover:underline">{{ __('app.edit') }}</a>
                        @if(!$user->is_super_admin)
                            <form method="POST" action="{{ route('admin.admins.destroy', $user) }}" onsubmit="return confirm('{{ __('app.confirm_delete_admin') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-error hover:underline">{{ __('app.delete') }}</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
