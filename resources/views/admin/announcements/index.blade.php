@extends('layouts.admin')
@section('title', __('app.announcements'))

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-primary">{{ __('app.announcements') }}</h1>
    <a href="{{ route('admin.announcements.create') }}"
       class="px-4 py-2 bg-accent text-on-accent rounded-lg text-sm font-medium hover:bg-accent-hover transition">
        + {{ __('app.create') }}
    </a>
</div>

<div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-muted border-b border-border">
            <tr>
                <th class="text-left px-4 py-3 font-semibold text-secondary w-16">{{ __('app.photo') }}</th>
                <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.title_label') }}</th>
                <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.date_label') }}</th>
                <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-border">
            @forelse($announcements as $announcement)
                <tr class="hover:bg-muted">
                    <td class="px-4 py-3">
                        @if($announcement->photo)
                            <img src="{{ $announcement->photo_url }}" alt="" class="w-12 h-12 object-cover rounded-lg">
                        @else
                            <div class="w-12 h-12 bg-muted rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/>
                                </svg>
                            </div>
                        @endif
                    </td>
                    <td class="px-4 py-3 font-medium text-primary">{{ $announcement->title }}</td>
                    <td class="px-4 py-3 text-muted-text">{{ $announcement->created_at->format('M d, Y') }}</td>
                    <td class="px-4 py-3 flex gap-3">
                        <a href="{{ route('admin.announcements.edit', $announcement) }}" class="text-accent hover:underline">{{ __('app.edit') }}</a>
                        <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" onsubmit="return confirm('{{ __('app.confirm') }} {{ __('app.delete') }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-error hover:underline">{{ __('app.delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-12 text-center text-muted-text">
                        <p class="mb-2">{{ __('app.no_announcements') }}</p>
                        <a href="{{ route('admin.announcements.create') }}" class="text-accent font-medium">{{ __('app.create') }} {{ __('app.announcement') }}</a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
