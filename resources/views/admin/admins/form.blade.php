@extends('layouts.admin')
@section('title', $user ? __('app.edit_admin') : __('app.add_admin'))

@section('content')
<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-primary mb-6">{{ $user ? __('app.edit_admin') : __('app.add_admin') }}</h1>

    <form method="POST" action="{{ $user ? route('admin.admins.update', $user) : route('admin.admins.store') }}"
          class="bg-card rounded-xl shadow-sm border border-border p-6 space-y-4">
        @csrf
        @if($user) @method('PUT') @endif

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.name') }}</label>
            <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required
                   class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
            @error('name')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.username') }}</label>
            <input type="text" name="username" value="{{ old('username', $user->username ?? '') }}" required
                   autocomplete="username"
                   class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none"
                   {{ $user?->is_super_admin ? 'readonly' : '' }}>
            @error('username')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.email_optional') }}</label>
            <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}"
                   class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
            @error('email')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.password') }}</label>
            <input type="password" name="password" {{ $user ? '' : 'required' }}
                   autocomplete="{{ $user ? 'new-password' : 'new-password' }}"
                   class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none"
                   placeholder="{{ $user ? __('app.password_leave_blank') : '' }}">
            @if($user)<p class="text-xs text-muted-text mt-1">{{ __('app.password_leave_blank') }}</p>@endif
            @error('password')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.password_confirmation') }}</label>
            <input type="password" name="password_confirmation" {{ $user ? '' : 'required' }}
                   class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
        </div>

        @if(!$user || !$user->is_super_admin)
        <div>
            <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.role') }}</label>
            <select name="role" class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
                <option value="admin" {{ old('role', $user->role ?? 'admin') === 'admin' ? 'selected' : '' }}>{{ __('app.admin') }}</option>
                <option value="editor" {{ old('role', $user->role ?? '') === 'editor' ? 'selected' : '' }}>{{ __('app.editor') }}</option>
            </select>
        </div>
        @endif

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2.5 bg-accent text-on-accent rounded-lg font-medium hover:bg-accent-hover transition">{{ __('app.save') }}</button>
            <a href="{{ route('admin.admins.index') }}" class="px-6 py-2.5 bg-muted text-secondary rounded-lg font-medium hover:bg-border transition">{{ __('app.cancel') }}</a>
        </div>
    </form>
</div>
@endsection
