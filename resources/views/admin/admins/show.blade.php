@extends('layouts.admin')
@section('title', __('app.view_admin'))

@section('content')
<div class="max-w-2xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-primary">{{ __('app.view_admin') }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.admins.edit', $admin) }}" class="px-4 py-2 bg-accent text-on-accent rounded-lg text-sm font-medium hover:bg-accent-hover transition">{{ __('app.edit') }}</a>
            <a href="{{ route('admin.admins.index') }}" class="px-4 py-2 bg-muted text-secondary rounded-lg text-sm font-medium hover:bg-border transition">{{ __('app.back') }}</a>
        </div>
    </div>

    <div class="bg-card rounded-xl shadow-sm border border-border p-6 space-y-4">
        <div>
            <p class="text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.username') }}</p>
            <p class="text-lg font-bold">{{ $admin->username }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.name') }}</p>
            <p class="text-lg">{{ $admin->name }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.email') }}</p>
            <p class="text-lg text-secondary">{{ $admin->email ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.role') }}</p>
            <p class="text-lg capitalize">
                @if($admin->is_super_admin)
                    <span class="px-2 py-0.5 rounded-full text-sm font-medium bg-accent-secondary/20 text-accent-secondary">{{ __('app.super_admin') }}</span>
                @else
                    {{ $admin->role }}
                @endif
            </p>
        </div>
        <div>
            <p class="text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.created') }}</p>
            <p class="text-sm text-muted-text">{{ $admin->created_at->format('d M Y H:i') }}</p>
        </div>
    </div>
</div>
@endsection
