@php
    $videoId = $announcement->youtubeVideoId();
@endphp
@if($videoId)
<div class="my-6 sm:my-8">
    <div class="aspect-video w-full rounded-xl overflow-hidden bg-muted shadow-lg">
        <iframe
            src="https://www.youtube.com/embed/{{ $videoId }}"
            title="{{ $announcement->titleForLocale() }}"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen
            class="w-full h-full"
        ></iframe>
    </div>
    <a href="{{ $announcement->youtube_url }}" target="_blank" rel="noopener"
       class="inline-flex items-center gap-1.5 mt-2 text-sm text-muted-text hover:text-accent transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        {{ __('app.open_in_youtube') }}
    </a>
</div>
@endif
