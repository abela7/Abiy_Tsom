@extends('layouts.admin')

@section('title', 'Volunteer Invitations')

@section('content')
@php
    $activeCampaign = $campaigns->firstWhere('is_active', true);
    $activeCampaignUrl = $activeCampaign ? route('volunteer.invite.show', $activeCampaign->slug) : null;
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-2xl sm:text-3xl font-black text-primary tracking-tight">Volunteer Invitations</h1>
            <p class="text-sm text-muted-text mt-2">Create invitation campaigns, add a YouTube intro, and track responses.</p>
        </div>
        <div class="flex flex-col sm:flex-row flex-wrap sm:flex-nowrap gap-2 w-full sm:w-auto">
            @if($activeCampaignUrl)
                <a href="{{ $activeCampaignUrl }}"
                   target="_blank"
                   rel="noopener"
                   class="inline-flex items-center justify-center rounded-xl border border-border bg-card px-4 py-2.5 text-sm font-semibold text-primary hover:bg-muted transition touch-manipulation w-full sm:w-auto">
                    Open active invitation
                    <svg class="ml-2 w-4 h-4 animate-nudge-right" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5-5 5m6-5H6"/>
                    </svg>
                </a>
            @endif
                <a href="{{ route('admin.suggestions.index') }}"
               class="inline-flex items-center justify-center rounded-xl bg-accent text-on-accent px-4 py-2.5 text-sm font-semibold hover:bg-accent-hover transition touch-manipulation">
                Back to content suggestions
            </a>
        </div>
    </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="bg-card rounded-xl p-4 border border-border shadow-sm">
                <p class="text-xs uppercase tracking-wider text-muted-text">Campaigns</p>
                <p class="text-2xl font-black text-primary mt-2">{{ $summary['total_campaigns'] }}</p>
            </div>
        <div class="bg-card rounded-xl p-4 border border-border shadow-sm">
            <p class="text-xs uppercase tracking-wider text-muted-text">Total Views</p>
            <p class="text-2xl font-black text-accent mt-2">{{ $summary['total_invitations'] }}</p>
        </div>
        <div class="bg-card rounded-xl p-4 border border-border shadow-sm">
            <p class="text-xs uppercase tracking-wider text-muted-text">Video started</p>
            <p class="text-2xl font-black text-primary mt-2">{{ $summary['video_started'] }}</p>
        </div>
        <div class="bg-card rounded-xl p-4 border border-border shadow-sm">
            <p class="text-xs uppercase tracking-wider text-muted-text">Decisions made</p>
            <p class="text-2xl font-black text-primary mt-2">{{ $summary['decisions_made'] }}</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-5 sm:gap-6">
        <section class="bg-card rounded-2xl border border-border shadow-sm p-4 sm:p-5 lg:col-span-1">
            <h2 class="text-sm uppercase tracking-wider text-muted-text font-bold">Create campaign</h2>
            <p class="text-xs text-muted-text mt-1.5">Create one invitation at a time and set one as active for the live link.</p>

            <form method="POST" action="{{ route('admin.volunteer-invitations.store') }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label for="campaign-name" class="block text-xs font-bold text-muted-text uppercase tracking-wider">Campaign name</label>
                    <input id="campaign-name"
                           name="name"
                           value="{{ old('name') }}"
                           required
                           class="mt-2 w-full h-11 px-3 rounded-xl border border-border bg-surface text-primary focus:outline-none focus:ring-2 focus:ring-accent/40"
                           placeholder="Spring 2026 invitation campaign">
                </div>
                <div>
                    <label for="campaign-slug" class="block text-xs font-bold text-muted-text uppercase tracking-wider">Slug</label>
                    <input id="campaign-slug"
                           name="slug"
                           value="{{ old('slug') }}"
                           required
                           pattern="[a-z0-9-]+"
                           class="mt-2 w-full h-11 px-3 rounded-xl border border-border bg-surface text-primary focus:outline-none focus:ring-2 focus:ring-accent/40"
                           placeholder="spring-2026">
                </div>
                <div>
                    <label for="campaign-youtube" class="block text-xs font-bold text-muted-text uppercase tracking-wider">YouTube link</label>
                    <input id="campaign-youtube"
                           name="youtube_url"
                           value="{{ old('youtube_url') }}"
                           type="url"
                           inputmode="url"
                           class="mt-2 w-full h-11 px-3 rounded-xl border border-border bg-surface text-primary focus:outline-none focus:ring-2 focus:ring-accent/40"
                           placeholder="https://www.youtube.com/watch?v=...">
                </div>
                <label class="flex items-center gap-2 text-sm text-secondary mt-1">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-border text-accent">
                    <span>Set as active campaign</span>
                </label>
                <button type="submit"
                        class="w-full h-11 rounded-xl bg-accent text-on-accent font-bold hover:bg-accent-hover transition active:scale-[0.985]">
                    Create campaign
                </button>
            </form>
        </section>

        <section class="lg:col-span-2 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-sm uppercase tracking-wider text-muted-text font-bold">Campaign list</h2>
                <span class="text-xs text-muted-text">Total: {{ $campaigns->count() }}</span>
            </div>

            @forelse($campaigns as $campaign)
                <article class="bg-card rounded-2xl border border-border shadow-sm p-4 sm:p-5">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-lg font-bold text-primary truncate">{{ $campaign->name }}</h3>
                                @if($campaign->is_active)
                                    <span class="shrink-0 text-[10px] font-bold uppercase px-2 py-1 rounded-full bg-accent/15 text-accent">Active</span>
                                @else
                                    <span class="shrink-0 text-[10px] font-bold uppercase px-2 py-1 rounded-full bg-muted text-muted-text">Draft</span>
                                @endif
                            </div>
                            <p class="text-xs text-muted-text mt-1">Slug: /invite/{{ $campaign->slug }}</p>
                            <p class="text-xs text-muted-text mt-0.5">
                                <span class="font-medium text-secondary">YouTube:</span>
                                @if($campaign->youtube_url)
                                    <a href="{{ $campaign->youtube_url }}" target="_blank" rel="noopener" class="text-accent underline hover:text-accent-hover break-all">
                                        {{ $campaign->youtube_url }}
                                    </a>
                                @else
                                    Not added yet
                                @endif
                            </p>
                        </div>
                        <div class="shrink-0 text-left sm:text-right">
                            <a href="{{ route('admin.volunteer-invitations.stats', $campaign) }}"
                               class="inline-flex items-center gap-1.5 rounded-lg border border-border px-3 py-2 text-xs font-semibold text-primary hover:bg-muted transition">
                                View Stats
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
                        <div class="rounded-xl bg-muted/50 border border-border px-3 py-2">
                            <p class="text-[11px] uppercase text-muted-text">Opened</p>
                            <p class="text-sm font-bold text-primary">{{ $campaign->submissions_count }}</p>
                        </div>
                        <div class="rounded-xl bg-muted/50 border border-border px-3 py-2">
                            <p class="text-[11px] uppercase text-muted-text">Started</p>
                            <p class="text-sm font-bold text-primary">{{ $campaign->video_started_count }}</p>
                        </div>
                        <div class="rounded-xl bg-muted/50 border border-border px-3 py-2">
                            <p class="text-[11px] uppercase text-muted-text">Completed</p>
                            <p class="text-sm font-bold text-primary">{{ $campaign->video_completed_count }}</p>
                        </div>
                        <div class="rounded-xl bg-muted/50 border border-border px-3 py-2">
                            <p class="text-[11px] uppercase text-muted-text">Contacted</p>
                            <p class="text-sm font-bold text-primary">{{ $campaign->contact_submitted_count }}</p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-[1fr_auto_auto_auto] sm:items-center" x-data="{ copied: false }">
                        <input id="invite-link-{{ $campaign->id }}"
                               class="flex-1 min-w-0 h-11 px-3 rounded-lg border border-border bg-muted text-xs text-secondary tabular-nums"
                               value="{{ route('volunteer.invite.show', $campaign->slug) }}"
                               readonly>
                        <button type="button"
                                @click="navigator.clipboard.writeText($el.previousElementSibling.value); copied = true; setTimeout(() => copied = false, 1800)"
                                class="px-3 h-11 rounded-lg border border-border text-xs font-semibold text-secondary hover:bg-muted transition touch-manipulation">
                            <span x-show="!copied">Copy link</span>
                            <span x-show="copied" x-cloak>Copied</span>
                        </button>

                        @if(!$campaign->is_active)
                            <form method="POST" action="{{ route('admin.volunteer-invitations.activate', $campaign) }}">
                                @csrf
                                    <button type="submit"
                                            class="w-full sm:w-auto px-3 h-11 rounded-lg border border-accent/40 text-xs font-semibold text-accent hover:bg-accent/10 transition touch-manipulation">
                                    Set active
                                </button>
                            </form>
                        @else
                            <span class="inline-flex h-11 min-h-[44px] items-center justify-center px-3 rounded-lg border border-green-500/30 bg-green-500/10 text-green-700 text-xs font-semibold">
                                Live
                            </span>
                        @endif

                        <form method="POST" action="{{ route('admin.volunteer-invitations.destroy', $campaign) }}"
                              onsubmit="return confirm('Delete this campaign and all data?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full sm:w-auto px-3 h-11 rounded-lg border border-red-500/40 text-xs font-semibold text-red-600 hover:bg-red-500/10 transition touch-manipulation">
                                Delete
                            </button>
                        </form>
                    </div>

                    <details class="mt-4 rounded-xl border border-border bg-muted/30">
                        <summary class="cursor-pointer px-3 py-3 text-sm font-bold text-primary list-none select-none">Quick edit</summary>
                        <form method="POST" action="{{ route('admin.volunteer-invitations.update', $campaign) }}" class="px-3 pb-3">
                            @csrf
                            @method('PUT')
                            <div class="grid sm:grid-cols-2 gap-3 mt-3">
                                <div>
                                    <label class="text-xs text-muted-text font-bold uppercase tracking-wider">Name</label>
                                    <input name="name"
                                           value="{{ old('name', $campaign->name) }}"
                                           class="mt-1.5 w-full h-11 px-3 rounded-lg border border-border bg-surface text-primary focus:outline-none focus:ring-2 focus:ring-accent/40">
                                </div>
                                <div>
                                    <label class="text-xs text-muted-text font-bold uppercase tracking-wider">Slug</label>
                                    <input name="slug"
                                           value="{{ old('slug', $campaign->slug) }}"
                                           pattern="[a-z0-9-]+"
                                           class="mt-1.5 w-full h-11 px-3 rounded-lg border border-border bg-surface text-primary focus:outline-none focus:ring-2 focus:ring-accent/40">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="text-xs text-muted-text font-bold uppercase tracking-wider">YouTube link</label>
                                <input name="youtube_url"
                                       type="url"
                                       inputmode="url"
                                       value="{{ old('youtube_url', $campaign->youtube_url) }}"
                                       class="mt-1.5 w-full h-11 px-3 rounded-lg border border-border bg-surface text-primary focus:outline-none focus:ring-2 focus:ring-accent/40">
                            </div>
                            <div class="mt-3">
                                <label class="flex items-center gap-2 text-sm text-secondary">
                                    <input type="checkbox" name="is_active" value="1" {{ $campaign->is_active ? 'checked' : '' }} class="rounded border-border text-accent">
                                    <span>Activate this campaign</span>
                                </label>
                            </div>
                            <div class="mt-3">
                                <button type="submit"
                                        class="w-full sm:w-auto px-4 h-11 rounded-lg bg-accent text-on-accent font-semibold hover:bg-accent-hover transition">
                                    Save changes
                                </button>
                            </div>
                        </form>
                    </details>
                </article>
            @empty
                <div class="bg-card rounded-2xl border border-border p-10 text-center">
                    <p class="text-sm text-muted-text">No campaigns yet. Create the first one from the form.</p>
                </div>
            @endforelse
        </section>
    </div>
</div>
@endsection
