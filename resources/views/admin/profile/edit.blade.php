@extends('layouts.admin')

@section('title', __('app.my_profile'))

@section('content')
<div class="max-w-3xl space-y-6">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-primary">{{ __('app.my_profile') }}</h1>
        <p class="text-sm text-muted-text mt-1">{{ __('app.my_profile_help') }}</p>
    </div>

    <div class="bg-card rounded-xl border border-border p-4 sm:p-6 shadow-sm space-y-6">
        <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="block text-sm font-medium text-secondary mb-1.5">{{ __('app.name') }}</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}"
                           class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-secondary mb-1.5">{{ __('app.username') }}</label>
                    <input type="text" value="{{ $user->username }}" disabled
                           class="w-full px-3 py-2.5 rounded-xl border border-border bg-muted text-muted-text text-sm cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-sm font-medium text-secondary mb-1.5">{{ __('app.role') }}</label>
                    <input type="text" value="{{ ucfirst((string) $user->role) }}" disabled
                           class="w-full px-3 py-2.5 rounded-xl border border-border bg-muted text-muted-text text-sm cursor-not-allowed">
                </div>
            </div>

            <div class="border-t border-border pt-6 space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-primary">{{ __('app.change_password') }}</h2>
                    <p class="text-sm text-muted-text mt-1">{{ __('app.my_profile_password_help') }}</p>
                </div>

                <div>
                    <label for="current_password" class="block text-sm font-medium text-secondary mb-1.5">{{ __('app.current_password') }}</label>
                    <input id="current_password" type="password" name="current_password" autocomplete="current-password"
                           class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent">
                    <p class="text-xs text-muted-text mt-1">{{ __('app.current_password_help') }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="password" class="block text-sm font-medium text-secondary mb-1.5">{{ __('app.password') }}</label>
                        <input id="password" type="password" name="password" autocomplete="new-password"
                               class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent">
                        <p class="text-xs text-muted-text mt-1">{{ __('app.password_leave_blank') }}</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-secondary mb-1.5">{{ __('app.password_confirmation') }}</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"
                               class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent">
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-accent px-5 py-2.5 text-sm font-semibold text-on-accent transition hover:bg-accent-hover active:scale-[0.97]">
                    {{ __('app.save_changes') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
