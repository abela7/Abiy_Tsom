@extends('layouts.member')

@section('title', __('app.day_page_title', ['day' => $daily->day_number]) . ' - ' . __('app.app_name'))

@section('content')
<div x-data="dayPage()" class="px-4 pt-4 space-y-4">

    {{-- Back + day info --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('member.calendar') }}" class="p-2 rounded-lg bg-muted">
            <svg class="w-5 h-5 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-lg font-bold text-primary">
                {{ __('app.day_of', ['day' => $daily->day_number, 'total' => 55]) }}
            </h1>
            <p class="text-xs text-muted-text">{{ $daily->date->locale('en')->translatedFormat('l, F j, Y') }}</p>
        </div>
    </div>

    {{-- Weekly theme badge --}}
    @if($daily->weeklyTheme)
    <div class="bg-accent/10 border border-accent/20 rounded-xl px-3 py-2">
        <span class="text-xs font-semibold text-accent">
            {{ __('app.week', ['number' => $daily->weeklyTheme->week_number]) }} &mdash; {{ localized($daily->weeklyTheme, 'name') ?? $daily->weeklyTheme->name_en ?? $daily->weeklyTheme->name_geez ?? '-' }} ({{ app()->getLocale() === 'am' && $daily->weeklyTheme->meaning_am ? $daily->weeklyTheme->meaning_am : $daily->weeklyTheme->meaning }})
        </span>
    </div>
    @endif

    {{-- Day title --}}
    @if(localized($daily, 'day_title'))
        <h2 class="text-lg font-semibold text-primary">{{ localized($daily, 'day_title') }}</h2>
    @endif

    {{-- Bible Reading --}}
    @if(localized($daily, 'bible_reference'))
    @php
        $bibleText = localized($daily, 'bible_text');
    @endphp
    <div class="bg-card rounded-2xl p-4 shadow-sm border border-border">
        <h3 class="font-semibold text-sm text-accent mb-1">{{ __('app.bible_reading') }}</h3>
        <p class="font-medium text-primary">{{ localized($daily, 'bible_reference') }}</p>
        @if(localized($daily, 'bible_summary'))
            <p class="text-sm text-muted-text mt-2 leading-relaxed">{{ localized($daily, 'bible_summary') }}</p>
        @endif
        @if($bibleText)
            <div class="mt-2" x-data="{ open: false }">
                <button type="button"
                        @click="open = !open"
                        class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-accent/10 text-accent font-medium text-sm hover:bg-accent/20 transition">
                    <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    <span x-text="open ? '{{ __('app.close') }}' : '{{ __('app.read') }}'"></span>
                </button>
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     x-cloak
                     class="mt-3 pt-3 border-t border-border">
                    <p class="text-sm text-secondary leading-relaxed whitespace-pre-wrap">{{ $bibleText }}</p>
                </div>
            </div>
        @endif
    </div>
    @endif

    {{-- Mezmur (multiple) — exclusive accordion: when one opens, others collapse --}}
    @if($daily->mezmurs->isNotEmpty())
    <div class="bg-card rounded-2xl p-4 shadow-sm border border-border" x-data="{ openId: null }">
        <h3 class="font-semibold text-sm text-accent-secondary mb-3">{{ __('app.mezmur') }}</h3>
        <div class="space-y-2">
            @foreach($daily->mezmurs as $mezmur)
            <div class="rounded-xl overflow-hidden" :class="openId === {{ $mezmur->id }} ? 'ring-2 ring-accent-secondary' : ''">
                <button type="button"
                        @click="openId = openId === {{ $mezmur->id }} ? null : {{ $mezmur->id }}"
                        class="w-full flex items-center justify-between gap-2 py-3 px-4 rounded-xl bg-accent-secondary/10 text-left hover:bg-accent-secondary/20 transition">
                    <div class="flex items-center gap-2 min-w-0">
                        <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="openId === {{ $mezmur->id }} ? 'rotate-90' : ''" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                        <span class="font-medium text-primary truncate">{{ localized($mezmur, 'title') }}</span>
                    </div>
                    <span class="text-sm text-muted-text shrink-0">{{ __('app.listen') }}</span>
                </button>
                <div x-show="openId === {{ $mezmur->id }}"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     x-cloak
                     class="border-t border-accent-secondary/20 bg-muted/30">
                    <div class="p-3 space-y-2">
                        @if(localized($mezmur, 'description'))
                            <p class="text-sm text-muted-text leading-relaxed">{{ localized($mezmur, 'description') }}</p>
                        @endif
                        @if($mezmur->url)
                            <x-embedded-media :url="$mezmur->url" play-label="{{ __('app.listen') }}" :open-label="__('app.open_in_youtube')" />
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Sinksar (Synaxarium) — same layout as Mezmur with YouTube/video link --}}
    @if(localized($daily, 'sinksar_title'))
    <div class="bg-card rounded-2xl p-4 shadow-sm border border-border">
        <h3 class="font-semibold text-sm text-sinksar mb-1">{{ __('app.sinksar') }}</h3>
        <p class="font-medium text-primary">{{ localized($daily, 'sinksar_title') }}</p>
        @if(localized($daily, 'sinksar_description'))
            <p class="text-sm text-muted-text mt-2 leading-relaxed">{{ localized($daily, 'sinksar_description') }}</p>
        @endif
        @if($daily->sinksar_url)
            <x-embedded-media :url="$daily->sinksar_url" play-label="{{ __('app.listen') }}" :open-label="__('app.open_in_youtube')" />
        @endif
    </div>
    @endif

    {{-- Spiritual books --}}
    @if($daily->books && $daily->books->isNotEmpty())
    <div class="space-y-3">
        <h3 class="font-semibold text-sm text-book">{{ __('app.spiritual_book') }}</h3>
        @foreach($daily->books as $book)
            @if(localized($book, 'title'))
            <div class="bg-card rounded-2xl p-4 shadow-sm border border-border">
                <p class="font-medium text-primary">{{ localized($book, 'title') }}</p>
                @if(localized($book, 'description'))
                    <p class="text-sm text-muted-text mt-1 leading-relaxed">{{ localized($book, 'description') }}</p>
                @endif
                @if($book->url)
                    <a href="{{ $book->url }}" target="_blank" rel="noopener" class="text-sm text-accent font-medium mt-2 inline-block">{{ __('app.read_more') }} &rarr;</a>
                @endif
            </div>
            @endif
        @endforeach
    </div>
    @endif

    {{-- Reflection --}}
    @if(localized($daily, 'reflection'))
    <div class="bg-reflection-bg border border-reflection-border rounded-2xl p-4">
        <h3 class="font-semibold text-sm text-primary mb-2">{{ __('app.reflection') }}</h3>
        <p class="text-sm text-secondary leading-relaxed">{{ localized($daily, 'reflection') }}</p>
    </div>
    @endif

    {{-- References (know more) — accordion with name + Read more per link --}}
    @if($daily->references->isNotEmpty())
    <div class="bg-card rounded-2xl p-4 shadow-sm border border-border" x-data="{ open: false }">
        <button type="button"
                @click="open = !open"
                class="w-full flex items-center justify-between gap-2 py-2 text-left">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z"/>
                </svg>
                <h3 class="font-semibold text-sm text-primary">{{ __('app.references') }}</h3>
            </div>
            <span class="text-sm text-muted-text" x-text="open ? '{{ __('app.close') }}' : ''"></span>
        </button>
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             x-cloak
             class="mt-3 pt-3 border-t border-border space-y-2">
            @foreach($daily->references as $ref)
            <a href="{{ $ref->url }}" target="_blank" rel="noopener"
               class="flex items-center justify-between gap-2 p-3 rounded-xl bg-muted hover:bg-border transition">
                <span class="text-sm font-medium text-primary">{{ localized($ref, 'name') }}</span>
                <span class="shrink-0 px-3 py-1 bg-accent text-on-accent rounded-lg text-xs font-medium">{{ __('app.read_more') }}</span>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Checklist --}}
    @if($activities->isNotEmpty() || ($customActivities ?? collect())->isNotEmpty())
    <div class="rounded-2xl p-5 shadow-sm border-2 transition-all duration-300"
         x-data="{
             allDone: false,
             checkAllDone() {
                 this.$nextTick(() => {
                     const cbs = this.$refs?.checklistItems?.querySelectorAll('input[type=checkbox]');
                     this.allDone = cbs?.length > 0 && Array.from(cbs).every(c => c.checked);
                 });
             }
         }"
         x-init="$nextTick(() => checkAllDone())"
         @checklist-updated="checkAllDone()"
         :class="allDone ? 'bg-success-bg/30 border-success ring-2 ring-success/50' : 'bg-card border-border'">
        <div class="flex items-center justify-between gap-3 mb-4">
            <h3 class="font-bold text-sm text-primary">{{ __('app.checklist') }}</h3>
            <p x-show="allDone" x-transition class="text-sm font-bold text-success flex items-center gap-1.5">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ __('app.well_done') }}
            </p>
        </div>
        <div class="space-y-2.5" x-ref="checklistItems">
            @foreach($activities as $activity)
                <label class="flex items-center gap-3 p-3.5 rounded-xl cursor-pointer transition-all duration-200"
                       :class="checked ? 'bg-success-bg/50 border border-success/30' : 'bg-muted hover:bg-border border border-transparent'"
                       x-data="{ checked: {{ isset($checklist[$activity->id]) && $checklist[$activity->id]->completed ? 'true' : 'false' }} }">
                    <input type="checkbox" x-model="checked"
                           @change="toggleChecklist({{ $daily->id }}, {{ $activity->id }}, checked); $dispatch('checklist-updated')"
                           class="w-5 h-5 rounded-md border-2 border-border accent-success focus:ring-2 focus:ring-success focus:ring-offset-0">
                    <span class="text-sm font-semibold" :class="checked ? 'line-through text-muted-text' : 'text-primary'">
                        {{ $activity->name }}
                    </span>
                </label>
            @endforeach
            @foreach($customActivities ?? [] as $customActivity)
                <label class="flex items-center gap-3 p-3.5 rounded-xl cursor-pointer transition-all duration-200"
                       :class="checked ? 'bg-success-bg/50 border border-success/30' : 'bg-muted hover:bg-border border border-transparent'"
                       x-data="{ checked: {{ isset($customChecklist[$customActivity->id]) && $customChecklist[$customActivity->id]->completed ? 'true' : 'false' }} }">
                    <input type="checkbox" x-model="checked"
                           @change="toggleCustomChecklist({{ $daily->id }}, {{ $customActivity->id }}, checked); $dispatch('checklist-updated')"
                           class="w-5 h-5 rounded-md border-2 border-border accent-success focus:ring-2 focus:ring-success focus:ring-offset-0">
                    <span class="text-sm font-semibold" :class="checked ? 'line-through text-muted-text' : 'text-primary'">
                        {{ $customActivity->name }}
                    </span>
                </label>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function dayPage() {
    return {
        async toggleChecklist(dailyContentId, activityId, completed) {
            await AbiyTsom.api('/api/member/checklist/toggle', {
                daily_content_id: dailyContentId,
                activity_id: activityId,
                completed: completed,
            });
        },
        async toggleCustomChecklist(dailyContentId, customActivityId, completed) {
            await AbiyTsom.api('/api/member/checklist/custom-toggle', {
                daily_content_id: dailyContentId,
                member_custom_activity_id: customActivityId,
                completed: completed,
            });
        }
    };
}
</script>
@endpush
