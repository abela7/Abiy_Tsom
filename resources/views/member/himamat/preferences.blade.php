@extends('layouts.member')

@section('title', __('app.himamat_preferences_title').' - '.__('app.app_name'))

@section('content')
<div class="px-4 pt-4 pb-12 space-y-4" x-data="himamatPreferencesPage()">
    <section class="rounded-[2rem] border border-accent/15 bg-[linear-gradient(145deg,rgba(10,98,134,0.14),rgba(226,202,24,0.10))] p-5 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-accent">{{ __('app.himamat_eyebrow') }}</p>
                <h1 class="mt-2 text-2xl font-bold text-primary">{{ __('app.himamat_preferences_title') }}</h1>
                <p class="mt-2 text-sm leading-relaxed text-secondary">{{ __('app.himamat_preferences_intro') }}</p>
            </div>
            <div class="rounded-2xl border border-white/40 bg-white/50 px-3 py-2 text-right backdrop-blur">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_timezone_label') }}</p>
                <p class="mt-1 text-sm font-semibold text-primary">{{ __('app.himamat_timezone_value') }}</p>
            </div>
        </div>
    </section>

    <section class="rounded-[2rem] border border-border bg-card p-5 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-primary">{{ __('app.himamat_preferences_master_title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ __('app.himamat_preferences_master_body') }}</p>
            </div>
            <button type="button"
                    @click="preferences.enabled = !preferences.enabled"
                    class="relative inline-flex h-7 w-12 shrink-0 rounded-full border-2 border-transparent transition"
                    :class="preferences.enabled ? 'bg-accent' : 'bg-border'"
                    aria-label="{{ __('app.himamat_preferences_master_title') }}">
                <span class="inline-block h-6 w-6 rounded-full bg-white shadow-sm transition"
                      :class="preferences.enabled ? 'translate-x-5' : 'translate-x-0'"></span>
            </button>
        </div>

        <div class="mt-5 space-y-3">
            @foreach($slotDefinitions as $slot)
                <div class="rounded-2xl border border-border/80 bg-muted/50 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-text">{{ $slot['time'] }}</p>
                            <h3 class="mt-1 text-sm font-semibold text-primary">{{ $slot['title'] }}</h3>
                        </div>
                        <button type="button"
                                @click="toggleSlot('{{ $slot['key'] }}')"
                                :disabled="!preferences.enabled"
                                class="relative inline-flex h-7 w-12 shrink-0 rounded-full border-2 border-transparent transition disabled:opacity-50"
                                :class="preferences['{{ $slot['key'] }}_enabled'] ? 'bg-accent' : 'bg-border'">
                            <span class="inline-block h-6 w-6 rounded-full bg-white shadow-sm transition"
                                  :class="preferences['{{ $slot['key'] }}_enabled'] ? 'translate-x-5' : 'translate-x-0'"></span>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p x-show="message" x-text="message" class="text-sm" :class="messageError ? 'text-error' : 'text-success'"></p>
            <button type="button"
                    @click="save()"
                    :disabled="saving"
                    class="inline-flex items-center justify-center rounded-xl bg-accent px-4 py-3 text-sm font-semibold text-on-accent transition hover:bg-accent-hover disabled:opacity-60">
                <span x-show="!saving">{{ __('app.save') }}</span>
                <span x-show="saving">{{ __('app.loading') }}</span>
            </button>
        </div>
    </section>

    @if($days->isNotEmpty())
    <section class="rounded-[2rem] border border-border bg-card p-5 shadow-sm">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-primary">{{ __('app.himamat_day_view_title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ __('app.himamat_preferences_timeline_hint') }}</p>
            </div>
            <a href="{{ route('member.himamat.index') }}"
               class="inline-flex items-center justify-center rounded-xl border border-border bg-muted px-4 py-2.5 text-sm font-semibold text-secondary transition hover:bg-border">
                {{ __('app.himamat_open_today') }}
            </a>
        </div>

        <div class="mt-4 grid gap-3">
            @foreach($days as $day)
                <a href="{{ route('member.himamat.day', ['day' => $day->slug]) }}"
                   class="rounded-2xl border border-border/80 bg-muted/40 px-4 py-4 transition hover:border-accent/40 hover:bg-accent/5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ $day->date?->format('D, d M Y') }}</p>
                            <h3 class="mt-1 text-sm font-semibold text-primary">{{ localized($day, 'title') ?? $day->title_en }}</h3>
                        </div>
                        <span class="rounded-full bg-accent/10 px-2.5 py-1 text-[11px] font-semibold text-accent">
                            {{ $day->publishedSlots->count() }} {{ __('app.himamat_slots_label') }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
    @endif
</div>
@endsection

@push('scripts')
<script>
function himamatPreferencesPage() {
    return {
        saving: false,
        message: '',
        messageError: false,
        preferences: {
            enabled: {{ $preferences->enabled ? 'true' : 'false' }},
            intro_enabled: {{ $preferences->intro_enabled ? 'true' : 'false' }},
            third_enabled: {{ $preferences->third_enabled ? 'true' : 'false' }},
            sixth_enabled: {{ $preferences->sixth_enabled ? 'true' : 'false' }},
            ninth_enabled: {{ $preferences->ninth_enabled ? 'true' : 'false' }},
            eleventh_enabled: {{ $preferences->eleventh_enabled ? 'true' : 'false' }},
        },
        toggleSlot(key) {
            if (!this.preferences.enabled) return;

            const field = key + '_enabled';
            this.preferences[field] = !this.preferences[field];
        },
        async save() {
            this.saving = true;
            this.message = '';
            this.messageError = false;

            try {
                const response = await AbiyTsom.api('/api/member/himamat/preferences', this.preferences);
                if (response && response.success) {
                    this.message = response.message || '{{ __("app.himamat_preferences_saved") }}';
                    return;
                }

                this.message = response?.message || '{{ __("app.failed_to_save") }}';
                this.messageError = true;
            } catch (error) {
                this.message = '{{ __("app.failed_to_save") }}';
                this.messageError = true;
            } finally {
                this.saving = false;
                setTimeout(() => { this.message = ''; }, 4000);
            }
        },
    };
}
</script>
@endpush
