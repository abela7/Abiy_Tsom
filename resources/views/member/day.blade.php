@extends('layouts.member')

@php
    $locale = app()->getLocale();
    $publicPreview = (bool) ($publicPreview ?? false);
    $backUrl = $publicPreview ? route('home') : route('member.calendar');
    $weekName = $daily->weeklyTheme ? (localized($daily->weeklyTheme, 'name') ?? $daily->weeklyTheme->name_en ?? '-') : '';
    $dayTitle = localized($daily, 'day_title') ?? __('app.day_x', ['day' => $daily->day_number]);
    $sinksarUrl = $daily->sinksarUrl($locale);
    $shareTitle = $weekName ? ($weekName . ' - ' . $dayTitle) : $dayTitle;
    $shareDescription = __('app.share_day_description');
    // Use public share URL so social crawlers can read OG meta tags
    $shareUrl = route('share.day', $daily);
@endphp

@section('title', $shareTitle . ' - ' . __('app.app_name'))

@section('og_title', $shareTitle)
@section('og_description', $shareDescription)

@section('content')
<div x-data="dayPage()" class="px-4 pt-4 space-y-4">

    {{-- "Copied!" toast --}}
    <div x-show="linkCopied"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         x-cloak
         class="fixed top-4 left-1/2 -translate-x-1/2 z-[100] px-4 py-2.5 bg-success text-white text-sm font-semibold rounded-xl shadow-lg flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ __('app.link_copied') }}
    </div>

    {{-- Back + day info + share --}}
    <div class="flex items-center gap-3">
        <a href="{{ $backUrl }}" class="p-2 rounded-lg bg-muted shrink-0">
            <svg class="w-5 h-5 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div class="flex-1 min-w-0">
            <h1 class="text-lg font-bold text-primary">
                {{ __('app.day_of', ['day' => $daily->day_number, 'total' => 55]) }}
            </h1>
            <p class="text-xs text-muted-text">{{ $daily->date->locale('en')->translatedFormat('l, F j, Y') }}</p>
        </div>
        <div class="relative shrink-0" x-data="{ shareOpen: false }" @click.outside="shareOpen = false">
            <button type="button"
                    @click="shareOpen = !shareOpen"
                    class="p-2.5 rounded-xl bg-accent/10 hover:bg-accent/20 transition touch-manipulation"
                    :aria-label="'{{ __('app.share') }}'">
                <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                </svg>
            </button>
            {{-- Share dropdown --}}
            <div x-show="shareOpen" x-transition
                 x-cloak
                 class="absolute right-0 top-full mt-2 w-44 bg-card rounded-xl shadow-xl border border-border z-50 overflow-hidden">
                <button type="button"
                        @click="shareOpen = false; shareDay()"
                        class="flex items-center gap-3 w-full px-4 py-3 text-sm text-primary hover:bg-muted transition text-left">
                    <svg class="w-4 h-4 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                    </svg>
                    {{ __('app.share') }}
                </button>
                <button type="button"
                        @click="shareOpen = false; copyLink()"
                        class="flex items-center gap-3 w-full px-4 py-3 text-sm text-primary hover:bg-muted transition text-left border-t border-border">
                    <svg class="w-4 h-4 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                    </svg>
                    {{ __('app.copy_link_btn') }}
                </button>
            </div>
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
                @php
                    $mezmurUrl = $mezmur->mediaUrl($locale);
                @endphp
                @if($mezmurUrl)
                            <x-embedded-media :url="$mezmurUrl" play-label="{{ __('app.listen') }}" :open-label="__('app.open_in_youtube')" />
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
        @if($sinksarUrl)
            <x-embedded-media :url="$sinksarUrl" play-label="{{ __('app.listen') }}" :open-label="__('app.open_in_youtube')" />
        @endif
    </div>
    @endif

    {{-- Spiritual books (multiple per day) --}}
    @if($daily->books && $daily->books->isNotEmpty())
    <div class="space-y-3">
        <h3 class="font-semibold text-sm text-book">{{ __('app.spiritual_book') }}</h3>
        @foreach($daily->books as $book)
                @php
                    $bookUrl = $book->mediaUrl($locale);
                @endphp
                @if(localized($book, 'title'))
            <div class="bg-card rounded-2xl p-4 shadow-sm border border-border">
                <p class="font-medium text-primary">{{ localized($book, 'title') }}</p>
                @if(localized($book, 'description'))
                    <p class="text-sm text-muted-text mt-1 leading-relaxed">{{ localized($book, 'description') }}</p>
                @endif
                @if($bookUrl)
                    <a href="{{ $bookUrl }}" target="_blank" rel="noopener" class="text-sm text-accent font-medium mt-2 inline-block">{{ __('app.read_more') }} &rarr;</a>
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
                @php
                    $refUrl = $ref->mediaUrl($locale);
                @endphp
                @if ($refUrl)
                @php
                $refType = $ref->type ?? 'website';
                $btnLabel = match($refType) {
                    'video' => __('app.view_video'),
                    'file' => __('app.view_file'),
                    default => __('app.read_more'),
                };
            @endphp
            <a href="{{ $refUrl }}" target="_blank" rel="noopener"
               class="flex items-center justify-between gap-2 p-3 rounded-xl bg-muted hover:bg-border transition">
                <span class="text-sm font-medium text-primary">{{ localized($ref, 'name') }}</span>
                <span class="shrink-0 px-3 py-1 bg-accent text-on-accent rounded-lg text-xs font-medium">{{ $btnLabel }}</span>
            </a>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    {{-- Bottom share prompt (appears when user scrolls near bottom) --}}
    <div x-ref="bottomSentinel" class="h-0"></div>
    <div x-show="showSharePrompt && !sharePromptDismissed"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-4"
         x-cloak
         class="flex items-center justify-between gap-3 p-4 rounded-2xl bg-accent/10 border border-accent/20">
        <p class="text-sm font-medium text-primary flex-1 min-w-0">{{ __('app.share_prompt_message') }}</p>
        <div class="flex items-center gap-2 shrink-0">
            <button type="button"
                    @click="shareDay()"
                    class="px-4 py-2 bg-accent text-on-accent rounded-xl text-sm font-semibold hover:bg-accent-hover transition touch-manipulation">
                {{ __('app.share_btn') }}
            </button>
            <button type="button"
                    @click="copyLink()"
                    class="p-2 rounded-xl bg-accent/10 hover:bg-accent/20 transition touch-manipulation"
                    :aria-label="'{{ __('app.copy_link_btn') }}'">
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                </svg>
            </button>
            <button type="button"
                    @click="sharePromptDismissed = true"
                    class="p-1.5 rounded-lg hover:bg-muted transition touch-manipulation"
                    aria-label="{{ __('app.close') }}">
                <svg class="w-4 h-4 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    {{-- Checklist (show when there are activities, custom activities, or member can add) --}}
    @php
        $customChecklistCompleted = ($customChecklist ?? collect())->mapWithKeys(fn ($c) => [(string) $c->member_custom_activity_id => $c->completed])->all();
    @endphp
    @if(!$publicPreview && ($activities->isNotEmpty() || ($customActivities ?? collect())->isNotEmpty() || $member))
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
                        {{ localized($activity, 'name') }}
                    </span>
                </label>
            @endforeach
            <template x-for="activity in customActivities" :key="activity.id">
                <label class="flex items-center gap-3 p-3.5 rounded-xl cursor-pointer transition-all duration-200"
                       :class="customChecklistCompleted[activity.id] ? 'bg-success-bg/50 border border-success/30' : 'bg-muted hover:bg-border border border-transparent'">
                    <input type="checkbox" :checked="customChecklistCompleted[activity.id]"
                           @change="toggleCustomChecklist({{ $daily->id }}, activity.id, $event.target.checked); customChecklistCompleted[activity.id] = $event.target.checked; $dispatch('checklist-updated')"
                           class="w-5 h-5 rounded-md border-2 border-border accent-success focus:ring-2 focus:ring-success focus:ring-offset-0">
                    <span class="text-sm font-semibold block min-w-0 truncate" :class="customChecklistCompleted[activity.id] ? 'line-through text-muted-text' : 'text-primary'" x-text="activity.name"></span>
                </label>
            </template>
        </div>
        @if($member)
        <div class="mt-4 pt-4 border-t border-border">
            <p class="text-xs text-muted-text mb-3">{{ __('app.custom_activities_desc') }}</p>
            <form @submit.prevent="addActivity().then(() => $dispatch('checklist-updated'))" class="flex flex-wrap gap-2">
                <input type="text" x-model="addActivityName" maxlength="255"
                       :placeholder="'{{ __('app.custom_activity_placeholder') }}'"
                       class="min-w-0 flex-1 basis-24 px-4 py-2.5 border border-border rounded-xl bg-muted text-primary text-sm outline-none focus:ring-2 focus:ring-accent">
                <button type="submit" :disabled="!addActivityName.trim() || addActivityLoading"
                        class="shrink-0 px-4 py-2.5 bg-accent text-on-accent rounded-xl font-medium text-sm disabled:opacity-50 transition">
                    <span x-show="!addActivityLoading">{{ __('app.add_activity_day_btn') }}</span>
                    <span x-show="addActivityLoading" x-cloak>{{ __('app.loading') }}...</span>
                </button>
            </form>
            <p x-show="addActivityMsg" x-text="addActivityMsg" class="text-sm mt-2" :class="addActivityMsgError ? 'text-error' : 'text-success'"></p>
        </div>
        @endif
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function dayPage() {
    return {
        showSharePrompt: false,
        sharePromptDismissed: false,
        linkCopied: false,
        _observer: null,

        shareTitle: @js($shareTitle),
        shareDescription: @js($shareDescription),
        shareUrl: @js($shareUrl),

        customActivities: @js(($customActivities ?? collect())->values()->all()),
        customChecklistCompleted: @js($customChecklistCompleted ?? []),

        addActivityName: '',
        addActivityLoading: false,
        addActivityMsg: '',
        addActivityMsgError: false,

        init() {
            this.$nextTick(() => {
                const sentinel = this.$refs.bottomSentinel;
                if (!sentinel) return;
                this._observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting && !this.sharePromptDismissed) {
                            this.showSharePrompt = true;
                        }
                    });
                }, { threshold: 0.1 });
                this._observer.observe(sentinel);
            });
        },

        destroy() {
            if (this._observer) this._observer.disconnect();
        },

        async shareDay() {
            if (navigator.share) {
                try {
                    await navigator.share({
                        text: this.shareTitle + '\n' + this.shareDescription + '\n' + this.shareUrl,
                    });
                } catch (_e) {
                    // User cancelled or share failed
                }
            } else {
                this.copyLink();
            }
        },

        async copyLink() {
            try {
                await navigator.clipboard.writeText(this.shareUrl);
            } catch (_e) {
                const ta = document.createElement('textarea');
                ta.value = this.shareUrl;
                ta.style.cssText = 'position:fixed;opacity:0';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
            }
            this.linkCopied = true;
            setTimeout(() => { this.linkCopied = false; }, 2000);
        },

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
        },

        async addActivity() {
            const name = this.addActivityName.trim();
            if (!name || this.addActivityLoading) return;
            this.addActivityLoading = true;
            this.addActivityMsg = '';
            const data = await AbiyTsom.api('/api/member/custom-activities', { name });
            this.addActivityLoading = false;
            if (data.success) {
                this.customActivities.push(data.activity);
                this.customChecklistCompleted = { ...this.customChecklistCompleted, [data.activity.id]: false };
                this.addActivityName = '';
                this.addActivityMsg = '{{ __("app.custom_activity_added") }}';
                this.addActivityMsgError = false;
                setTimeout(() => { this.addActivityMsg = ''; }, 3000);
            } else {
                this.addActivityMsg = data.message || '{{ __("app.failed_to_add") }}';
                this.addActivityMsgError = true;
            }
        }
    };
}
</script>
@endpush
