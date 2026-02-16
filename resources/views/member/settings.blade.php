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

        {{-- Custom Activities --}}
        <div class="bg-card rounded-2xl shadow-sm border border-border overflow-hidden">
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
                    <form @submit.prevent="addActivity()" class="flex flex-wrap gap-2 mb-3">
                        <input type="text" x-model="newName" maxlength="255"
                               :placeholder="'{{ __('app.custom_activity_placeholder') }}'"
                               class="min-w-0 flex-1 basis-24 px-4 py-2.5 border border-border rounded-xl bg-muted text-primary text-sm outline-none focus:ring-2 focus:ring-accent">
                        <button type="submit" :disabled="!newName.trim()"
                                class="shrink-0 px-4 py-2.5 bg-accent text-on-accent rounded-xl font-medium text-sm disabled:opacity-50 transition">
                            {{ __('app.add') }}
                        </button>
                    </form>
                    <ul class="space-y-2 overflow-hidden" x-show="customActivities.length > 0">
                        <template x-for="activity in customActivities" :key="activity.id">
                            <li class="flex items-center justify-between gap-2 p-3 rounded-xl bg-muted min-w-0">
                                <span class="text-sm font-medium text-primary min-w-0 truncate" x-text="activity.name"></span>
                                <button type="button" @click="deleteActivity(activity.id)"
                                        class="shrink-0 p-1.5 text-error hover:bg-error/10 rounded-lg transition"
                                        :aria-label="'{{ __('app.delete') }}'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
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
        async addActivity() {
            const name = this.newName.trim();
            if (!name) return;
            const data = await AbiyTsom.api('/api/member/custom-activities', { name });
            if (data.success) {
                this.customActivities.push(data.activity);
                this.newName = '';
                this.msg = '{{ __("app.custom_activity_added") }}';
                this.msgError = false;
                setTimeout(() => { this.msg = ''; }, 3000);
            } else {
                this.msg = data.message || '{{ __("app.failed_to_add") }}';
                this.msgError = true;
            }
        },
        async deleteActivity(id) {
            const data = await AbiyTsom.api('/api/member/custom-activities/delete', { id });
            if (data.success) {
                this.customActivities = this.customActivities.filter(a => a.id !== id);
                this.msg = '{{ __("app.custom_activity_deleted") }}';
                this.msgError = false;
                setTimeout(() => { this.msg = ''; }, 3000);
            }
        }
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
                const url = AbiyTsom.baseUrl + '/api/member/data/export?token=' + encodeURIComponent(AbiyTsom.token);
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
        theme: (typeof localStorage !== 'undefined' ? localStorage.getItem('theme') : null) || '{{ $member?->theme ?? 'light' }}',
        baptismName: '{{ addslashes($member?->baptism_name ?? '') }}',
        savedBaptismName: '{{ addslashes($member?->baptism_name ?? '') }}',
        profileSaving: false,
        profileMsg: '',
        profileMsgError: false,
        passcodeEnabled: {{ ($member?->passcode_enabled ?? false) ? 'true' : 'false' }},
        newPasscode: '',
        passcodeMsg: '',
        async setLocale(lang) {
            this.locale = lang;
            await AbiyTsom.api('/api/member/settings', { locale: lang });
            window.location.replace(AbiyTsom.baseUrl + '/member/settings?lang=' + lang + '&token=' + AbiyTsom.token);
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
</script>
@endpush
