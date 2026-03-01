@extends('layouts.member')

@section('title', __('app.settings_title') . ' - ' . __('app.app_name'))

@section('content')
<div class="px-4 pt-4 pb-10 space-y-3" x-data="settingsPage()" x-init="syncThemeFromStorage()">
    <h1 class="text-xl font-bold text-primary">{{ __('app.settings_title') }}</h1>

    {{-- Accordion: each section collapsible --}}
    <div class="space-y-2" x-data="{ openId: null }">
        {{-- Profile --}}
        <div class="bg-card rounded-2xl shadow-sm border border-border overflow-hidden">
            <button type="button" @click="openId = openId === 'profile' ? null : 'profile'"
                    class="w-full flex items-center justify-between px-4 py-4 text-left">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-accent rounded-xl flex items-center justify-center text-on-accent font-bold"
                         x-text="(baptismName || 'A').charAt(0).toUpperCase()">
                    </div>
                    <div>
                        <h3 class="font-semibold text-primary">{{ __('app.baptism_name') }}</h3>
                        <p class="text-xs text-muted-text" x-text="baptismName || '—'"></p>
                    </div>
                </div>
                <svg class="w-5 h-5 text-muted-text transition-transform" :class="openId === 'profile' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="openId === 'profile'" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="px-4 pb-4 pt-0 space-y-3">
                <div>
                    <label for="baptismName" class="block text-xs font-medium text-muted-text mb-1">{{ __('app.baptism_name') }}</label>
                    <input type="text" id="baptismName" x-model="baptismName"
                           :placeholder="'{{ __('app.baptism_name_placeholder') }}'"
                           maxlength="255"
                           class="w-full px-4 py-2.5 border border-border rounded-xl bg-muted text-primary text-sm outline-none focus:ring-2 focus:ring-accent">
                </div>
                <button type="button" @click="saveBaptismName()"
                        :disabled="!baptismName.trim() || baptismName === savedBaptismName || profileSaving"
                        class="w-full py-2.5 bg-accent text-on-accent rounded-xl font-medium text-sm disabled:opacity-50 transition">
                    <span x-show="!profileSaving">{{ __('app.save') }}</span>
                    <span x-show="profileSaving">{{ __('app.loading') }}</span>
                </button>
                <p x-show="profileMsg" x-text="profileMsg" class="text-xs" :class="profileMsgError ? 'text-error' : 'text-success'"></p>
            </div>
        </div>

        {{-- Language --}}
        <div class="bg-card rounded-2xl shadow-sm border border-border overflow-hidden">
            <button type="button" @click="openId = openId === 'language' ? null : 'language'"
                    class="w-full flex items-center justify-between px-4 py-4 text-left">
                <h3 class="font-semibold text-primary">{{ __('app.language') }}</h3>
                <svg class="w-5 h-5 text-muted-text transition-transform shrink-0" :class="openId === 'language' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="openId === 'language'" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="px-4 pb-4 pt-0">
                <div class="flex gap-2">
                    <button @click="setLocale('en')"
                            class="flex-1 py-2.5 rounded-xl text-sm font-medium transition"
                            :class="locale === 'en' ? 'bg-accent text-on-accent' : 'bg-muted text-secondary'">
                        {{ __('app.lang_en') }}
                    </button>
                    <button @click="setLocale('am')"
                            class="flex-1 py-2.5 rounded-xl text-sm font-medium transition"
                            :class="locale === 'am' ? 'bg-accent text-on-accent' : 'bg-muted text-secondary'">
                        {{ __('app.lang_am') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Theme --}}
        <div class="bg-card rounded-2xl shadow-sm border border-border overflow-hidden">
            <button type="button" @click="openId = openId === 'theme' ? null : 'theme'"
                    class="w-full flex items-center justify-between px-4 py-4 text-left">
                <h3 class="font-semibold text-primary">{{ __('app.theme') }}</h3>
                <svg class="w-5 h-5 text-muted-text transition-transform shrink-0" :class="openId === 'theme' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="openId === 'theme'" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="px-4 pb-4 pt-0">
                <div class="flex gap-2">
                    <button @click="setTheme('light')"
                            class="flex-1 py-2.5 rounded-xl text-sm font-medium transition"
                            :class="theme === 'light' ? 'bg-accent-secondary text-primary' : 'bg-muted text-secondary'">
                        {{ __('app.theme_light') }}
                    </button>
                    <button @click="setTheme('dark')"
                            class="flex-1 py-2.5 rounded-xl text-sm font-medium transition"
                            :class="theme === 'dark' ? 'bg-accent-secondary text-primary' : 'bg-muted text-secondary'">
                        {{ __('app.theme_dark') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- App Tour --}}
        <div data-tour="settings-tour" class="bg-card rounded-2xl shadow-sm border border-border overflow-hidden"
             x-data="{ tourCompleted: {{ ($member && $member->tour_completed_at) ? 'true' : 'false' }} }">
            <button type="button" @click="openId = openId === 'tour' ? null : 'tour'; tourCompleted = typeof window.AbiyTsomIsTourCompleted === 'function' ? window.AbiyTsomIsTourCompleted() : tourCompleted"
                    class="w-full flex items-center justify-between px-4 py-4 text-left">
                <div>
                    <h3 class="font-semibold text-primary">{{ __('app.tour_section_title') }}</h3>
                    <p class="text-xs text-muted-text mt-0.5" x-text="tourCompleted ? '{{ __('app.tour_status_completed') }}' : '{{ __('app.tour_status_not_completed') }}'"></p>
                </div>
                <svg class="w-5 h-5 text-muted-text transition-transform shrink-0" :class="openId === 'tour' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="openId === 'tour'" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="px-4 pb-4 pt-0">
                <p class="text-sm text-muted-text mb-4">{{ __('app.tour_section_desc') }}</p>
                <button type="button" @click="(async () => { await window.AbiyTsomResetTour?.(); tourCompleted = false; window.location.href = '{{ route('member.home') }}?tour=1'; })()"
                        class="w-full py-2.5 bg-accent text-on-accent rounded-xl font-medium text-sm transition hover:opacity-90">
                    {{ __('app.tour_show_again') }}
                </button>
            </div>
        </div>

        {{-- WhatsApp Reminder --}}
        <div class="bg-card rounded-2xl shadow-sm border border-border overflow-hidden">
            <button type="button" @click="openId = openId === 'whatsapp' ? null : 'whatsapp'"
                    class="w-full flex items-center justify-between px-4 py-4 text-left">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                         :class="waStatus === 'confirmed' ? 'bg-green-500/15' : (waStatus === 'pending' ? 'bg-amber-500/15' : 'bg-muted')">
                        <svg class="w-5 h-5" :class="waStatus === 'confirmed' ? 'text-green-500' : (waStatus === 'pending' ? 'text-amber-500' : 'text-muted-text')" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-semibold text-primary">{{ __('app.settings_whatsapp_title') }}</h3>
                        <p class="text-xs text-muted-text truncate" x-text="waStatusLabel"></p>
                    </div>
                </div>
                <svg class="w-5 h-5 text-muted-text transition-transform shrink-0" :class="openId === 'whatsapp' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="openId === 'whatsapp'" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="px-4 pb-4 pt-0 space-y-4">

                {{-- Disabled state: setup prompt --}}
                <template x-if="!waEnabled && !waPhone">
                    <div class="space-y-4">
                        <p class="text-sm text-muted-text">{{ __('app.settings_whatsapp_setup_cta') }}</p>

                        {{-- Phone --}}
                        <div>
                            <label class="block text-xs font-medium text-muted-text mb-1.5">{{ __('app.settings_whatsapp_phone') }}</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                    <span class="text-sm">🇬🇧</span>
                                </div>
                                <input type="tel" x-model="waPhone" placeholder="07123456789" dir="ltr"
                                       class="w-full pl-10 pr-4 py-2.5 border border-border rounded-xl bg-muted text-primary text-sm font-mono tracking-wider outline-none focus:ring-2 focus:ring-accent">
                            </div>
                        </div>

                        {{-- Time --}}
                        <div>
                            <label class="block text-xs font-medium text-muted-text mb-1.5">{{ __('app.settings_whatsapp_time') }}</label>
                            <input type="time" x-model="waTime"
                                   class="w-full px-4 py-2.5 border border-border rounded-xl bg-muted text-primary text-sm outline-none focus:ring-2 focus:ring-accent">
                        </div>

                        {{-- Language --}}
                        <div>
                            <label class="block text-xs font-medium text-muted-text mb-1.5">{{ __('app.settings_whatsapp_lang') }}</label>
                            <div class="flex gap-2">
                                <button type="button" @click="waLang = 'en'"
                                        class="flex-1 py-2.5 rounded-xl text-sm font-medium transition flex items-center justify-center gap-1.5"
                                        :class="waLang === 'en' ? 'bg-accent text-on-accent' : 'bg-muted text-secondary border border-border'">
                                    <span>🇬🇧</span> {{ __('app.wizard_lang_english') }}
                                </button>
                                <button type="button" @click="waLang = 'am'"
                                        class="flex-1 py-2.5 rounded-xl text-sm font-medium transition flex items-center justify-center gap-1.5"
                                        :class="waLang === 'am' ? 'bg-accent text-on-accent' : 'bg-muted text-secondary border border-border'">
                                    <span>🇪🇹</span> {{ __('app.wizard_lang_amharic') }}
                                </button>
                            </div>
                        </div>

                        {{-- Enable button --}}
                        <button type="button" @click="enableWhatsApp()"
                                :disabled="!waPhoneValid || !waTime || waSaving"
                                class="w-full py-3 bg-green-600 text-white rounded-xl font-bold text-sm disabled:opacity-40 transition active:scale-[0.98] flex items-center justify-center gap-2">
                            <span x-show="!waSaving">{{ __('app.settings_whatsapp_enable') }}</span>
                            <span x-show="waSaving" class="inline-flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                {{ __('app.loading') }}
                            </span>
                        </button>
                    </div>
                </template>

                {{-- Enabled state: showing current settings with edit --}}
                <template x-if="waEnabled || waPhone">
                    <div class="space-y-4">
                        <p class="text-sm text-muted-text" x-text="waStatusDescription"></p>

                        {{-- Toggle on/off --}}
                        <div class="flex items-center justify-between p-3 rounded-xl bg-muted/60">
                            <span class="text-sm font-medium text-primary">{{ __('app.settings_whatsapp_title') }}</span>
                            <button type="button" @click="toggleWhatsApp()"
                                    :disabled="waSaving || (waEnabled ? false : (!waPhoneValid || !waTime))"
                                    :title="(!waEnabled && (!waPhoneValid || !waTime)) ? '{{ __('app.whatsapp_reminder_requires_phone_and_time') }}' : ''"
                                    class="relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                    :class="waEnabled ? 'bg-green-500' : 'bg-border'"
                                    role="switch" :aria-checked="waEnabled">
                                <span class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-sm ring-0 transition-transform duration-200"
                                      :class="waEnabled ? 'translate-x-5' : 'translate-x-0'"></span>
                            </button>
                        </div>

                        {{-- Editable fields (always visible when toggle is shown so user can fill phone/time before enabling) --}}
                        <div x-show="true" x-transition class="space-y-3">
                            {{-- Phone --}}
                            <div>
                                <label class="block text-xs font-medium text-muted-text mb-1.5">{{ __('app.settings_whatsapp_phone') }}</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                        <span class="text-sm">🇬🇧</span>
                                    </div>
                                    <input type="tel" x-model="waPhone" placeholder="07123456789" dir="ltr"
                                           class="w-full pl-10 pr-10 py-2.5 border rounded-xl bg-muted text-primary text-sm font-mono tracking-wider outline-none focus:ring-2 focus:ring-accent"
                                           :class="waPhone && !waPhoneValid ? 'border-red-400' : 'border-border'">
                                    <div x-show="waPhoneValid" class="absolute inset-y-0 right-0 flex items-center pr-3.5">
                                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                </div>
                            </div>

                            {{-- Time --}}
                            <div>
                                <label class="block text-xs font-medium text-muted-text mb-1.5">{{ __('app.settings_whatsapp_time') }}</label>
                                <input type="time" x-model="waTime"
                                       class="w-full px-4 py-2.5 border border-border rounded-xl bg-muted text-primary text-sm outline-none focus:ring-2 focus:ring-accent">
                                <p class="text-xs text-muted-text mt-1">{{ __('app.wizard_time_help') }}</p>
                            </div>

                            {{-- Language --}}
                            <div>
                                <label class="block text-xs font-medium text-muted-text mb-1.5">{{ __('app.settings_whatsapp_lang') }}</label>
                                <div class="flex gap-2">
                                    <button type="button" @click="waLang = 'en'"
                                            class="flex-1 py-2.5 rounded-xl text-sm font-medium transition flex items-center justify-center gap-1.5"
                                            :class="waLang === 'en' ? 'bg-accent text-on-accent' : 'bg-muted text-secondary border border-border'">
                                        <span>🇬🇧</span> {{ __('app.wizard_lang_english') }}
                                    </button>
                                    <button type="button" @click="waLang = 'am'"
                                            class="flex-1 py-2.5 rounded-xl text-sm font-medium transition flex items-center justify-center gap-1.5"
                                            :class="waLang === 'am' ? 'bg-accent text-on-accent' : 'bg-muted text-secondary border border-border'">
                                        <span>🇪🇹</span> {{ __('app.wizard_lang_amharic') }}
                                    </button>
                                </div>
                            </div>

                            {{-- Save changes button --}}
                            <button type="button" @click="saveWhatsApp()"
                                    :disabled="!waPhoneValid || !waTime || waSaving || (!waHasChanges && waStatus !== 'pending')"
                                    class="w-full py-2.5 bg-accent text-on-accent rounded-xl font-medium text-sm disabled:opacity-40 transition active:scale-[0.98]">
                                <span x-show="!waSaving" x-text="waStatus === 'pending' ? '{{ __('app.settings_whatsapp_resend_confirmation') }}' : '{{ __('app.save') }}'"></span>
                                <span x-show="waSaving">{{ __('app.loading') }}</span>
                            </button>
                        </div>

                        {{-- Feedback --}}
                        <p x-show="waMsg" x-text="waMsg" class="text-xs" :class="waMsgError ? 'text-error' : 'text-success'"></p>
                    </div>
                </template>
            </div>
        </div>

        {{-- Link Telegram --}}
        <div class="bg-card rounded-2xl shadow-sm border border-border overflow-hidden">
            <button type="button" @click="openId = openId === 'telegram' ? null : 'telegram'"
                    class="w-full flex items-center justify-between px-4 py-4 text-left">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 bg-sky-500/15">
                        <svg class="w-5 h-5 text-sky-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.161c-.18 1.897-.962 6.502-1.359 8.627-.168.9-.5 1.201-.82 1.23-.697.064-1.226-.461-1.901-.903-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.139-5.062 3.345-.479.329-.913.489-1.302.481-.428-.009-1.252-.241-1.865-.44-.752-.244-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.831-2.529 6.998-3.015 3.333-1.386 4.025-1.627 4.477-1.635.099-.002.321.023.465.141.121.1.154.234.17.332.015.098.034.321.019.495z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-semibold text-primary">{{ __('app.telegram_settings_link_title') }}</h3>
                        <p class="text-xs text-muted-text truncate" x-text="telegramStatusLabel"></p>
                    </div>
                </div>
                <svg class="w-5 h-5 text-muted-text transition-transform shrink-0" :class="openId === 'telegram' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="openId === 'telegram'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="px-4 pb-4 pt-0 space-y-4">
                <p class="text-sm text-muted-text">{{ __('app.telegram_settings_link_desc') }}</p>
                @if ($member?->telegram_chat_id)
                <div class="flex items-center justify-between p-3 rounded-xl bg-green-500/10 border border-green-500/20">
                    <span class="text-sm text-green-700 dark:text-green-400">{{ __('app.telegram_settings_link_title') }} — {{ __('app.telegram_settings_linked') }}</span>
                    <button type="button" @click="unlinkTelegram()"
                            :disabled="telegramLoading"
                            class="px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-500/10 rounded-lg transition disabled:opacity-50">
                        {{ __('app.telegram_settings_unlink') }}
                    </button>
                </div>
                @endif
                <button type="button" @click="generateLink()"
                        :disabled="telegramLoading"
                        class="w-full py-2.5 bg-accent text-on-accent rounded-xl font-medium text-sm disabled:opacity-50 transition flex items-center justify-center gap-2">
                    <span x-show="!telegramLoading">{{ __('app.telegram_settings_generate_link') }}</span>
                    <span x-show="telegramLoading" class="inline-flex gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        {{ __('app.loading') }}
                    </span>
                </button>
                <div x-show="telegramLink || telegramCode" class="space-y-3">
                    <p class="text-xs text-success" x-text="telegramMsg"></p>
                    <template x-if="telegramCode">
                        <div class="p-4 rounded-xl bg-accent/10 border-2 border-accent/30 text-center">
                            <p class="text-xs text-muted-text mb-1">{{ __('app.telegram_settings_code_instructions') }}</p>
                            <p class="text-2xl font-bold tracking-[0.3em] text-primary font-mono" x-text="telegramCode"></p>
                            <p class="text-xs text-muted-text mt-2">Type this in the Telegram bot</p>
                        </div>
                    </template>
                    <div class="flex gap-2">
                        <button type="button" @click="copyCode()" x-show="telegramCode"
                                class="flex-1 py-2.5 bg-accent text-on-accent rounded-xl text-sm font-medium hover:bg-accent-hover transition">
                            Copy code
                        </button>
                        <button type="button" @click="copyLink()" x-show="telegramLink"
                                class="flex-1 py-2.5 bg-muted text-primary rounded-xl text-sm font-medium border border-border hover:bg-muted/80 transition">
                            Copy link
                        </button>
                    </div>
                </div>
                <p x-show="telegramError" x-text="telegramError" class="text-xs text-error"></p>
            </div>
        </div>

        {{-- Custom Activities --}}
        <div data-tour="settings-custom" class="bg-card rounded-2xl shadow-sm border border-border overflow-hidden">
            <button type="button" @click="openId = openId === 'activities' ? null : 'activities'"
                    class="w-full flex items-center justify-between px-4 py-4 text-left">
                <h3 class="font-semibold text-primary">{{ __('app.custom_activities') }}</h3>
                <svg class="w-5 h-5 text-muted-text transition-transform shrink-0" :class="openId === 'activities' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="openId === 'activities'" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="px-4 pb-4 pt-0">
                <p class="text-xs text-muted-text mb-3">{{ __('app.custom_activities_desc') }}</p>
                <div x-data="customActivitiesSection()" x-init="customActivities = {{ json_encode($customActivities ?? []) }}">

                    {{-- Add new activity --}}
                    <form @submit.prevent="addActivity()" class="flex flex-wrap gap-2 mb-4">
                        <input type="text" x-model="newName" maxlength="255"
                               :placeholder="'{{ __('app.custom_activity_placeholder') }}'"
                               class="min-w-0 flex-1 basis-24 px-4 py-2.5 border border-border rounded-xl bg-muted text-primary text-sm outline-none focus:ring-2 focus:ring-accent">
                        <button type="submit" :disabled="!newName.trim()"
                                class="shrink-0 px-4 py-2.5 bg-accent text-on-accent rounded-xl font-medium text-sm disabled:opacity-50 transition">
                            {{ __('app.add') }}
                        </button>
                    </form>

                    {{-- Activity list --}}
                    <ul class="space-y-2" x-show="customActivities.length > 0">
                        <template x-for="activity in customActivities" :key="activity.id">
                            <li class="rounded-xl bg-muted border border-transparent hover:border-border transition"
                                x-data="{ editing: false, editName: activity.name }">

                                {{-- Display mode --}}
                                <div x-show="!editing" class="flex items-center gap-2 p-3 min-w-0">
                                    <span class="text-sm font-medium text-primary min-w-0 truncate flex-1" x-text="activity.name"></span>
                                    <button type="button"
                                            @click="editing = true; editName = activity.name; $nextTick(() => $el.closest('li').querySelector('input[type=text]')?.focus())"
                                            class="shrink-0 p-1.5 text-muted-text hover:text-accent hover:bg-accent/10 rounded-lg transition touch-manipulation"
                                            :aria-label="'{{ __('app.edit') }}'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button type="button"
                                            @click="deleteActivity(activity.id)"
                                            class="shrink-0 p-1.5 text-error hover:bg-error/10 rounded-lg transition touch-manipulation"
                                            :aria-label="'{{ __('app.delete') }}'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>

                                {{-- Edit mode --}}
                                <form x-show="editing" x-transition
                                      @submit.prevent="renameActivity(activity, editName).then(ok => { if(ok) editing = false; })"
                                      @keyup.escape="editing = false; editName = activity.name"
                                      class="flex items-center gap-2 p-2 min-w-0">
                                    <input type="text" x-model="editName" maxlength="255"
                                           class="min-w-0 flex-1 px-3 py-2 border border-accent rounded-lg bg-surface text-primary text-sm outline-none focus:ring-2 focus:ring-accent">
                                    <button type="submit"
                                            :disabled="!editName.trim() || editName.trim() === activity.name"
                                            class="shrink-0 p-2 rounded-lg bg-accent text-on-accent transition hover:opacity-80 disabled:opacity-40"
                                            :aria-label="'{{ __('app.save') }}'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                    <button type="button"
                                            @click="editing = false; editName = activity.name"
                                            class="shrink-0 p-2 rounded-lg bg-muted text-muted-text transition hover:bg-border"
                                            :aria-label="'{{ __('app.cancel') }}'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </form>
                            </li>
                        </template>
                    </ul>
                    <p x-show="customActivities.length === 0" class="text-sm text-muted-text">{{ __('app.no_custom_activities') }}</p>
                    <p x-show="msg" x-text="msg" class="text-sm mt-2" :class="msgError ? 'text-error' : 'text-success'"></p>
                </div>
            </div>
        </div>

        {{-- Passcode --}}
        <div class="bg-card rounded-2xl shadow-sm border border-border overflow-hidden">
            <button type="button" @click="openId = openId === 'passcode' ? null : 'passcode'"
                    class="w-full flex items-center justify-between px-4 py-4 text-left">
                <h3 class="font-semibold text-primary">{{ __('app.passcode_lock') }}</h3>
                <svg class="w-5 h-5 text-muted-text transition-transform shrink-0" :class="openId === 'passcode' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="openId === 'passcode'" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="px-4 pb-4 pt-0">
                <div x-show="!passcodeEnabled" class="space-y-3">
                    <input type="password" x-model="newPasscode" maxlength="6" inputmode="numeric" pattern="[0-9]*"
                           placeholder="{{ __('app.set_passcode') }}"
                           class="w-full px-4 py-3 border border-border rounded-xl bg-muted text-primary text-base outline-none focus:ring-2 focus:ring-accent">
                    <button @click="enablePasscode()"
                            :disabled="newPasscode.length < 4"
                            class="w-full py-2.5 bg-accent text-on-accent rounded-xl font-medium text-sm disabled:opacity-50 transition">
                        {{ __('app.passcode_enable') }}
                    </button>
                </div>
                <div x-show="passcodeEnabled" class="space-y-3">
                    <p class="text-sm text-success">{{ __('app.passcode_enabled') }}</p>
                    <button @click="disablePasscode()"
                            class="w-full py-2.5 bg-error text-on-error rounded-xl font-medium text-sm transition hover:opacity-90">
                        {{ __('app.passcode_disable') }}
                    </button>
                </div>
                <p x-show="passcodeMsg" x-text="passcodeMsg" class="text-sm text-success mt-2"></p>
            </div>
        </div>

        {{-- Data Management --}}
        <div class="bg-card rounded-2xl shadow-sm border border-border overflow-hidden"
             x-data="dataManagement()">
            <button type="button" @click="openId = openId === 'data' ? null : 'data'"
                    class="w-full flex items-center justify-between px-4 py-4 text-left">
                <h3 class="font-semibold text-primary">{{ __('app.data_management') }}</h3>
                <svg class="w-5 h-5 text-muted-text transition-transform shrink-0" :class="openId === 'data' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="openId === 'data'" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="px-4 pb-4 pt-0 space-y-4">
                <p class="text-sm text-muted-text">{{ __('app.data_management_desc') }}</p>

                {{-- Export --}}
                <div>
                    <p class="text-sm font-medium text-primary mb-1">{{ __('app.export_data') }}</p>
                    <p class="text-xs text-muted-text mb-2">{{ __('app.export_data_desc') }}</p>
                    <button type="button" @click="exportData()"
                            :disabled="dataLoading"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-accent text-on-accent rounded-xl text-sm font-medium hover:bg-accent-hover transition disabled:opacity-50">
                        <svg x-show="!dataLoading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span x-show="dataLoading" class="w-4 h-4 border-2 border-on-accent/30 border-t-on-accent rounded-full animate-spin"></span>
                        {{ __('app.export') }}
                    </button>
                </div>

                {{-- Import --}}
                <div>
                    <p class="text-sm font-medium text-primary mb-1">{{ __('app.import_data') }}</p>
                    <p class="text-xs text-muted-text mb-2">{{ __('app.import_data_desc') }}</p>
                    <input type="file" @change="handleFileSelect($event)" accept=".json,application/json" class="hidden" x-ref="importInput">
                    <button type="button" @click="$refs.importInput.click()"
                            :disabled="dataLoading"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-muted text-primary rounded-xl text-sm font-medium hover:bg-muted/80 transition border border-border disabled:opacity-50">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 16m4-4v12"/>
                        </svg>
                        {{ __('app.import') }}
                    </button>
                </div>

                {{-- Clear --}}
                <div>
                    <p class="text-sm font-medium text-primary mb-1">{{ __('app.clear_data') }}</p>
                    <p class="text-xs text-muted-text mb-2">{{ __('app.clear_data_desc') }}</p>
                    <div class="flex flex-wrap gap-2 items-end">
                        <div class="min-w-0 flex-1">
                            <label for="clearConfirm" class="block text-xs text-muted-text mb-1">{{ __('app.clear_confirm_label') }}</label>
                            <input type="text" id="clearConfirm" x-model="clearConfirm"
                                   :placeholder="'{{ __('app.clear_confirm_placeholder') }}'"
                                   class="w-full px-4 py-2.5 border border-border rounded-xl bg-muted text-primary text-sm outline-none focus:ring-2 focus:ring-accent">
                        </div>
                        <button type="button" @click="clearData()"
                                :disabled="clearConfirm !== 'RESET' || dataLoading"
                                class="px-4 py-2.5 bg-error text-on-error rounded-xl text-sm font-medium hover:opacity-90 transition disabled:opacity-50">
                            {{ __('app.reset') }}
                        </button>
                    </div>
                </div>

                <p x-show="dataMsg" x-text="dataMsg" class="text-sm pt-2" :class="dataMsgError ? 'text-error' : 'text-success'"></p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function customActivitiesSection() {
    return {
        customActivities: [],
        newName: '',
        msg: '',
        msgError: false,

        showMsg(text, isError = false) {
            this.msg = text;
            this.msgError = isError;
            setTimeout(() => { this.msg = ''; }, 3000);
        },

        async addActivity() {
            const name = this.newName.trim();
            if (!name) return;
            const data = await AbiyTsom.api('/api/member/custom-activities', { name });
            if (data.success) {
                this.customActivities.push(data.activity);
                this.newName = '';
                this.showMsg('{{ __("app.custom_activity_added") }}');
            } else {
                this.showMsg(data.message || '{{ __("app.failed_to_add") }}', true);
            }
        },

        async renameActivity(activity, newName) {
            newName = newName.trim();
            if (!newName || newName === activity.name) return false;
            try {
                const data = await AbiyTsom.api('/api/member/custom-activities/update', {
                    id: activity.id,
                    name: newName,
                });
                if (data.success) {
                    activity.name = data.activity.name;
                    this.showMsg('{{ __("app.custom_activity_updated") }}');
                    return true;
                }
                this.showMsg(data.message || '{{ __("app.failed_to_save") }}', true);
            } catch (_e) {
                this.showMsg('{{ __("app.failed_to_save") }}', true);
            }
            return false;
        },

        async deleteActivity(id) {
            if (!confirm('{{ __("app.confirm_delete_custom_activity") }}')) return;
            const data = await AbiyTsom.api('/api/member/custom-activities/delete', { id });
            if (data.success) {
                this.customActivities = this.customActivities.filter(a => a.id !== id);
                this.showMsg('{{ __("app.custom_activity_deleted") }}');
            }
        },
    };
}

