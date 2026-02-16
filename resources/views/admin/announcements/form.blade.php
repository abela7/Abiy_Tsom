@extends('layouts.admin')
@section('title', $announcement->exists ? __('app.edit') . ' ' . __('app.announcement') : __('app.create') . ' ' . __('app.announcement'))

@section('content')
<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-primary mb-6">
        {{ $announcement->exists ? __('app.edit') : __('app.create') }} {{ __('app.announcement') }}
    </h1>

    <form method="POST"
          action="{{ $announcement->exists ? route('admin.announcements.update', $announcement) : route('admin.announcements.store') }}"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf
        @if($announcement->exists)
            @method('PUT')
        @endif

        {{-- Photo --}}
        <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
            <label class="block text-sm font-medium text-secondary mb-2">{{ __('app.photo') }}</label>
            @if($announcement->photo)
                <div class="mb-3">
                    <img src="{{ $announcement->photo_url }}" alt="" class="w-32 h-32 object-cover rounded-lg border border-border">
                    <p class="text-xs text-muted-text mt-1">{{ __('app.current_photo') }}</p>
                </div>
            @endif
            <input type="file" name="photo" accept="image/*"
                   class="block w-full text-sm text-secondary file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-accent file:text-on-accent">
        </div>

        {{-- Title --}}
        <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
            <label for="title" class="block text-sm font-medium text-secondary mb-2">{{ __('app.title_label') }} *</label>
            <input type="text" name="title" id="title" value="{{ old('title', $announcement->title) }}"
                   required maxlength="255"
                   class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none bg-card text-primary">
        </div>

        {{-- Description --}}
        <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
            <label for="description" class="block text-sm font-medium text-secondary mb-2">{{ __('app.description_label') }}</label>
            <textarea name="description" id="description" rows="5" maxlength="5000"
                      class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none bg-card text-primary">{{ old('description', $announcement->description) }}</textarea>
        </div>

        {{-- YouTube video (optional) --}}
        <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
            <label for="youtube_url" class="block text-sm font-medium text-secondary mb-2">{{ __('app.youtube_url') }}</label>
            <input type="url" name="youtube_url" id="youtube_url" value="{{ old('youtube_url', $announcement->youtube_url) }}"
                   maxlength="500"
                   class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none bg-card text-primary"
                   placeholder="{{ __('app.youtube_url_placeholder') }}">
            <p class="mt-1 text-xs text-muted-text">{{ __('app.youtube_url_placeholder') }}</p>

            <div class="mt-4">
                <label class="block text-sm font-medium text-secondary mb-2">{{ __('app.youtube_position') }}</label>
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="youtube_position" value="top"
                               {{ old('youtube_position', $announcement->youtube_position) === 'top' ? 'checked' : '' }}>
                        <span class="text-sm text-secondary">{{ __('app.youtube_position_top') }}</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="youtube_position" value="end"
                               {{ old('youtube_position', $announcement->youtube_position) === 'end' ? 'checked' : '' }}>
                        <span class="text-sm text-secondary">{{ __('app.youtube_position_end') }}</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Button (optional) --}}
        @php $hasButton = old('button_enabled', $announcement->button_enabled); @endphp
        <div class="bg-card rounded-xl p-4 shadow-sm border border-border" x-data="{ buttonEnabled: {{ $hasButton ? 'true' : 'false' }} }">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="button_enabled" value="1"
                       {{ $hasButton ? 'checked' : '' }}
                       x-model="buttonEnabled">
                <span class="text-sm font-medium text-secondary">{{ __('app.show_action_button') }}</span>
            </label>

            <div x-show="buttonEnabled" x-transition class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="button_label" class="block text-sm font-medium text-secondary mb-1">{{ __('app.button_label') }}</label>
                    <input type="text" name="button_label" id="button_label" value="{{ old('button_label', $announcement->button_label) }}"
                           maxlength="100"
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none bg-card text-primary"
                           placeholder="{{ __('app.button_label_placeholder') }}">
                </div>
                <div>
                    <label for="button_url" class="block text-sm font-medium text-secondary mb-1">{{ __('app.button_url') }}</label>
                    <input type="url" name="button_url" id="button_url" value="{{ old('button_url', $announcement->button_url) }}"
                           maxlength="500"
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none bg-card text-primary"
                           placeholder="{{ __('app.url_placeholder') }}">
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2 bg-accent text-on-accent rounded-lg font-medium hover:bg-accent-hover transition">
                {{ $announcement->exists ? __('app.save') : __('app.create') }}
            </button>
            <a href="{{ route('admin.announcements.index') }}" class="px-6 py-2 border border-border rounded-lg font-medium hover:bg-muted transition">
                {{ __('app.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection
