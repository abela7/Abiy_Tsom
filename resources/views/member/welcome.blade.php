@extends('layouts.member-guest')

@section('title', __('app.app_name') . ' - ' . __('app.tagline'))

@section('content')
<div x-data="onboarding()"
     x-init="checkExisting()">

    {{-- Registration form --}}
    <div x-show="!hasToken" x-transition>
        <div class="bg-card rounded-2xl sm:rounded-3xl shadow-xl shadow-black/5 dark:shadow-black/20 p-6 sm:p-8 space-y-5 border border-border">
            <div class="text-center">
                <h2 class="text-lg font-semibold text-primary">{{ __('app.onboarding_title') }}</h2>
                <p class="text-sm text-muted-text mt-1">{{ __('app.onboarding_subtitle') }}</p>
            </div>

            <div>
                <label for="baptism_name" class="block text-sm font-semibold text-secondary mb-2">
                    {{ __('app.baptism_name') }}
                </label>
                <input type="text"
                       id="baptism_name"
                       x-model="baptismName"
                       :placeholder="'{{ __('app.baptism_name_placeholder') }}'"
                       class="w-full px-4 py-3.5 border border-border rounded-xl bg-muted/50 dark:bg-muted/30 text-primary placeholder:text-muted-text focus:ring-2 focus:ring-accent focus:border-accent outline-none transition text-base">
            </div>

            <button @click="register()"
                    :disabled="!baptismName.trim() || isLoading"
                    class="w-full py-3.5 bg-accent text-on-accent rounded-xl font-bold text-base hover:bg-accent-hover disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.98] transition shadow-lg shadow-accent/20">
                <span x-show="!isLoading">{{ __('app.start_journey') }}</span>
                <span x-show="isLoading">{{ __('app.loading') }}</span>
            </button>
        </div>
    </div>

    {{-- Redirect message (shown briefly when existing token found) --}}
    <div x-show="hasToken" x-transition class="text-center">
        <div class="animate-pulse">
            <p class="text-muted-text">{{ __('app.loading') }}</p>
        </div>
    </div>

    {{-- Language switcher --}}
    <div class="mt-6 flex items-center justify-center gap-2 text-sm">
        <span class="text-muted-text">{{ __('app.language') }}:</span>
        <a href="?lang=en" class="px-3 py-1.5 rounded-lg transition {{ app()->getLocale() === 'en' ? 'bg-accent text-on-accent' : 'bg-muted text-muted-text hover:bg-muted/80' }}">{{ __('app.lang_en') }}</a>
        <a href="?lang=am" class="px-3 py-1.5 rounded-lg transition {{ app()->getLocale() === 'am' ? 'bg-accent text-on-accent' : 'bg-muted text-muted-text hover:bg-muted/80' }}">{{ __('app.lang_am') }}</a>
    </div>

    <p class="text-center text-xs text-muted-text mt-6">{{ __('app.footer_branding', ['name' => __('app.app_name')]) }}</p>
</div>
@endsection

@push('scripts')
<script>
function onboarding() {
    return {
        baptismName: '',
        hasToken: false,
        isLoading: false,

        checkExisting() {
            const token = localStorage.getItem('member_token');
            if (token) {
                this.hasToken = true;
                AbiyTsom.token = token;
                AbiyTsom.api('/member/identify', { token })
                    .then(data => {
                        if (data.success) {
                            if (data.member.passcode_enabled) {
                                window.location.href = AbiyTsom.baseUrl + '/member/passcode';
                            } else {
                                window.location.href = AbiyTsom.baseUrl + '/member/home?token=' + token;
                            }
                        } else {
                            localStorage.removeItem('member_token');
                            this.hasToken = false;
                        }
                    })
                    .catch(() => {
                        localStorage.removeItem('member_token');
                        this.hasToken = false;
                    });
            }
        },

        register() {
            if (!this.baptismName.trim()) return;
            this.isLoading = true;

            AbiyTsom.api('/member/register', { baptism_name: this.baptismName.trim() })
                .then(data => {
                    if (data.success) {
                        localStorage.setItem('member_token', data.token);
                        localStorage.setItem('member_name', data.member.baptism_name);
                        AbiyTsom.token = data.token;
                        window.location.href = AbiyTsom.baseUrl + '/member/home?token=' + data.token;
                    }
                })
                .catch(() => { this.isLoading = false; });
        }
    };
}
</script>
@endpush