function dataManagement() {
    return {
        dataLoading: false,
        dataMsg: '',
        dataMsgError: false,
        clearConfirm: '',
        async exportData() {
            this.dataLoading = true;
            this.dataMsg = '';
            try {
                const url = AbiyTsom.baseUrl + '/api/member/data/export';
                window.location.href = url;
                this.dataMsg = '{{ __("app.export_success") }}';
                this.dataMsgError = false;
                setTimeout(() => { this.dataMsg = ''; }, 3000);
            } catch (e) {
                this.dataMsg = '{{ __("app.export_failed") }}';
                this.dataMsgError = true;
            } finally {
                this.dataLoading = false;
            }
        },
        async handleFileSelect(ev) {
            const file = ev.target.files?.[0];
            if (!file) return;
            this.dataLoading = true;
            this.dataMsg = '';
            this.dataMsgError = false;
            try {
                const text = await file.text();
                const data = await AbiyTsom.api('/api/member/data/import', { data: text });
                if (data.success) {
                    this.dataMsg = '{{ __("app.import_success") }}';
                    this.dataMsgError = false;
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    this.dataMsg = data.message || '{{ __("app.import_failed") }}';
                    this.dataMsgError = true;
                }
            } catch (e) {
                this.dataMsg = '{{ __("app.import_failed") }}';
                this.dataMsgError = true;
            } finally {
                this.dataLoading = false;
                ev.target.value = '';
            }
        },
        async clearData() {
            if (this.clearConfirm !== 'RESET' || this.dataLoading) return;
            if (!confirm('{{ __("app.clear_data_desc") }} {{ __("app.are_you_sure") }}')) return;
            this.dataLoading = true;
            this.dataMsg = '';
            try {
                const data = await AbiyTsom.api('/api/member/data/clear', { confirm: 'RESET' });
                if (data.success) {
                    this.dataMsg = '{{ __("app.data_cleared") }}';
                    this.dataMsgError = false;
                    this.clearConfirm = '';
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    this.dataMsg = data.message || '{{ __("app.failed") }}';
                    this.dataMsgError = true;
                }
            } catch (e) {
                this.dataMsg = '{{ __("app.failed_to_clear") }}';
                this.dataMsgError = true;
            } finally {
                this.dataLoading = false;
            }
        }
    };
}

