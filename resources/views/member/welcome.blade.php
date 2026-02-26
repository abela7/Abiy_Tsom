@extends('layouts.member-guest')

@section('title', __('app.app_name') . ' - ' . __('app.tagline'))

@section('content')
<div x-data="onboarding()"
     x-init="checkExisting()">

    {{-- Redirect message (shown briefly when existing token found) --}}
    <div x-show="hasToken" x-transition class="text-center py-12">
        <div class="animate-pulse">
            <p class="text-muted-text text-lg">{{ __('app.loading') }}</p>
        </div>
    </div>

    {{-- Registration wizard --}}
    <div x-show="!hasToken" x-transition>
        <div class="bg-card rounded-2xl sm:rounded-3xl shadow-2xl shadow-black/10 dark:shadow-black/30 border border-border overflow-hidden">

            {{-- Progress bar --}}
            <div class="h-1 bg-muted/60">
                <div class="h-full bg-gradient-to-r from-accent to-accent-secondary rounded-r-full transition-all duration-500 ease-out"
                     :style="'width: ' + (step / (wantsWhatsApp ? 5 : 2) * 100) + '%'"></div>
            </div>

            {{-- Step indicator --}}
            <div class="flex items-center justify-center pt-5 pb-1">
                <span class="text-[11px] font-medium text-muted-text tracking-wide"
                      x-text="step + ' / ' + (wantsWhatsApp ? 5 : 2)"></span>
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
                               id="baptism_name"
                               x-model="baptismName"
                               @keydown.enter="if (baptismName.trim()) step = 2"
                               :placeholder="'{{ __('app.baptism_name_placeholder') }}'"
                               class="w-full px-4 py-3.5 border border-border rounded-xl bg-surface text-primary placeholder:text-muted-text/60 focus:ring-2 focus:ring-accent/40 focus:border-accent outline-none transition text-base">
                    </div>

                    <button @click="step = 2"
                            :disabled="!baptismName.trim()"
                            class="group w-full py-3.5 bg-accent text-on-accent rounded-xl font-bold text-base hover:brightness-110 disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.98] transition-all duration-150 shadow-lg shadow-accent/25 flex items-center justify-center gap-2">
                        {{ __('app.wizard_next') }}
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>

                {{-- ==================== STEP 2: WhatsApp Opt-in ==================== --}}
                <div x-show="step === 2" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                    <div class="text-center mb-7">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-500/10 dark:bg-green-500/15 mb-4 ring-4 ring-green-500/5">
                            <svg class="w-8 h-8 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-bold text-primary leading-tight">{{ __('app.wizard_whatsapp_title') }}</h2>
                        <p class="text-sm text-muted-text mt-2.5 leading-relaxed max-w-[280px] mx-auto">{{ __('app.wizard_whatsapp_description') }}</p>
                    </div>

                    <div class="space-y-3">
                        <button @click="wantsWhatsApp = true; step = 3"
                                class="group w-full py-4 bg-green-600 hover:bg-green-500 text-white rounded-xl font-bold text-base active:scale-[0.98] transition-all duration-150 shadow-lg shadow-green-600/30 flex items-center justify-center gap-2.5">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            {{ __('app.wizard_yes_notify') }}
                        </button>

                        <button @click="wantsWhatsApp = false; register()"
                                :disabled="isLoading"
                                class="w-full py-3.5 bg-muted/80 hover:bg-muted text-secondary rounded-xl font-semibold text-base active:scale-[0.98] transition-all duration-150 border border-border disabled:opacity-50">
                            <span x-show="!isLoading">{{ __('app.wizard_no_thanks') }}</span>
                            <span x-show="isLoading" class="inline-flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                {{ __('app.loading') }}
                            </span>
                        </button>
                    </div>

                    <button @click="step = 1" class="group w-full mt-5 py-2 text-sm text-muted-text hover:text-primary transition-colors flex items-center justify-center gap-1">
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        {{ __('app.wizard_back') }}
                    </button>
                </div>

                {{-- ==================== STEP 3: Phone Number ==================== --}}
                <div x-show="step === 3" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                    <div class="text-center mb-7">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-accent/10 mb-4">
                            <svg class="w-7 h-7 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-bold text-primary leading-tight">{{ __('app.wizard_phone_title') }}</h2>
                        <p class="text-sm text-muted-text mt-2 max-w-[260px] mx-auto leading-relaxed">{{ __('app.wizard_phone_subtitle') }}</p>
                    </div>

                    <div class="mb-6">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                <span class="text-muted-text text-sm font-medium">🇬🇧</span>
                            </div>
                            <input type="tel"
                                   id="whatsapp_phone"
                                   x-model="phone"
                                   @keydown.enter="if (isPhoneValid) step = 4"
                                   placeholder="07123456789"
                                   class="w-full pl-12 pr-4 py-3.5 border rounded-xl bg-surface text-primary text-lg placeholder:text-muted-text/50 focus:ring-2 focus:ring-accent/40 outline-none transition font-mono tracking-wider"
                                   :class="phone && !isPhoneValid ? 'border-red-400 focus:ring-red-400/40 focus:border-red-400' : 'border-border focus:border-accent'"
                                   dir="ltr">
                            <div x-show="isPhoneValid" x-transition class="absolute inset-y-0 right-0 flex items-center pr-4">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                        </div>
                        <p x-show="phone && !isPhoneValid" x-transition class="text-xs text-red-500 mt-2">{{ __('app.wizard_phone_invalid') }}</p>
                    </div>

                    <button @click="step = 4"
                            :disabled="!isPhoneValid"
                            class="group w-full py-3.5 bg-accent text-on-accent rounded-xl font-bold text-base hover:brightness-110 disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.98] transition-all duration-150 shadow-lg shadow-accent/25 flex items-center justify-center gap-2">
                        {{ __('app.wizard_next') }}
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </button>

                    <button @click="step = 2" class="group w-full mt-5 py-2 text-sm text-muted-text hover:text-primary transition-colors flex items-center justify-center gap-1">
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        {{ __('app.wizard_back') }}
                    </button>
                </div>

                {{-- ==================== STEP 4: Reminder Language ==================== --}}
                <div x-show="step === 4" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                    <div class="text-center mb-7">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-accent/10 mb-4">
                            <svg class="w-7 h-7 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                            </svg>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-bold text-primary leading-tight">{{ __('app.wizard_lang_title') }}</h2>
                        <p class="text-sm text-muted-text mt-2 max-w-[260px] mx-auto leading-relaxed">{{ __('app.wizard_lang_subtitle') }}</p>
                    </div>

                    <div class="space-y-3">
                        {{-- English option --}}
                        <button @click="whatsappLang = 'en'; step = 5"
                                class="group w-full py-4 rounded-xl font-bold text-base active:scale-[0.98] transition-all duration-150 flex items-center justify-between px-5 border-2"
                                :class="whatsappLang === 'en'
                                    ? 'bg-accent text-on-accent border-accent shadow-lg shadow-accent/25'
                                    : 'bg-muted/60 text-primary border-border hover:border-accent/50'">
                            <span class="flex items-center gap-3">
                                <span class="text-2xl leading-none">🇬🇧</span>
                                <span>{{ __('app.wizard_lang_english') }}</span>
                            </span>
                            <svg x-show="whatsappLang === 'en'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </button>

                        {{-- Amharic option --}}
                        <button @click="whatsappLang = 'am'; step = 5"
                                class="group w-full py-4 rounded-xl font-bold text-base active:scale-[0.98] transition-all duration-150 flex items-center justify-between px-5 border-2"
                                :class="whatsappLang === 'am'
                                    ? 'bg-accent text-on-accent border-accent shadow-lg shadow-accent/25'
                                    : 'bg-muted/60 text-primary border-border hover:border-accent/50'">
                            <span class="flex items-center gap-3">
                                <span class="text-2xl leading-none">🇪🇹</span>
                                <span>{{ __('app.wizard_lang_amharic') }}</span>
                            </span>
                            <svg x-show="whatsappLang === 'am'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </button>
                    </div>

                    <button @click="step = 3" class="group w-full mt-5 py-2 text-sm text-muted-text hover:text-primary transition-colors flex items-center justify-center gap-1">
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        {{ __('app.wizard_back') }}
                    </button>
                </div>

                {{-- ==================== STEP 5: Reminder Time ==================== --}}
                <div x-show="step === 5" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                    <div class="text-center mb-7">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-accent/10 mb-4">
                            <svg class="w-7 h-7 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-bold text-primary leading-tight">{{ __('app.wizard_time_title') }}</h2>
                        <p class="text-sm text-muted-text mt-2 max-w-[260px] mx-auto leading-relaxed">{{ __('app.wizard_time_subtitle') }}</p>
                    </div>

                    <div class="mb-6">
                        <input type="time"
                               id="reminder_time"
                               x-model="reminderTime"
                               class="w-full px-4 py-3.5 border border-border rounded-xl bg-surface text-primary text-lg focus:ring-2 focus:ring-accent/40 focus:border-accent outline-none transition">
                        <p class="text-xs text-muted-text mt-2.5 flex items-center justify-center gap-1.5">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ __('app.wizard_time_help') }}
                        </p>
                    </div>

                    <button @click="register()"
                            :disabled="!reminderTime || isLoading"
                            class="group w-full py-3.5 bg-accent text-on-accent rounded-xl font-bold text-base hover:brightness-110 disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.98] transition-all duration-150 shadow-lg shadow-accent/25 flex items-center justify-center gap-2">
                        <span x-show="!isLoading" class="inline-flex items-center gap-2">
                            {{ __('app.wizard_finish') }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <span x-show="isLoading" class="inline-flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            {{ __('app.loading') }}
                        </span>
                    </button>

                    <button @click="step = 4" :disabled="isLoading" class="group w-full mt-5 py-2 text-sm text-muted-text hover:text-primary transition-colors disabled:opacity-50 flex items-center justify-center gap-1">
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
    </div>

    {{-- WhatsApp Confirmation Modal --}}
    <div x-show="showWhatsAppModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         style="display: none;">
        <div x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-card rounded-2xl shadow-2xl border border-border w-full max-w-sm p-6 text-center">

            {{-- WhatsApp icon --}}
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-500/10 dark:bg-green-500/15 mb-4 ring-4 ring-green-500/5">
                <svg class="w-8 h-8 text-green-500" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
            </div>

            <h3 class="text-lg font-bold text-primary mb-2">{{ __('app.wizard_whatsapp_sent_title') }}</h3>
            <p x-text="modalMessage" class="text-sm text-muted-text mb-6 leading-relaxed"></p>

            <div class="flex flex-col gap-3">
@php
    $churchPhone = ltrim((string) config('services.ultramsg.church_phone', '+447757668785'), '+');
    $waUrl = 'https://wa.me/' . $churchPhone . '?text=' . rawurlencode('YES');
@endphp
                <a href="{{ $waUrl }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="w-full py-3.5 bg-green-600 hover:bg-green-500 text-white rounded-xl font-bold text-base active:scale-[0.98] transition-all duration-150 shadow-lg shadow-green-600/30 flex items-center justify-center gap-2.5">
                    <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    {{ __('app.wizard_open_whatsapp') }}
                </a>

                {{-- Waiting indicator — shown while polling for confirmation --}}
                <div class="flex items-center justify-center gap-2 py-2 text-sm text-muted-text">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse shrink-0"></span>
                    <span>{{ __('app.wizard_whatsapp_waiting') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Language switcher --}}
    <div class="mt-6 flex items-center justify-center gap-2 text-sm">
        <span class="text-muted-text">{{ __('app.language') }}:</span>
        <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" class="px-3 py-1.5 rounded-lg transition {{ app()->getLocale() === 'en' ? 'bg-accent text-on-accent font-semibold' : 'bg-muted text-muted-text hover:bg-muted/80' }}">{{ __('app.lang_en') }}</a>
        <a href="{{ request()->fullUrlWithQuery(['lang' => 'am']) }}" class="px-3 py-1.5 rounded-lg transition {{ app()->getLocale() === 'am' ? 'bg-accent text-on-accent font-semibold' : 'bg-muted text-muted-text hover:bg-muted/80' }}">{{ __('app.lang_am') }}</a>
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
        whatsappLang: '{{ in_array(app()->getLocale(), ['en', 'am']) ? app()->getLocale() : 'en' }}',
        reminderTime: '18:00',
        hasToken: false,
        isLoading: false,
        errorMessage: '',
        showWhatsAppModal: false,
        modalMessage: '',
        modalPhone: '',
        pendingRedirect: '',
        _pollInterval: null,
        _visibilityHandler: null,

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
        get isPhoneValid() {
            return this.normalizeUkPhone(this.phone) !== null;
        },

        checkExisting() {
            this.hasToken = true;
            AbiyTsom.api('/member/identify', {})
                .then(data => {
                    if (data.success) {
                        // If this member opted in but hasn't confirmed yet,
                        // show the modal again regardless of refresh.
                        if (data.member.whatsapp_confirmation_status === 'pending') {
                            this.hasToken = false;
                            this.pendingRedirect = AbiyTsom.baseUrl + '/member/home';
                            this.modalMessage = '{{ __('app.whatsapp_confirmation_pending_notice') }}';
                            this.modalPhone = data.member.whatsapp_phone || '';
                            this.showWhatsAppModal = true;
                            this.startConfirmationPolling();
                            return;
                        }

                        if (data.member.passcode_enabled) {
                            window.location.href = AbiyTsom.baseUrl + '/member/passcode';
                        } else {
                            window.location.href = AbiyTsom.baseUrl + '/member/home';
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
                payload.whatsapp_phone = this.normalizeUkPhone(this.phone) || this.phone;
                payload.whatsapp_language = this.whatsappLang;
                payload.whatsapp_reminder_time = this.reminderTime;
            }

            AbiyTsom.api('/member/register', payload)
                .then(data => {
                    if (data.success) {
                        localStorage.setItem('member_name', data.member.baptism_name);
                        const redirect = data.redirect_url || (AbiyTsom.baseUrl + '/member/home');
                        if (data.whatsapp_confirmation_pending && data.message) {
                            this.pendingRedirect = redirect;
                            this.modalMessage = data.message;
                            this.modalPhone = data.member.whatsapp_phone || '';
                            this.showWhatsAppModal = true;
                            this.isLoading = false;
                            this.startConfirmationPolling();
                        } else {
                            window.location.href = redirect;
                        }
                    } else {
                        this.errorMessage = data.message || '{{ __('app.wizard_error') }}';
                        this.isLoading = false;
                    }
                })
                .catch(() => {
                    this.errorMessage = '{{ __('app.wizard_error') }}';
                    this.isLoading = false;
                });
        },

        startConfirmationPolling() {
            this.stopConfirmationPolling();

            // Check immediately when the user returns to this tab from WhatsApp.
            this._visibilityHandler = () => {
                if (!document.hidden) this.checkConfirmationStatus();
            };
            document.addEventListener('visibilitychange', this._visibilityHandler);

            // Also poll every 4 seconds as a fallback.
            this._pollInterval = setInterval(() => this.checkConfirmationStatus(), 4000);
        },

        stopConfirmationPolling() {
            if (this._pollInterval) {
                clearInterval(this._pollInterval);
                this._pollInterval = null;
            }
            if (this._visibilityHandler) {
                document.removeEventListener('visibilitychange', this._visibilityHandler);
                this._visibilityHandler = null;
            }
        },

        checkConfirmationStatus() {
            AbiyTsom.api('/member/identify', {})
                .then(data => {
                    if (!data.success) return;
                    const status = data.member.whatsapp_confirmation_status;
                    if (status === 'confirmed' || status === 'rejected') {
                        this.stopConfirmationPolling();
                        this.dismissWhatsAppModal();
                    }
                })
                .catch(() => {});
        },

        dismissWhatsAppModal() {
            this.stopConfirmationPolling();
            this.showWhatsAppModal = false;
            window.location.href = this.pendingRedirect;
        }
    };
}
</script>
@endpush
