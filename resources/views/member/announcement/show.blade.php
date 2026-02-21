@extends('layouts.member')

@section('title', $announcement->titleForLocale() . ' - ' . __('app.app_name'))

@section('content')
@php
    $announcementPhotoUrl = $announcement->photoUrlForLocale();
    $youtubePosition = $announcement->youtubePositionForLocale();
@endphp
<article class="min-h-screen bg-surface">
    {{-- Back link --}}
    <div class="sticky top-0 z-10 bg-surface/95 backdrop-blur supports-[backdrop-filter]:bg-surface/80 border-b border-border">
        <div class="max-w-2xl mx-auto px-4 py-3 flex items-center gap-2">
            <a href="{{ route('member.home') }}"
               class="p-2 -ml-2 rounded-lg text-muted-text hover:text-primary hover:bg-muted transition flex items-center gap-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span class="text-sm font-medium">{{ __('app.back') }}</span>
            </a>
        </div>
    </div>

    {{-- Hero image --}}
    @if($announcementPhotoUrl)
        <div class="relative w-full aspect-[16/9] sm:aspect-[21/9] overflow-hidden bg-muted">
            <img src="{{ $announcementPhotoUrl }}" alt=""
                 class="absolute inset-0 w-full h-full object-cover object-center">
            <div class="absolute inset-0 bg-gradient-to-t from-surface/60 via-transparent to-transparent"></div>
        </div>
    @endif

    {{-- Content --}}
    <div class="max-w-2xl mx-auto px-4 py-6 sm:py-8">
        <header class="mb-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-primary leading-tight tracking-tight">
                {{ $announcement->titleForLocale() }}
            </h1>
            <p class="mt-2 text-sm text-muted-text">
                {{ $announcement->created_at->locale('en')->translatedFormat('l, F j, Y') }}
            </p>
        </header>

        @if($announcement->hasYoutubeVideo() && $youtubePosition === 'top')
            @include('member.announcement._youtube-embed', ['announcement' => $announcement])
        @endif

        @if($announcement->descriptionForLocale())
            <div class="prose prose-sm sm:prose max-w-none">
                <div class="text-secondary leading-relaxed whitespace-pre-wrap">{{ $announcement->descriptionForLocale() }}</div>
            </div>
        @endif

        @if($announcement->hasYoutubeVideo() && $youtubePosition === 'end')
            @include('member.announcement._youtube-embed', ['announcement' => $announcement])
        @endif

        @if($announcement->hasButton())
            <div class="mt-8 pt-6 border-t border-border">
                <a href="{{ $announcement->buttonUrlForLocale() }}" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-accent text-on-accent rounded-xl text-sm font-semibold hover:bg-accent-hover transition active:scale-95">
                    {{ $announcement->buttonLabelForLocale() }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
            </div>
        @endif

        {{-- Back to home --}}
        <div class="mt-10">
            <a href="{{ route('member.home') }}"
               class="inline-flex items-center gap-2 text-sm text-accent font-medium hover:underline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ __('app.nav_home') }}
            </a>
        </div>
    </div>
</article>
@endsection
