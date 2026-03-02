@extends('layouts.member-guest')

@php
    $videoId = null;
    $youtubeUrl = $campaign->youtube_url;
    if ($youtubeUrl && preg_match('/(?:youtube\\.com\\/watch\\?v=|youtu\\.be\\/|youtube\\.com\\/embed\\/)([a-zA-Z0-9_-]{11})/', $youtubeUrl, $m)) {
        $videoId = $m[1];
    }
    $hasVideo = $videoId !== null;
@endphp

@section('title', $campaign->name)

@section('content')
<div class="relative isolate">
    <div class="absolute inset-x-0 -top-16 h-40 rounded-full blur-[120px] opacity-45 pointer-events-none"
         style="background: radial-gradient(circle at 50% 50%, var(--color-accent) 0%, transparent 58%);">
    </div>

    <div class="relative mx-auto w-full max-w-2xl"
         x-data="volunteerInviteFlow(@js($hasVideo), @js($campaign->slug), @js(route('volunteer.invite.track', $campaign->slug)), @js(route('volunteer.invite.decision', $campaign->slug)), @js(route('volunteer.invite.contact', $campaign->slug)), @js(route('volunteer.invite.show', $campaign->slug)))">
        <div class="rounded-[28px] border border-border bg-card/95 backdrop-blur-sm shadow-2xl overflow-hidden">
            <div class="px-4 py-5 sm:p-8">
                <div class="text-center">
                    <p class="inline-flex items-center gap-2 rounded-full bg-accent/10 text-accent px-3 py-1 text-[9px] sm:text-[10px] tracking-[0.22em] sm:tracking-[0.26em] font-bold uppercase">Volunteer invitation</p>
                    <h1 class="text-2xl sm:text-3xl mt-4 font-black text-primary leading-tight">
                        {{ $campaign->name }}
                    </h1>
                    <p class="text-sm text-muted-text mt-2 max-w-xl mx-auto min-h-[1.25rem] px-1" x-text="stepHint"></p>
                    <div class="mt-4 flex items-center justify-center gap-2 sm:gap-3 text-[10px] sm:text-[11px] tracking-[0.18em] uppercase font-semibold">
                        <span :class="step === 'video' ? 'text-primary' : 'text-muted-text'">Step 1</span>
                        <span class="text-muted-text" aria-hidden="true">&middot;</span>
                        <span :class="step === 'decision' ? 'text-primary' : 'text-muted-text'">Step 2</span>
                        <span class="text-muted-text" aria-hidden="true">&middot;</span>
                        <span :class="step === 'contact' || step === 'thanks' || step === 'contact-thank' ? 'text-primary' : 'text-muted-text'">Step 3</span>
                    </div>
                    <div class="mt-2 h-1.5 max-w-sm mx-auto w-full rounded-full bg-muted overflow-hidden">
                        <div class="h-full rounded-full bg-accent transition-all duration-300" :style="{ width: progressWidth }"></div>
                    </div>
                </div>

                {{-- STEP 1: Video --}}
                <div x-show="step === 'video'" x-transition.opacity class="mt-8">
                    @if($hasVideo)
                        <div class="aspect-video w-full rounded-2xl overflow-hidden border border-border bg-muted">
                            <div id="invite-youtube-player" class="w-full h-full bg-black"></div>
                        </div>
                        <div class="mt-5 space-y-2">
                            <button type="button"
                                    x-show="!playerReady"
                                    @click="initPlayer()"
                                    class="w-full h-12 rounded-xl bg-accent text-on-accent font-bold text-sm hover:bg-accent-hover active:scale-[0.985] transition flex items-center justify-center gap-2">
                                <span>Start the short video</span>
                                <svg class="w-4 h-4 animate-nudge-right" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12l7-5v10m7 0l-7-5"/>
                                </svg>
                            </button>
                            <p x-show="playerReady && !hasStarted" class="text-xs text-muted-text text-center">
                                We only count people who watch the full intro.
                            </p>
                            <p x-show="playerReady && hasStarted && !hasCompleted" class="text-xs text-accent text-center">
                                Great. Keep watching to continue.
                            </p>
                            <button type="button"
                                    x-show="playerReady && hasCompleted"
                                    @click="step = 'decision'"
                                    class="w-full h-12 rounded-xl border border-accent/30 bg-accent/10 text-accent font-semibold text-sm hover:bg-accent/20 active:scale-[0.985] transition flex items-center justify-center gap-2">
                                Continue to question
                                <svg class="w-4 h-4 animate-nudge-right" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 6h6v6H9l4 4-1 1.5L11 12H7v4H5V9.5l4-3.5z"/>
                                </svg>
                            </button>
                        </div>
                    @else
                        <div class="rounded-2xl border border-dashed border-border bg-muted/40 p-6 text-center">
                            <p class="text-sm text-muted-text">No video link is configured yet.</p>
                            <p class="text-sm text-primary mt-2">
                                We can still continue to the decision step.
                            </p>
                            <button type="button"
                                    @click="step = 'decision'"
                                    class="mt-4 h-12 px-6 rounded-xl bg-accent text-on-accent font-bold text-sm hover:bg-accent-hover active:scale-[0.985] transition flex items-center justify-center gap-2 mx-auto">
                                Continue
                                <svg class="w-4 h-4 animate-nudge-right" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 6h6v6H9l4 4-1 1.5L11 12H7v4H5V9.5l4-3.5z"/>
                                </svg>
                            </button>
                        </div>
                    @endif
                </div>

                {{-- STEP 2: decision --}}
                <div x-show="step === 'decision'" x-transition.opacity class="mt-8 space-y-4">
                    <p class="text-lg sm:text-xl font-black text-primary text-center">Do you understand the concept and are you willing to help?</p>
                    <p class="text-sm text-muted-text text-center">Choose the option that best matches you.</p>
                    <div class="grid gap-3">
                        <button type="button" @click="submitDecision('interested')"
                                class="w-full text-left rounded-2xl border border-accent/35 bg-accent/10 hover:bg-accent/20 px-5 py-4 text-sm font-semibold text-primary leading-relaxed transition active:scale-[0.985]">
                            I understand and am willing to help
                        </button>
                        <button type="button" @click="submitDecision('no_time')"
                                class="w-full text-left rounded-2xl border border-border bg-muted hover:bg-muted/80 px-5 py-4 text-sm font-semibold text-primary leading-relaxed transition active:scale-[0.985]">
                            I understand, but I don't have time to help
                        </button>
                        <button type="button" @click="submitDecision('not_interested')"
                                class="w-full text-left rounded-2xl border border-border bg-muted hover:bg-muted/80 px-5 py-4 text-sm font-semibold text-primary leading-relaxed transition active:scale-[0.985]">
                            I understand, but I do not want to be part of this
                        </button>
                    </div>
                </div>

                {{-- STEP 3A: Thanks + share --}}
                <div x-show="step === 'thanks'" x-transition.opacity class="mt-8 text-center space-y-5">
                    <div class="mx-auto w-16 h-16 rounded-full bg-accent/15 flex items-center justify-center">
                        <svg class="w-8 h-8 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-black text-primary">Thank you!</h2>
                    <p class="text-sm text-muted-text">Thank you for your honesty. Your answer helps us improve this campaign.</p>
                    <div class="grid gap-2.5 sm:grid-cols-2">
                        <button type="button" @click="shareInvite()"
                                class="h-12 rounded-xl border border-accent/30 bg-accent/10 text-accent font-bold hover:bg-accent/20 transition">
                            Share this invitation with someone else
                        </button>
                        <button type="button" @click="closePage()"
                                class="h-12 rounded-xl border border-border text-sm font-bold text-muted-text hover:bg-muted transition">
                            Close page
                        </button>
                    </div>
                    <p x-show="copyNotice" x-text="copyNotice" class="text-xs text-green-600 pt-1"></p>
                </div>

                {{-- STEP 3B: contact collection --}}
                <div x-show="step === 'contact'" x-transition.opacity class="mt-8 space-y-4">
                    <div class="text-center space-y-1">
                        <p class="text-lg font-black text-primary">Great, we'll contact you</p>
                        <p class="text-sm text-muted-text">Leave your name, phone number, and preferred method.</p>
                    </div>

                    <form class="space-y-3" @submit.prevent="submitContact()">
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-muted-text">Full name</label>
                            <input x-model="contactName"
                                   type="text"
                                   autocomplete="name"
                                   maxlength="150"
                                   required
                                   class="mt-1 w-full h-12 px-4 rounded-xl border border-border bg-surface text-primary focus:outline-none focus:ring-2 focus:ring-accent/40">
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-muted-text">Phone number</label>
                            <input x-model="phone"
                                   type="text"
                                   inputmode="tel"
                                   maxlength="40"
                                   autocomplete="tel"
                                   required
                                   class="mt-1 w-full h-12 px-4 rounded-xl border border-border bg-surface text-primary focus:outline-none focus:ring-2 focus:ring-accent/40">
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-muted-text">Preferred contact method</p>
                            <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-2">
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-border bg-surface cursor-pointer">
                                    <input type="radio" value="whatsapp" x-model="contactMethod" class="text-accent">
                                    <span class="text-sm text-primary">WhatsApp</span>
                                </label>
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-border bg-surface cursor-pointer">
                                    <input type="radio" value="phone" x-model="contactMethod" class="text-accent">
                                    <span class="text-sm text-primary">Regular phone call</span>
                                </label>
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-border bg-surface cursor-pointer">
                                    <input type="radio" value="telegram" x-model="contactMethod" class="text-accent">
                                    <span class="text-sm text-primary">Telegram</span>
                                </label>
                            </div>
                        </div>

                        <button type="submit"
                                :disabled="formSubmitting"
                                class="w-full h-12 rounded-xl bg-accent text-on-accent font-bold disabled:opacity-60 disabled:cursor-not-allowed transition active:scale-[0.985]">
                            <span x-show="!formSubmitting">Submit and send details</span>
                            <span x-show="formSubmitting">Saving...</span>
                        </button>
                    </form>
                    <p x-show="formError" x-text="formError" class="text-xs text-error"></p>
                </div>

                {{-- STEP 4: success --}}
                <div x-show="step === 'contact-thank'" x-transition.opacity class="mt-8 text-center space-y-4">
                    <p class="text-lg font-black text-primary">Thank you, your details are saved.</p>
                    <p class="text-sm text-muted-text">We will contact you as soon as possible.</p>
                    <button type="button" @click="closePage()"
                            class="h-12 rounded-xl border border-border text-sm font-bold text-muted-text hover:bg-muted transition">
                        Close page
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('volunteerInviteFlow', function (hasVideo, slug, trackUrl, decisionUrl, contactUrl, inviteUrl) {
        let player = null;
        let hasTrackedStarted = false;
        let hasTrackedCompleted = false;

        return {
            hasVideo,
            slug,
            trackUrl,
            decisionUrl,
            contactUrl,
            inviteUrl,
            step: hasVideo ? 'video' : 'decision',
            playerReady: false,
            formSubmitting: false,
            contactName: '',
            phone: '',
            contactMethod: 'whatsapp',
            hasStarted: false,
            hasCompleted: false,
            copyNotice: '',
            formError: '',
            get stepIndex() {
                if (this.step === 'video') {
                    return 1;
                }
                if (this.step === 'decision') {
                    return this.hasVideo ? 2 : 1;
                }
                return 2;
            },
            get progressWidth() {
                if (this.stepIndex === 1) {
                    return this.hasVideo ? '33%' : '50%';
                }
                if (this.stepIndex === 2) {
                    return this.hasVideo ? '66%' : '100%';
                }
                return '100%';
            },
            get stepHint() {
                if (this.step === 'video') {
                    return 'Watch the short intro, then answer one quick question.';
                }
                if (this.step === 'decision') {
                    return 'Choose one response and continue.';
                }
                if (this.step === 'contact') {
                    return 'Share your preferred contact details below.';
                }
                if (this.step === 'thanks') {
                    return 'Thank you for your time. Invite one more person if you wish.';
                }
                return 'Your details are saved. Thank you for helping.';
            },
            async postEvent(eventName) {
                try {
                    await window.fetch(this.trackUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({event: eventName}),
                        credentials: 'same-origin',
                    });
                } catch (error) {
                    // keep UX smooth even if endpoint is temporary unreachable
                }
            },
            init() {
                if (!this.hasVideo) {
                    this.step = 'decision';
                    return;
                }

                const script = document.createElement('script');
                script.src = 'https://www.youtube.com/iframe_api';
                script.async = true;
                if (!document.querySelector('script[src=\"https://www.youtube.com/iframe_api\"]')) {
                    document.head.appendChild(script);
                }

                window.onYouTubeIframeAPIReady = () => {
                    this.setupPlayer();
                };
            },
            setupPlayer() {
                if (this.playerReady || !window.YT || !window.YT.Player) {
                    return;
                }
                const el = document.getElementById('invite-youtube-player');
                if (!el) {
                    return;
                }
                player = new YT.Player(el, {
                    videoId: '{{ $videoId }}',
                    host: 'https://www.youtube.com',
                    playerVars: {
                        autoplay: 0,
                        controls: 1,
                        rel: 0,
                        modestbranding: 1,
                        playsinline: 1,
                    },
                    events: {
                        onReady: () => {
                            this.playerReady = true;
                        },
                        onStateChange: (event) => {
                            if (event.data === YT.PlayerState.PLAYING && !hasTrackedStarted) {
                                hasTrackedStarted = true;
                                this.hasStarted = true;
                                this.postEvent('video_started');
                            }
                            if (event.data === YT.PlayerState.ENDED && !hasTrackedCompleted) {
                                hasTrackedCompleted = true;
                                this.hasCompleted = true;
                                this.postEvent('video_completed');
                                this.step = 'decision';
                            }
                        },
                    }
                });
            },
            initPlayer() {
                this.setupPlayer();
            },
            async submitDecision(decision) {
                try {
                    const response = await window.fetch(this.decisionUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({decision})
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        alert(data.message || 'Unable to save your choice. Please try again.');
                        return;
                    }

                    if (decision === 'interested') {
                        this.step = 'contact';
                    } else {
                        this.step = 'thanks';
                    }
                } catch (error) {
                    alert('Network error. Please check your connection and try again.');
                }
            },
            async submitContact() {
                this.formError = '';
                if (!this.contactName.trim() || !this.phone.trim()) {
                    this.formError = 'Please fill full name and phone.';
                    return;
                }
                if (!this.contactMethod) {
                    this.formError = 'Please choose a preferred contact method.';
                    return;
                }
                this.formSubmitting = true;

                try {
                    const response = await window.fetch(this.contactUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            contact_name: this.contactName.trim(),
                            phone: this.phone.trim(),
                            contact_method: this.contactMethod,
                        })
                    });
                    const data = await response.json();
                    if (!response.ok) {
                        this.formError = data.message || 'Could not submit your contact details.';
                        return;
                    }
                    this.step = 'contact-thank';
                } catch (error) {
                    this.formError = 'Network error. Please retry.';
                } finally {
                    this.formSubmitting = false;
                }
            },
            async shareInvite() {
                const payload = {
                    title: 'Volunteer invitation',
                    text: 'Watch this short invitation and choose how you can support the campaign.',
                    url: this.inviteUrl,
                };
                if (navigator.share) {
                    try {
                        await navigator.share(payload);
                        this.copyNotice = 'Shared successfully.';
                        return;
                    } catch (error) {
                        // user cancelled or unsupported options
                    }
                }
                await this.copyInvite();
            },
            async copyInvite() {
                try {
                    await navigator.clipboard.writeText(this.inviteUrl);
                    this.copyNotice = 'Link copied to clipboard.';
                } catch (error) {
                    this.copyNotice = 'Could not copy the link.';
                }
            },
            closePage() {
                if (window.history.length > 1) {
                    window.close();
                    return;
                }
                window.location.href = '/';
            }
        };
    });
});
</script>
@end_push
