@extends('layouts.member-guest')

@section('title', __('app.app_name') . ' - ' . __('app.tagline'))

@section('content')
<div x-data="registration()" x-cloak>

    {{-- Registration wizard --}}
    <div x-show="!loginMode" class="bg-card rounded-2xl sm:rounded-3xl shadow-2xl shadow-black/10 dark:shadow-black/30 border border-border overflow-hidden">

        {{-- Progress bar --}}
        <div class="h-1 bg-muted/60">
            <div class="h-full bg-gradient-to-r from-accent to-accent-secondary rounded-r-full transition-all duration-500 ease-out"
                 :style="'width: ' + (step / totalSteps * 100) + '%'"></div>
        </div>

        {{-- Step indicator --}}
        <div class="flex items-center justify-center pt-5 pb-1">
            <span class="text-[11px] font-medium text-muted-text tracking-wide"
                  x-text="step + ' / ' + totalSteps"></span>
        </div>

        <div class="px-5 pb-6 pt-2 sm:px-8 sm:pb-8 sm:pt-3">

            {{-- ==================== STEP 1: Baptism Name ==================== --}}
            <div x-show="step === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                <div class="text-center mb-7">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-accent/10 mb-4">
                        <svg class="w-7 h-7 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-bold text-primary leading-tight">{{ __('app.onboarding_title') }}</h2>
                    <p class="text-sm text-muted-text mt-2 max-w-[280px] mx-auto leading-relaxed">{{ __('app.onboarding_subtitle') }}</p>
                </div>

                <div class="mb-6">
                    <input type="text"
                           x-model="baptismName"
                           @keydown.enter="if (baptismName.trim()) step = 2"
                           :placeholder="'{{ __('app.baptism_name_placeholder') }}'"
                           class="w-full px-4 py-3.5 border-2 border-accent/30 rounded-xl bg-surface text-primary placeholder:text-muted-text/60 focus:ring-2 focus:ring-accent/40 focus:border-accent outline-none transition text-base">
                </div>

                {{-- Language selection --}}
                <div class="mb-6">
                    <p class="text-sm font-medium text-primary mb-3 text-center">{{ __('app.registration_language_title') }}</p>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" @click="locale = 'en'; window.location.href = '{{ request()->fullUrlWithQuery(['lang' => 'en']) }}'"
                                class="py-3 rounded-xl font-bold text-sm active:scale-[0.98] transition-all duration-150 flex items-center justify-center gap-2 border-2"
                                :class="locale === 'en'
                                    ? 'bg-accent text-on-accent border-accent shadow-lg shadow-accent/25'
                                    : 'bg-muted/60 text-primary border-border hover:border-accent/50'">
                            <span class="text-lg leading-none">🇬🇧</span>
                            English
                        </button>
                        <button type="button" @click="locale = 'am'; window.location.href = '{{ request()->fullUrlWithQuery(['lang' => 'am']) }}'"
                                class="py-3 rounded-xl font-bold text-sm active:scale-[0.98] transition-all duration-150 flex items-center justify-center gap-2 border-2"
                                :class="locale === 'am'
                                    ? 'bg-accent text-on-accent border-accent shadow-lg shadow-accent/25'
                                    : 'bg-muted/60 text-primary border-border hover:border-accent/50'">
                            <span class="text-lg leading-none">🇪🇹</span>
                            አማርኛ
                        </button>
                    </div>
                </div>

                <button @click="step = 2"
                        :disabled="!baptismName.trim()"
                        class="group w-full py-3.5 bg-accent text-on-accent rounded-xl font-bold text-base hover:brightness-110 disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.98] transition-all duration-150 shadow-lg shadow-accent/25 flex items-center justify-center gap-2">
                    {{ __('app.wizard_next') }}
                    <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>

            {{-- ==================== STEP 2: Phone Number ==================== --}}
            <div x-show="step === 2" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                <div class="text-center mb-7">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-accent/10 mb-4">
                        <svg class="w-7 h-7 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-bold text-primary leading-tight">{{ __('app.registration_phone_title') }}</h2>
                    <p class="text-sm text-muted-text mt-2 max-w-[260px] mx-auto leading-relaxed">{{ __('app.registration_phone_subtitle') }}</p>
                </div>

                <div class="mb-6">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                            <svg class="w-5 h-5 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <input type="tel"
                               x-model="phone"
                               @keydown.enter="if (isPhoneValid) goAfterPhone()"
                               placeholder="+447123456789"
                               class="w-full pl-12 pr-4 py-3.5 border rounded-xl bg-surface text-primary text-lg placeholder:text-muted-text/50 focus:ring-2 focus:ring-accent/40 outline-none transition font-mono tracking-wider"
                               :class="phone && !isPhoneValid ? 'border-red-400 focus:ring-red-400/40 focus:border-red-400' : 'border-border focus:border-accent'"
                               dir="ltr">
                        <div x-show="isPhoneValid" x-transition class="absolute inset-y-0 right-0 flex items-center pr-4">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                    </div>
                    <p x-show="phone && !isPhoneValid" x-transition class="text-xs text-red-500 mt-2">{{ __('app.wizard_phone_invalid') }}</p>
                </div>

                <button @click="goAfterPhone()"
                        :disabled="!isPhoneValid"
                        class="group w-full py-3.5 bg-accent text-on-accent rounded-xl font-bold text-base hover:brightness-110 disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.98] transition-all duration-150 shadow-lg shadow-accent/25 flex items-center justify-center gap-2">
                    {{ __('app.wizard_next') }}
                    <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </button>

                <button @click="step = 1" class="group w-full mt-5 py-2 text-sm text-muted-text hover:text-primary transition-colors flex items-center justify-center gap-1">
                    <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    {{ __('app.wizard_back') }}
                </button>
            </div>

            {{-- ==================== STEP 3: Email (non-UK only) ==================== --}}
            <div x-show="step === 3" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                <div class="text-center mb-7">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-accent/10 mb-4">
                        <svg class="w-7 h-7 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-bold text-primary leading-tight">{{ __('app.registration_email_title') }}</h2>
                    <p class="text-sm text-muted-text mt-2 max-w-[280px] mx-auto leading-relaxed">{{ __('app.registration_email_subtitle') }}</p>
                </div>

                <div class="mb-6">
                    <input type="email"
                           x-model="email"
                           @keydown.enter="if (isEmailValid) submitRegistration()"
                           :placeholder="'{{ __('app.registration_email_placeholder') }}'"
                           class="w-full px-4 py-3.5 border rounded-xl bg-surface text-primary placeholder:text-muted-text/50 focus:ring-2 focus:ring-accent/40 outline-none transition text-base"
                           :class="email && !isEmailValid ? 'border-red-400 focus:ring-red-400/40 focus:border-red-400' : 'border-border focus:border-accent'">
                </div>

                <button @click="submitRegistration()"
                        :disabled="!isEmailValid || isLoading"
                        class="group w-full py-3.5 bg-accent text-on-accent rounded-xl font-bold text-base hover:brightness-110 disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.98] transition-all duration-150 shadow-lg shadow-accent/25 flex items-center justify-center gap-2">
                    <span x-show="!isLoading" class="inline-flex items-center gap-2">
                        {{ __('app.wizard_next') }}
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </span>
                    <span x-show="isLoading" class="inline-flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        {{ __('app.loading') }}
                    </span>
                </button>

                <button @click="step = 2" :disabled="isLoading" class="group w-full mt-5 py-2 text-sm text-muted-text hover:text-primary transition-colors disabled:opacity-50 flex items-center justify-center gap-1">
                    <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    {{ __('app.wizard_back') }}
                </button>
            </div>

            {{-- ==================== STEP 4: Confirmation / Verification ==================== --}}
            <div x-show="step === 4" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                <div class="text-center mb-7">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl mb-4"
                         :class="verifyChannel === 'whatsapp' ? 'bg-green-500/10' : 'bg-accent/10'">
                        {{-- WhatsApp icon --}}
                        <svg x-show="verifyChannel === 'whatsapp'" class="w-7 h-7 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        {{-- Email icon --}}
                        <svg x-show="verifyChannel === 'email'" class="w-7 h-7 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>

                    {{-- WhatsApp: tell user to reply YES on WhatsApp --}}
                    <template x-if="verifyChannel === 'whatsapp'">
                        <div>
                            <h2 class="text-xl sm:text-2xl font-bold text-primary leading-tight">{{ __('app.registration_whatsapp_confirm_title') }}</h2>
                            <p class="text-sm text-muted-text mt-2 max-w-[300px] mx-auto leading-relaxed">{{ __('app.registration_whatsapp_confirm_subtitle') }}</p>
                            <p class="text-xs text-muted-text mt-1" x-text="maskedContact"></p>

                            {{-- Rejected state --}}
                            <div x-show="whatsappRejected" x-transition class="mt-5 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                                <p class="text-sm text-red-600 dark:text-red-400 font-medium">{{ __('app.registration_whatsapp_rejected') }}</p>
                            </div>

                            {{-- Open WhatsApp button --}}
                            @php
                                $waPhone = preg_replace('/\D/', '', config('services.ultramsg.church_phone', '+447757668785'));
                            @endphp
                            <div x-show="!whatsappRejected" class="mt-5">
                                <a href="https://wa.me/{{ $waPhone }}?text=YES"
                                   target="_blank"
                                   style="display:block;width:100%;padding:14px 0;background-color:#25D366;color:#fff;border-radius:12px;font-weight:700;font-size:1rem;text-align:center;text-decoration:none;box-shadow:0 4px 12px rgba(37,211,102,0.3)">
                                    <span style="display:inline-flex;align-items:center;gap:8px">
                                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        {{ __('app.registration_open_whatsapp') }}
                                    </span>
                                </a>
                            </div>

                            {{-- Waiting animation --}}
                            <div x-show="!whatsappRejected" class="mt-4 flex flex-col items-center gap-3">
                                <div class="flex items-center gap-2 text-green-600">
                                    <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    <span class="text-sm font-medium">{{ __('app.registration_waiting_whatsapp') }}</span>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Email: code input --}}
                    <template x-if="verifyChannel === 'email'">
                        <div>
                            <h2 class="text-xl sm:text-2xl font-bold text-primary leading-tight">{{ __('app.registration_verify_title') }}</h2>
                            <p class="text-sm text-muted-text mt-2 max-w-[280px] mx-auto leading-relaxed">{{ __('app.registration_verify_email_subtitle') }}</p>
                            <p class="text-xs text-muted-text mt-1" x-text="maskedContact"></p>
                        </div>
                    </template>
                </div>

                {{-- Code input — email users only --}}
                <div x-show="verifyChannel === 'email'" class="mb-6">
                    <input type="text"
                           x-model="verificationCode"
                           @keydown.enter="if (verificationCode.length === 6) verifyCode()"
                           maxlength="6"
                           inputmode="numeric"
                           pattern="[0-9]*"
                           :placeholder="'{{ __('app.registration_code_placeholder') }}'"
                           class="w-full px-4 py-4 border-2 border-accent/30 rounded-xl bg-surface text-primary text-center text-2xl font-mono tracking-[0.5em] placeholder:text-muted-text/40 focus:ring-2 focus:ring-accent/40 focus:border-accent outline-none transition">

                    <button @click="verifyCode()"
                            :disabled="verificationCode.length !== 6 || isLoading"
                            class="group w-full mt-4 py-3.5 bg-accent text-on-accent rounded-xl font-bold text-base hover:brightness-110 disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.98] transition-all duration-150 shadow-lg shadow-accent/25 flex items-center justify-center gap-2">
                        <span x-show="!isLoading" class="inline-flex items-center gap-2">
                            {{ __('app.registration_verify_button') }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <span x-show="isLoading" class="inline-flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            {{ __('app.loading') }}
                        </span>
                    </button>
                </div>

                {{-- Resend --}}
                <div class="mt-4 text-center">
                    <button @click="resendCode()"
                            :disabled="resendCooldown > 0 || isLoading"
                            class="text-sm font-medium transition-colors disabled:opacity-40"
                            :class="resendCooldown > 0 ? 'text-muted-text' : 'text-accent hover:text-primary'">
                        <span x-show="resendCooldown <= 0">{{ __('app.registration_resend') }}</span>
                        <span x-show="resendCooldown > 0" x-text="'{{ __('app.registration_resend') }} (' + resendCooldown + 's)'"></span>
                    </button>
                    <p x-show="resendMessage" x-transition x-text="resendMessage" class="text-xs text-green-600 mt-1"></p>
                </div>

                <button @click="step = isUkPhone ? 2 : 3; stopPolling()" :disabled="isLoading" class="group w-full mt-4 py-2 text-sm text-muted-text hover:text-primary transition-colors disabled:opacity-50 flex items-center justify-center gap-1">
                    <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    {{ __('app.wizard_back') }}
                </button>
            </div>

            {{-- Error message --}}
            <div x-show="errorMessage" x-transition class="mt-5 p-3.5 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-600 dark:text-red-400 text-center flex items-center justify-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p x-text="errorMessage"></p>
            </div>
        </div>
    </div>

    {{-- Already Registered? link --}}
    <div x-show="!loginMode" class="mt-5 text-center">
        <button @click="loginMode = true; loginStep = 'phone'" class="text-sm font-medium text-accent hover:text-primary transition-colors underline underline-offset-2">
            {{ __('app.login_already_registered') }}
        </button>
    </div>

    {{-- ===================== LOGIN FLOW ===================== --}}
    <div x-show="loginMode" x-transition class="bg-card rounded-2xl sm:rounded-3xl shadow-2xl shadow-black/10 dark:shadow-black/30 border border-border overflow-hidden mt-4">
        <div class="px-5 pb-6 pt-5 sm:px-8 sm:pb-8">

            {{-- LOGIN STEP: Phone --}}
            <div x-show="loginStep === 'phone'" x-transition>
                <div class="text-center mb-7">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-accent/10 mb-4">
                        <svg class="w-7 h-7 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-bold text-primary leading-tight">{{ __('app.login_title') }}</h2>
                    <p class="text-sm text-muted-text mt-2 max-w-[280px] mx-auto leading-relaxed">{{ __('app.login_subtitle') }}</p>
                </div>

                <div class="mb-4">
                    <input type="tel" x-model="loginPhone" @keydown.enter="submitLogin()"
                           placeholder="{{ __('app.login_phone_placeholder') }}" dir="ltr"
                           class="w-full px-4 py-3.5 border-2 border-accent/30 rounded-xl bg-surface text-primary text-lg placeholder:text-muted-text/50 focus:ring-2 focus:ring-accent/40 focus:border-accent outline-none transition font-mono tracking-wider">
                </div>

                <button @click="submitLogin()" :disabled="!loginPhone.trim() || loginLoading"
                        class="group w-full py-3.5 bg-accent text-on-accent rounded-xl font-bold text-base hover:brightness-110 disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.98] transition-all duration-150 shadow-lg shadow-accent/25 flex items-center justify-center gap-2">
                    <span x-show="!loginLoading" class="inline-flex items-center gap-2">
                        {{ __('app.login_button') }}
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </span>
                    <span x-show="loginLoading" class="inline-flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        {{ __('app.loading') }}
                    </span>
                </button>

                {{-- Phone not found? Try email --}}
                <div x-show="loginPhoneNotFound" x-transition class="mt-4 p-3.5 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl text-sm text-amber-700 dark:text-amber-400 text-center">
                    <p>{{ __('app.login_phone_not_found') }}</p>
                    <button @click="loginStep = 'email'" class="mt-2 font-bold text-accent underline">{{ __('app.login_ask_email') }}</button>
                </div>
            </div>

            {{-- LOGIN STEP: Email (when phone not found) --}}
            <div x-show="loginStep === 'email'" x-transition>
                <div class="text-center mb-7">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-accent/10 mb-4">
                        <svg class="w-7 h-7 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-bold text-primary leading-tight">{{ __('app.login_ask_email') }}</h2>
                    <p class="text-sm text-muted-text mt-2 max-w-[280px] mx-auto leading-relaxed">{{ __('app.login_ask_email_subtitle') }}</p>
                </div>

                <div class="mb-4">
                    <input type="email" x-model="loginEmail" @keydown.enter="submitLoginEmail()"
                           placeholder="{{ __('app.login_email_placeholder') }}"
                           class="w-full px-4 py-3.5 border-2 border-accent/30 rounded-xl bg-surface text-primary placeholder:text-muted-text/50 focus:ring-2 focus:ring-accent/40 focus:border-accent outline-none transition text-base">
                </div>

                <button @click="submitLoginEmail()" :disabled="!loginEmailValid || loginLoading"
                        class="group w-full py-3.5 bg-accent text-on-accent rounded-xl font-bold text-base hover:brightness-110 disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.98] transition-all duration-150 shadow-lg shadow-accent/25 flex items-center justify-center gap-2">
                    <span x-show="!loginLoading" class="inline-flex items-center gap-2">
                        {{ __('app.login_button') }}
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </span>
                    <span x-show="loginLoading" class="inline-flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        {{ __('app.loading') }}
                    </span>
                </button>

                <button @click="loginStep = 'phone'" class="group w-full mt-4 py-2 text-sm text-muted-text hover:text-primary transition-colors flex items-center justify-center gap-1">
                    <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    {{ __('app.wizard_back') }}
                </button>
            </div>

            {{-- LOGIN STEP: WhatsApp confirmation (waiting for YES) --}}
            <div x-show="loginStep === 'whatsapp-wait'" x-transition>
                <div class="text-center mb-5">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-green-500/10 mb-4">
                        <svg class="w-7 h-7 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-bold text-primary leading-tight">{{ __('app.login_whatsapp_confirm_title') }}</h2>
                    <p class="text-sm text-muted-text mt-2 max-w-[300px] mx-auto leading-relaxed">{{ __('app.login_whatsapp_confirm_subtitle') }}</p>
                    <p class="text-xs text-muted-text mt-1" x-text="loginMaskedContact"></p>
                </div>

                @php
                    $waPhone = preg_replace('/\D/', '', config('services.ultramsg.church_phone', '+447757668785'));
                @endphp
                <a href="https://wa.me/{{ $waPhone }}?text=YES"
                   target="_blank"
                   style="display:block;width:100%;padding:14px 0;background-color:#25D366;color:#fff;border-radius:12px;font-weight:700;font-size:1rem;text-align:center;text-decoration:none;box-shadow:0 4px 12px rgba(37,211,102,0.3)">
                    <span style="display:inline-flex;align-items:center;gap:8px">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        {{ __('app.registration_open_whatsapp') }}
                    </span>
                </a>

                <div class="mt-4 flex flex-col items-center gap-3">
                    <div class="flex items-center gap-2 text-green-600">
                        <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span class="text-sm font-medium">{{ __('app.registration_waiting_whatsapp') }}</span>
                    </div>
                </div>
            </div>

            {{-- LOGIN STEP: Email code verification --}}
            <div x-show="loginStep === 'email-verify'" x-transition>
                <div class="text-center mb-7">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-accent/10 mb-4">
                        <svg class="w-7 h-7 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-bold text-primary leading-tight">{{ __('app.login_email_verify_title') }}</h2>
                    <p class="text-sm text-muted-text mt-2 max-w-[280px] mx-auto leading-relaxed">{{ __('app.login_email_verify_subtitle') }}</p>
                </div>

                <div class="mb-4">
                    <input type="text" x-model="loginCode" @keydown.enter="if (loginCode.length === 6) verifyLoginCode()"
                           maxlength="6" inputmode="numeric" pattern="[0-9]*"
                           placeholder="000000"
                           class="w-full px-4 py-4 border-2 border-accent/30 rounded-xl bg-surface text-primary text-center text-2xl font-mono tracking-[0.5em] placeholder:text-muted-text/40 focus:ring-2 focus:ring-accent/40 focus:border-accent outline-none transition">
                </div>

                <button @click="verifyLoginCode()" :disabled="loginCode.length !== 6 || loginLoading"
                        class="group w-full py-3.5 bg-accent text-on-accent rounded-xl font-bold text-base hover:brightness-110 disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.98] transition-all duration-150 shadow-lg shadow-accent/25 flex items-center justify-center gap-2">
                    <span x-show="!loginLoading" class="inline-flex items-center gap-2">
                        {{ __('app.registration_verify_button') }}
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </span>
                    <span x-show="loginLoading" class="inline-flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        {{ __('app.loading') }}
                    </span>
                </button>

                <button @click="loginStep = 'email'" :disabled="loginLoading" class="group w-full mt-4 py-2 text-sm text-muted-text hover:text-primary transition-colors flex items-center justify-center gap-1">
                    <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    {{ __('app.wizard_back') }}
                </button>
            </div>

            {{-- Error message --}}
            <div x-show="loginError" x-transition class="mt-5 p-3.5 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-600 dark:text-red-400 text-center flex items-center justify-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p x-text="loginError"></p>
            </div>

            {{-- Back to register --}}
            <button @click="loginMode = false; stopLoginPolling()" class="group w-full mt-5 py-2 text-sm text-muted-text hover:text-primary transition-colors flex items-center justify-center gap-1">
                <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                {{ __('app.login_back_to_register') }}
            </button>
        </div>
    </div>


    <p class="text-center text-xs text-muted-text mt-6">{{ __('app.footer_branding', ['name' => __('app.app_name')]) }}</p>
</div>
@endsection

@push('scripts')
<script>
function registration() {
    return {
        // Login flow state
        loginMode: false,
        loginStep: 'phone', // 'phone', 'email', 'whatsapp-wait', 'email-verify'
        loginPhone: '',
        loginEmail: '',
        loginCode: '',
        loginLoading: false,
        loginError: '',
        loginPhoneNotFound: false,
        loginMaskedContact: '',
        loginMemberPhone: '',
        _loginPollInterval: null,

        get loginEmailValid() {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.loginEmail);
        },

        async submitLogin() {
            if (!this.loginPhone.trim()) return;
            this.loginLoading = true;
            this.loginError = '';
            this.loginPhoneNotFound = false;

            try {
                const data = await AbiyTsom.api('/login/member', { phone: this.loginPhone.trim() });

                if (data.success && data.channel === 'whatsapp') {
                    this.loginMaskedContact = data.member_phone || '';
                    this.loginMemberPhone = this.loginPhone.trim();
                    this.loginStep = 'whatsapp-wait';
                    this.startLoginPolling();
                } else if (data.phone_not_found) {
                    this.loginPhoneNotFound = true;
                } else {
                    this.loginError = data.message || '{{ __("app.wizard_error") }}';
                }
            } catch {
                this.loginError = '{{ __("app.wizard_error") }}';
            } finally {
                this.loginLoading = false;
            }
        },

        async submitLoginEmail() {
            if (!this.loginEmailValid) return;
            this.loginLoading = true;
            this.loginError = '';

            try {
                const data = await AbiyTsom.api('/login/member', { email: this.loginEmail.trim() });

                if (data.success && data.channel === 'email') {
                    this.loginMemberPhone = data.member_phone || '';
                    this.loginStep = 'email-verify';
                } else {
                    this.loginError = data.message || '{{ __("app.wizard_error") }}';
                }
            } catch {
                this.loginError = '{{ __("app.wizard_error") }}';
            } finally {
                this.loginLoading = false;
            }
        },

        async verifyLoginCode() {
            if (this.loginCode.length !== 6) return;
            this.loginLoading = true;
            this.loginError = '';

            try {
                const data = await AbiyTsom.api('/login/member/verify', {
                    email: this.loginEmail.trim(),
                    code: this.loginCode,
                });

                if (data.success && data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    this.loginError = data.message || '{{ __("app.wizard_error") }}';
                    this.loginLoading = false;
                }
            } catch {
                this.loginError = '{{ __("app.wizard_error") }}';
                this.loginLoading = false;
            }
        },

        startLoginPolling() {
            this.stopLoginPolling();
            this._loginPollInterval = setInterval(() => this.checkLoginStatus(), 3000);
        },

        stopLoginPolling() {
            if (this._loginPollInterval) {
                clearInterval(this._loginPollInterval);
                this._loginPollInterval = null;
            }
        },

        async checkLoginStatus() {
            try {
                const data = await AbiyTsom.api('/login/member/status', {
                    phone: this.loginMemberPhone,
                });
                if (data.status === 'confirmed' && data.redirect_url) {
                    this.stopLoginPolling();
                    window.location.href = data.redirect_url;
                } else if (data.status === 'rejected') {
                    this.stopLoginPolling();
                    this.loginError = '{{ __("app.registration_whatsapp_rejected") }}';
                    this.loginStep = 'phone';
                }
            } catch {
                // Silently ignore polling errors.
            }
        },

        // Registration flow state
        step: 1,
        baptismName: '',
        locale: '{{ in_array(app()->getLocale(), ['en', 'am']) ? app()->getLocale() : 'am' }}',
        phone: '',
        email: '',
        verificationCode: '',
        verifyChannel: 'whatsapp',
        maskedContact: '',
        isLoading: false,
        errorMessage: '',
        resendCooldown: 0,
        resendMessage: '',
        whatsappRejected: false,
        _cooldownInterval: null,
        _pollInterval: null,

        get isUkPhone() {
            return this.normalizeUkPhone(this.phone) !== null;
        },
        get isPhoneValid() {
            return this.normalizeUkPhone(this.phone) !== null || this.normalizeIntlPhone(this.phone) !== null;
        },
        get isEmailValid() {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email);
        },
        get totalSteps() {
            // Steps: 1=name, 2=phone, 3=email (non-UK only), 4=verify
            return this.isUkPhone || !this.phone ? 3 : 4;
        },
        get normalizedPhone() {
            return this.normalizeUkPhone(this.phone) || this.normalizeIntlPhone(this.phone) || this.phone;
        },

        normalizeUkPhone(raw) {
            if (!raw || typeof raw !== 'string') return null;
            let d = raw.replace(/\D/g, '');
            if (!d) return null;
            if (d.startsWith('00')) d = d.slice(2);
            while (d.startsWith('0')) d = d.slice(1);
            if (d.startsWith('44')) d = d.slice(2);
            if (d.startsWith('0')) d = d.slice(1);
            if (d.length !== 10 || d[0] !== '7') return null;
            return '+44' + d;
        },
        normalizeIntlPhone(raw) {
            if (!raw || typeof raw !== 'string') return null;
            let d = raw.replace(/\D/g, '');
            if (!d || d.length < 7 || d.length > 15) return null;
            return '+' + d;
        },

        goAfterPhone() {
            if (!this.isPhoneValid) return;
            if (this.isUkPhone) {
                // UK phone → register immediately, verify via WhatsApp
                this.submitRegistration();
            } else {
                // Non-UK → ask for email first
                this.step = 3;
            }
        },

        async submitRegistration() {
            this.isLoading = true;
            this.errorMessage = '';

            const payload = {
                baptism_name: this.baptismName.trim(),
                phone: this.normalizedPhone,
                locale: this.locale,
            };
            if (this.email) {
                payload.email = this.email;
            }

            try {
                const data = await AbiyTsom.api('/register', payload);

                if (data.success && data.verification_pending) {
                    this.verifyChannel = data.channel || 'whatsapp';
                    this.maskedContact = data.channel === 'email'
                        ? (data.member_email || '')
                        : (data.member_phone || '');
                    this.whatsappRejected = false;
                    this.step = 4;
                    this.startResendCooldown();
                    if (this.verifyChannel === 'whatsapp') {
                        this.startPolling();
                    }
                } else if (data.redirect_url) {
                    // Already verified — redirect to their account.
                    window.location.href = data.redirect_url;
                    return;
                } else if (data.requires_email) {
                    // Server says non-UK needs email
                    this.step = 3;
                } else {
                    this.errorMessage = data.message || '{{ __('app.wizard_error') }}';
                }
            } catch {
                this.errorMessage = '{{ __('app.wizard_error') }}';
            } finally {
                this.isLoading = false;
            }
        },

        async verifyCode() {
            if (this.verificationCode.length !== 6) return;
            this.isLoading = true;
            this.errorMessage = '';

            try {
                const data = await AbiyTsom.api('/register/verify', {
                    phone: this.normalizedPhone,
                    code: this.verificationCode,
                });

                if (data.success && data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    this.errorMessage = data.message || '{{ __('app.wizard_error') }}';
                    this.isLoading = false;
                }
            } catch {
                this.errorMessage = '{{ __('app.wizard_error') }}';
                this.isLoading = false;
            }
        },

        async resendCode() {
            if (this.resendCooldown > 0) return;
            this.resendMessage = '';

            try {
                const data = await AbiyTsom.api('/register/resend', {
                    phone: this.normalizedPhone,
                });
                if (data.code_sent) {
                    this.resendMessage = '{{ __('app.registration_resend_sent') }}';
                    this.startResendCooldown();
                    setTimeout(() => this.resendMessage = '', 3000);
                } else {
                    this.errorMessage = data.message || '{{ __('app.wizard_error') }}';
                }
            } catch {
                this.errorMessage = '{{ __('app.wizard_error') }}';
            }
        },

        startResendCooldown() {
            this.resendCooldown = 60;
            if (this._cooldownInterval) clearInterval(this._cooldownInterval);
            this._cooldownInterval = setInterval(() => {
                this.resendCooldown--;
                if (this.resendCooldown <= 0) {
                    clearInterval(this._cooldownInterval);
                    this._cooldownInterval = null;
                }
            }, 1000);
        },

        startPolling() {
            this.stopPolling();
            this._pollInterval = setInterval(() => this.checkVerificationStatus(), 3000);
        },

        stopPolling() {
            if (this._pollInterval) {
                clearInterval(this._pollInterval);
                this._pollInterval = null;
            }
        },

        async checkVerificationStatus() {
            try {
                const data = await AbiyTsom.api('/register/status', {
                    phone: this.normalizedPhone,
                });
                if (data.status === 'confirmed' && data.redirect_url) {
                    this.stopPolling();
                    window.location.href = data.redirect_url;
                } else if (data.status === 'rejected') {
                    this.stopPolling();
                    this.whatsappRejected = true;
                }
            } catch {
                // Silently ignore polling errors.
            }
        }
    };
}
</script>
@endpush
