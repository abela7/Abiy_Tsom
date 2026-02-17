@extends('layouts.member-guest')

@section('title', __('app.app_name') . ' - ' . __('app.tagline'))

@section('content')
<div x-data="onboarding()"
     x-init="checkExisting()">

    {{-- Redirect message (shown briefly when existing token found) --}}
    <div x-show="hasToken" x-transition class="text-center">
        <div class="animate-pulse">
            <p class="text-muted-text">{{ __('app.loading') }}</p>
        </div>
    </div>

    {{-- Registration wizard --}}
    <div x-show="!hasToken" x-transition>
        <div class="bg-card rounded-2xl sm:rounded-3xl shadow-xl shadow-black/5 dark:shadow-black/20 p-6 sm:p-8 border border-border">

            {{-- Progress dots --}}
            <div class="flex items-center justify-center gap-2 mb-6">
                <template x-for="s in (wantsWhatsApp ? 4 : 2)" :key="s">
                    <div class="h-2 rounded-full transition-all duration-300"
                         :class="s === step ? 'w-8 bg-accent' : s < step ? 'w-2 bg-accent/60' : 'w-2 bg-border'"></div>
                </template>
            </div>

            {{-- ==================== STEP 1: Baptism Name ==================== --}}
            <div x-show="step === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                <div class="text-center mb-5">
                    <h2 class="text-lg font-semibold text-primary">{{ __('app.onboarding_title') }}</h2>
                    <p class="text-sm text-muted-text mt-1">{{ __('app.onboarding_subtitle') }}</p>
                </div>

                <div class="mb-5">
                    <label for="baptism_name" class="block text-sm font-semibold text-secondary mb-2">
                        {{ __('app.baptism_name_label') }}
                    </label>
                    <input type="text"
                           id="baptism_name"
                           x-model="baptismName"
                           @keydown.enter="if (baptismName.trim()) step = 2"
                           :placeholder="'{{ __('app.baptism_name_placeholder') }}'"
                           class="w-full px-4 py-3.5 border border-border rounded-xl bg-muted/50 dark:bg-muted/30 text-primary placeholder:text-muted-text focus:ring-2 focus:ring-accent focus:border-accent outline-none transition text-base">
                </div>

                <button @click="step = 2"
                        :disabled="!baptismName.trim()"
                        class="w-full py-3.5 bg-accent text-on-accent rounded-xl font-bold text-base hover:bg-accent-hover disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.98] transition shadow-lg shadow-accent/20">
                    {{ __('app.wizard_next') }}
                </button>
            </div>

            {{-- ==================== STEP 2: WhatsApp Opt-in ==================== --}}
            <div x-show="step === 2" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                <div class="text-center mb-5">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-green-100 dark:bg-green-900/30 mb-3">
                        <svg class="w-7 h-7 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                    </div>
                    <h2 class="text-lg font-semibold text-primary">{{ __('app.wizard_whatsapp_title') }}</h2>
                    <p class="text-sm text-muted-text mt-2 leading-relaxed">{{ __('app.wizard_whatsapp_description') }}</p>
                </div>

                <div class="space-y-3">
                    <button @click="wantsWhatsApp = true; step = 3"
                            class="w-full py-3.5 bg-green-600 text-white rounded-xl font-bold text-base hover:bg-green-700 active:scale-[0.98] transition shadow-lg shadow-green-600/20 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ __('app.wizard_yes_notify') }}
                    </button>

                    <button @click="wantsWhatsApp = false; register()"
                            :disabled="isLoading"
                            class="w-full py-3.5 bg-muted text-secondary rounded-xl font-medium text-base hover:bg-muted/80 active:scale-[0.98] transition disabled:opacity-50">
                        <span x-show="!isLoading">{{ __('app.wizard_no_thanks') }}</span>
                        <span x-show="isLoading">{{ __('app.loading') }}</span>
                    </button>
                </div>

                <button @click="step = 1" class="w-full mt-3 py-2 text-sm text-muted-text hover:text-secondary transition">
                    {{ __('app.wizard_back') }}
                </button>
            </div>

            {{-- ==================== STEP 3: Phone Number ==================== --}}
            <div x-show="step === 3" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                <div class="text-center mb-5">
                    <h2 class="text-lg font-semibold text-primary">{{ __('app.wizard_phone_title') }}</h2>
                    <p class="text-sm text-muted-text mt-1">{{ __('app.wizard_phone_subtitle') }}</p>
                </div>

                <div class="mb-5">
                    <label for="whatsapp_phone" class="block text-sm font-semibold text-secondary mb-2">
                        {{ __('app.wizard_phone_label') }}
                    </label>
                    <input type="tel"
                           id="whatsapp_phone"
                           x-model="phone"
                           @keydown.enter="if (isPhoneValid) step = 4"
                           placeholder="+251912345678"
                           class="w-full px-4 py-3.5 border border-border rounded-xl bg-muted/50 dark:bg-muted/30 text-primary placeholder:text-muted-text focus:ring-2 focus:ring-accent focus:border-accent outline-none transition text-base font-mono tracking-wider"
                           dir="ltr">
                    <p class="text-xs text-muted-text mt-2">{{ __('app.wizard_phone_help') }}</p>
                    <p x-show="phone && !isPhoneValid" class="text-xs text-red-500 mt-1">{{ __('app.wizard_phone_invalid') }}</p>
                </div>

                <button @click="step = 4"
                        :disabled="!isPhoneValid"
                        class="w-full py-3.5 bg-accent text-on-accent rounded-xl font-bold text-base hover:bg-accent-hover disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.98] transition shadow-lg shadow-accent/20">
                    {{ __('app.wizard_next') }}
                </button>

                <button @click="step = 2" class="w-full mt-3 py-2 text-sm text-muted-text hover:text-secondary transition">
                    {{ __('app.wizard_back') }}
                </button>
            </div>

            {{-- ==================== STEP 4: Reminder Time ==================== --}}
            <div x-show="step === 4" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                <div class="text-center mb-5">
                    <h2 class="text-lg font-semibold text-primary">{{ __('app.wizard_time_title') }}</h2>
                    <p class="text-sm text-muted-text mt-1">{{ __('app.wizard_time_subtitle') }}</p>
                </div>

                <div class="mb-5">
                    <label for="reminder_time" class="block text-sm font-semibold text-secondary mb-2">
                        {{ __('app.wizard_time_label') }}
                    </label>
                    <input type="time"
                           id="reminder_time"
                           x-model="reminderTime"
                           class="w-full px-4 py-3.5 border border-border rounded-xl bg-muted/50 dark:bg-muted/30 text-primary focus:ring-2 focus:ring-accent focus:border-accent outline-none transition text-base">
                    <p class="text-xs text-muted-text mt-2">{{ __('app.wizard_time_help') }}</p>
                </div>

                <button @click="register()"
                        :disabled="!reminderTime || isLoading"
                        class="w-full py-3.5 bg-accent text-on-accent rounded-xl font-bold text-base hover:bg-accent-hover disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.98] transition shadow-lg shadow-accent/20">
                    <span x-show="!isLoading">{{ __('app.wizard_finish') }}</span>
                    <span x-show="isLoading">{{ __('app.loading') }}</span>
                </button>

                <button @click="step = 3" :disabled="isLoading" class="w-full mt-3 py-2 text-sm text-muted-text hover:text-secondary transition disabled:opacity-50">
                    {{ __('app.wizard_back') }}
                </button>
            </div>

            {{-- Error message --}}
            <div x-show="errorMessage" x-transition class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-700 dark:text-red-300 text-center">
                <p x-text="errorMessage"></p>
            </div>
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
        step: 1,
        baptismName: '',
        wantsWhatsApp: false,
        phone: '',
        reminderTime: '',
        hasToken: false,
        isLoading: false,
        errorMessage: '',

        get isPhoneValid() {
            return /^\+[1-9]\d{7,14}$/.test(this.phone.replace(/[\s\-\(\)]/g, ''));
        },

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
            this.errorMessage = '';

            const payload = {
                baptism_name: this.baptismName.trim(),
                whatsapp_reminder_enabled: this.wantsWhatsApp,
            };

            if (this.wantsWhatsApp) {
                payload.whatsapp_phone = this.phone.replace(/[\s\-\(\)]/g, '');
                payload.whatsapp_reminder_time = this.reminderTime;
            }

            AbiyTsom.api('/member/register', payload)
                .then(data => {
                    if (data.success) {
                        localStorage.setItem('member_token', data.token);
                        localStorage.setItem('member_name', data.member.baptism_name);
                        AbiyTsom.token = data.token;
                        window.location.href = AbiyTsom.baseUrl + '/member/home?token=' + data.token;
                    } else {
                        this.errorMessage = data.message || '{{ __('app.wizard_error') }}';
                        this.isLoading = false;
                    }
                })
                .catch(() => {
                    this.errorMessage = '{{ __('app.wizard_error') }}';
                    this.isLoading = false;
                });
        }
    };
}
</script>
@endpush
