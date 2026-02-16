@extends('layouts.admin')
@section('title', __('app.translations'))

@section('content')
<div x-data="{ showAdd: false }">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="text-2xl font-bold text-primary">{{ __('app.translations') }}</h1>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('admin.translations.sync') }}" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-accent-secondary text-primary rounded-lg text-sm font-medium hover:opacity-90 transition">
                    {{ __('app.sync_translations') }}
                </button>
            </form>
            <button @click="showAdd = !showAdd" class="px-4 py-2 bg-accent text-on-accent rounded-lg text-sm font-medium hover:bg-accent-hover transition">{{ __('app.add_key') }}</button>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-success/20 border border-success rounded-lg text-success text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Section tabs: User | Admin --}}
    <div class="flex gap-2 mb-4 p-1 bg-muted rounded-xl w-fit">
        <a href="{{ route('admin.translations.index', ['section' => 'user', 'group' => array_key_first($sections['user'] ?? []) ?? 'onboarding']) }}"
           class="px-5 py-2.5 rounded-lg text-sm font-semibold transition {{ $section === 'user' ? 'bg-card text-primary shadow-sm border border-border' : 'text-muted-text hover:text-secondary' }}">
            {{ __('app.section_user') }}
        </a>
        <a href="{{ route('admin.translations.index', ['section' => 'admin', 'group' => array_key_first($sections['admin'] ?? []) ?? 'admin_login']) }}"
           class="px-5 py-2.5 rounded-lg text-sm font-semibold transition {{ $section === 'admin' ? 'bg-card text-primary shadow-sm border border-border' : 'text-muted-text hover:text-secondary' }}">
            {{ __('app.section_admin') }}
        </a>
    </div>

    {{-- Page tabs within section --}}
    <div class="mb-6">
        <p class="text-xs font-medium text-muted-text uppercase tracking-wider mb-2">
            {{ $section === 'user' ? __('app.pages_label') : __('app.admin_pages_label') }}
        </p>
        <div class="flex flex-wrap gap-2">
            @foreach($sections[$section] ?? [] as $g => $label)
                <a href="{{ route('admin.translations.index', ['section' => $section, 'group' => $g]) }}"
                   class="px-3 py-1.5 rounded-lg text-sm font-medium whitespace-nowrap transition {{ $group === $g ? 'bg-accent text-on-accent' : 'bg-muted text-secondary hover:bg-border' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
        @if(empty($sections[$section] ?? []))
            <span class="text-muted-text text-sm">{{ __('app.no_translation_groups') }}</span>
        @endif
    </div>

    {{-- Add new key form --}}
    <div x-show="showAdd" x-transition class="bg-card rounded-xl shadow-sm border border-border p-4 mb-6">
        <form method="POST" action="{{ route('admin.translations.store') }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
            @csrf
            <div>
                <label class="block text-xs font-medium text-secondary mb-1">{{ __('app.group_label') }}</label>
                <select name="group" required class="w-full px-3 py-2 border border-border rounded-lg text-sm outline-none focus:ring-2 focus:ring-accent">
                    @foreach($sections[$section] ?? [] as $g => $label)
                        <option value="{{ $g }}" {{ $group === $g ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-secondary mb-1">{{ __('app.key_label') }}</label>
                <input type="text" name="key" required placeholder="{{ __('app.key_placeholder') }}" class="w-full px-3 py-2 border border-border rounded-lg text-sm outline-none focus:ring-2 focus:ring-accent">
            </div>
            <div>
                <label class="block text-xs font-medium text-secondary mb-1">{{ __('app.english_label') }}</label>
                <input type="text" name="en" required class="w-full px-3 py-2 border border-border rounded-lg text-sm outline-none focus:ring-2 focus:ring-accent">
            </div>
            <div>
                <label class="block text-xs font-medium text-secondary mb-1">{{ __('app.amharic_label') }}</label>
                <input type="text" name="am" class="w-full px-3 py-2 border border-border rounded-lg text-sm outline-none focus:ring-2 focus:ring-accent" dir="auto">
            </div>
            <button type="submit" class="px-4 py-2 bg-accent-secondary text-primary rounded-lg font-medium text-sm hover:opacity-90 transition">{{ __('app.add') }}</button>
        </form>
    </div>

    {{-- Translation table --}}
    @if($enStrings->isNotEmpty())
        <form method="POST" action="{{ route('admin.translations.update') }}">
            @csrf @method('PUT')
            <input type="hidden" name="group" value="{{ $group }}">

            <div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-muted border-b border-border">
                        <tr>
                            <th class="text-left px-4 py-3 font-semibold text-secondary w-1/4">{{ __('app.key_label') }}</th>
                            <th class="text-left px-4 py-3 font-semibold text-secondary w-3/8">{{ __('app.english_label') }}</th>
                            <th class="text-left px-4 py-3 font-semibold text-secondary w-3/8">{{ __('app.amharic_label') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($enStrings as $key => $enItem)
                            <tr class="hover:bg-muted">
                                <td class="px-4 py-2">
                                    <code class="text-xs bg-muted px-1.5 py-0.5 rounded text-secondary">{{ $key }}</code>
                                    <input type="hidden" name="translations[{{ $loop->index }}][key]" value="{{ $key }}">
                                </td>
                                <td class="px-4 py-2">
                                    <input type="text" name="translations[{{ $loop->index }}][en]" value="{{ $enItem->value }}"
                                           class="w-full px-2 py-1.5 border border-border rounded-lg text-sm outline-none focus:ring-1 focus:ring-accent">
                                </td>
                                <td class="px-4 py-2">
                                    <input type="text" name="translations[{{ $loop->index }}][am]" value="{{ ($amStrings[$key] ?? null)?->value ?? '' }}"
                                           class="w-full px-2 py-1.5 border border-border rounded-lg text-sm outline-none focus:ring-1 focus:ring-accent" dir="auto">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <button type="submit" class="px-6 py-2.5 bg-accent text-on-accent rounded-lg font-medium hover:bg-accent-hover transition">{{ __('app.save') }}</button>
            </div>
        </form>
    @else
        <p class="text-muted-text text-center py-8">{{ __('app.no_translations_in_group') }}</p>
    @endif
</div>
@endsection