function settingsPage() {
    return {
        locale: '{{ $member?->locale ?? 'en' }}',
        telegramLink: '',
        telegramCode: '',
        telegramLoading: false,
        telegramMsg: '',
        telegramError: '',
        theme: (typeof localStorage !== 'undefined' ? localStorage.getItem('theme') : null) || '{{ $member?->theme ?? 'light' }}',
        baptismName: '{{ addslashes($member?->baptism_name ?? '') }}',
        savedBaptismName: '{{ addslashes($member?->baptism_name ?? '') }}',
        profileSaving: false,
        profileMsg: '',
        profileMsgError: false,
        passcodeEnabled: {{ ($member?->passcode_enabled ?? false) ? 'true' : 'false' }},
        newPasscode: '',
        passcodeMsg: '',

        // WhatsApp reminder state
        waEnabled: {{ ($member?->whatsapp_reminder_enabled ?? false) ? 'true' : 'false' }},
        waPhone: '{{ addslashes($member?->whatsapp_phone ?? '') }}',
        waTime: '{{ $member?->whatsapp_reminder_time ? substr($member->whatsapp_reminder_time, 0, 5) : '18:00' }}',
        waLang: '{{ $member?->whatsapp_language ?? 'en' }}',
        waStatus: '{{ $member?->whatsapp_confirmation_status ?? 'none' }}',
        waSaving: false,
        waMsg: '',
        waMsgError: false,
        waSavedPhone: '{{ addslashes($member?->whatsapp_phone ?? '') }}',
        waSavedTime: '{{ $member?->whatsapp_reminder_time ? substr($member->whatsapp_reminder_time, 0, 5) : '' }}',
        waSavedLang: '{{ $member?->whatsapp_language ?? 'en' }}',

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
        normalizeTimeForInput(value) {
            if (!value || typeof value !== 'string') return '';
            return value.slice(0, 5);
        },
        applyWhatsAppMember(member) {
            if (!member || typeof member !== 'object') return;
            this.waEnabled = !!member.whatsapp_reminder_enabled;
            this.waStatus = member.whatsapp_confirmation_status || (this.waEnabled ? 'confirmed' : 'none');
            this.waPhone = member.whatsapp_phone || this.waPhone;
            this.waSavedPhone = this.waPhone;

            const inputTime = this.normalizeTimeForInput(member.whatsapp_reminder_time);
            if (inputTime) {
                this.waTime = inputTime;
                this.waSavedTime = inputTime;
            }

            if (member.whatsapp_language) {
                this.waLang = member.whatsapp_language;
                this.waSavedLang = member.whatsapp_language;
            }
        },
        get waPhoneValid() {
            return this.normalizeUkPhone(this.waPhone) !== null;
        },
        get waHasChanges() {
            return this.waPhone !== this.waSavedPhone
                || this.waTime !== this.waSavedTime
                || this.waLang !== this.waSavedLang;
        },
        get waStatusLabel() {
            if (this.waStatus === 'pending') return '{{ __("app.settings_whatsapp_pending") }}';
            return this.waEnabled ? '{{ __("app.settings_whatsapp_enabled") }}' : '{{ __("app.settings_whatsapp_not_setup") }}';
        },
        get waStatusDescription() {
            if (this.waStatus === 'pending') return '{{ __("app.settings_whatsapp_desc_pending") }}';
            return this.waEnabled ? '{{ __("app.settings_whatsapp_desc_on") }}' : '{{ __("app.settings_whatsapp_desc_off") }}';
        },
        get telegramStatusLabel() {
            return (this.telegramLink || this.telegramCode) ? '{{ __("app.telegram_settings_link_generated") }}' : '{{ __("app.telegram_settings_link_desc") }}';
        },
        async generateLink() {
            this.telegramLoading = true;
            this.telegramError = '';
            this.telegramMsg = '';
            this.telegramCode = '';
            this.telegramLink = '';
            try {
                const data = await AbiyTsom.api('/api/member/telegram-link', {});
                if (data.success) {
                    this.telegramLink = data.link || '';
                    this.telegramCode = data.code || '';
                    this.telegramMsg = data.message || '{{ __("app.telegram_settings_link_generated") }}';
                } else {
                    this.telegramError = data.message || '{{ __("app.failed") }}';
                }
            } catch (e) {
                this.telegramError = '{{ __("app.failed") }}';
            } finally {
                this.telegramLoading = false;
            }
        },
        copyCode() {
            if (!this.telegramCode) return;
            navigator.clipboard.writeText(this.telegramCode).then(() => {
                this.telegramMsg = 'Code copied. Type it in the Telegram bot.';
                setTimeout(() => { this.telegramMsg = '{{ __("app.telegram_settings_link_generated") }}'; }, 2000);
            });
        },
        copyLink() {
            if (!this.telegramLink) return;
            navigator.clipboard.writeText(this.telegramLink).then(() => {
                this.telegramMsg = 'Link copied. Open it in Telegram.';
                setTimeout(() => { this.telegramMsg = '{{ __("app.telegram_settings_link_generated") }}'; }, 2000);
            });
        },
        async unlinkTelegram() {
            if (!confirm('{{ __("app.telegram_settings_unlink_confirm") }}')) return;
            this.telegramLoading = true;
            this.telegramError = '';
            this.telegramMsg = '';
            try {
                const data = await AbiyTsom.api('/api/member/telegram-unlink', {});
                if (data.success) {
                    this.telegramMsg = data.message || '{{ __("app.telegram_settings_unlinked") }}';
                    this.telegramLink = '';
                    this.telegramCode = '';
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    this.telegramError = data.message || '{{ __("app.failed") }}';
                }
            } catch (e) {
                this.telegramError = '{{ __("app.failed") }}';
            } finally {
                this.telegramLoading = false;
            }
        },

        async enableWhatsApp() {
            if (!this.waPhoneValid || !this.waTime) return;
            this.waSaving = true;
            this.waMsg = '';
            try {
                const phone = this.normalizeUkPhone(this.waPhone) || this.waPhone;
                const data = await AbiyTsom.api('/api/member/settings', {
                    whatsapp_reminder_enabled: true,
                    whatsapp_phone: phone,
                    whatsapp_reminder_time: this.waTime,
                    whatsapp_language: this.waLang,
                });
                if (data.success) {
                    this.applyWhatsAppMember(data.member);
                    this.waMsg = data.message || (data.whatsapp_confirmation_pending
                        ? '{{ __("app.whatsapp_confirmation_pending_notice") }}'
                        : '{{ __("app.settings_whatsapp_enabled") }}');
                    this.waMsgError = false;
                } else {
                    this.waMsg = data.message || '{{ __("app.failed_to_save") }}';
                    this.waMsgError = true;
                }
            } catch (e) {
                this.waMsg = '{{ __("app.failed_to_save") }}';
                this.waMsgError = true;
            } finally {
                this.waSaving = false;
                setTimeout(() => { this.waMsg = ''; }, 4000);
            }
        },

        async toggleWhatsApp() {
            this.waSaving = true;
            this.waMsg = '';
            try {
                const next = !this.waEnabled;
                if (next && (!this.waPhoneValid || !this.waTime)) {
                    this.waMsg = '{{ __("app.whatsapp_reminder_requires_phone_and_time") }}';
                    this.waMsgError = true;
                    this.waSaving = false;
                    return;
                }
                const payload = { whatsapp_reminder_enabled: next };
                if (next) {
                    payload.whatsapp_phone = this.normalizeUkPhone(this.waPhone) || this.waPhone;
                    payload.whatsapp_reminder_time = this.waTime;
                    payload.whatsapp_language = this.waLang;
                }
                const data = await AbiyTsom.api('/api/member/settings', payload);
                if (data.success) {
                    this.applyWhatsAppMember(data.member);
                    this.waMsg = data.message || (next
                        ? '{{ __("app.settings_whatsapp_enabled") }}'
                        : '{{ __("app.settings_whatsapp_disabled") }}');
                    this.waMsgError = false;
                } else {
                    this.waMsg = (data.errors && Object.values(data.errors).flat().length)
                        ? Object.values(data.errors).flat().join(' ')
                        : (data.message || '{{ __("app.failed_to_save") }}');
                    this.waMsgError = true;
                }
            } catch (e) {
                this.waMsg = (e && e.message) ? e.message : '{{ __("app.failed_to_save") }}';
                this.waMsgError = true;
            } finally {
                this.waSaving = false;
                setTimeout(() => { this.waMsg = ''; }, 4000);
            }
        },

        async saveWhatsApp() {
            if (!this.waPhoneValid || !this.waTime || (!this.waHasChanges && this.waStatus !== 'pending')) return;
            this.waSaving = true;
            this.waMsg = '';
            try {
                const phone = this.normalizeUkPhone(this.waPhone) || this.waPhone;
                const payload = {
                    whatsapp_phone: phone,
                    whatsapp_reminder_time: this.waTime,
                    whatsapp_language: this.waLang,
                };
                if (this.waStatus === 'pending') {
                    payload.whatsapp_reminder_enabled = true;
                }
                const data = await AbiyTsom.api('/api/member/settings', payload);
                if (data.success) {
                    this.applyWhatsAppMember(data.member);
                    this.waMsg = data.message || (data.whatsapp_confirmation_pending
                        ? '{{ __("app.whatsapp_confirmation_pending_notice") }}'
                        : '{{ __("app.settings_whatsapp_saved") }}');
                    this.waMsgError = false;
                } else {
                    this.waMsg = data.message || '{{ __("app.failed_to_save") }}';
                    this.waMsgError = true;
                }
            } catch (e) {
                this.waMsg = '{{ __("app.failed_to_save") }}';
                this.waMsgError = true;
            } finally {
                this.waSaving = false;
                setTimeout(() => { this.waMsg = ''; }, 4000);
            }
        },
        async setLocale(lang) {
            this.locale = lang;
            await AbiyTsom.api('/api/member/settings', { locale: lang });
            window.location.replace(AbiyTsom.baseUrl + '/member/settings?lang=' + lang);
        },
        async saveBaptismName() {
            const name = this.baptismName.trim();
            if (!name) return;
            this.profileSaving = true;
            this.profileMsg = '';
            this.profileMsgError = false;
            try {
                const data = await AbiyTsom.api('/api/member/settings', { baptism_name: name });
                if (data.success) {
                    this.savedBaptismName = name;
                    this.profileMsg = '{{ __("app.baptism_name_saved") }}';
                    this.profileMsgError = false;
                    setTimeout(() => { this.profileMsg = ''; }, 3000);
                } else {
                    this.profileMsg = data.message || '{{ __("app.failed_to_save") }}';
                    this.profileMsgError = true;
                }
            } catch (e) {
                this.profileMsg = '{{ __("app.failed_to_save") }}';
                this.profileMsgError = true;
            } finally {
                this.profileSaving = false;
            }
        },
        syncThemeFromStorage() {
            const stored = localStorage.getItem('theme');
            if (!stored && this.theme) {
                localStorage.setItem('theme', this.theme);
                document.documentElement.classList.toggle('dark', this.theme === 'dark');
            }
        },
        async setTheme(t) {
            this.theme = t;
            localStorage.setItem('theme', t);
            document.documentElement.classList.toggle('dark', t === 'dark');
            await AbiyTsom.api('/api/member/settings', { theme: t });
        },
        async enablePasscode() {
            if (this.newPasscode.length < 4) return;
            const data = await AbiyTsom.api('/member/passcode/update', { passcode: this.newPasscode, enabled: true });
            if (data.success) {
                this.passcodeEnabled = true;
                this.passcodeMsg = '{{ __("app.passcode_saved") }}';
                this.newPasscode = '';
            }
        },
        async disablePasscode() {
            const data = await AbiyTsom.api('/member/passcode/update', { passcode: null, enabled: false });
            if (data.success) {
                this.passcodeEnabled = false;
                this.passcodeMsg = '';
            }
        }
    };
}
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => { window.AbiyTsomContinueTour?.('settings'); }, 500);
});
</script>
@endpush
