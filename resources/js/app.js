import './bootstrap';
import Alpine from 'alpinejs';
import 'driver.js/dist/driver.css';
import { startMemberTour, continuePageTour, isTourCompleted, resetTour, syncTourCompletion } from './tour.js';

window.Alpine = Alpine;
window.AbiyTsomStartTour    = startMemberTour;
window.AbiyTsomContinueTour = continuePageTour;
window.AbiyTsomIsTourCompleted = isTourCompleted;
window.AbiyTsomResetTour = resetTour;

// Register global app store (replaces x-data on <html> for performance)
document.addEventListener('alpine:init', () => {
    const publicFasika = document.documentElement.dataset.abiyPublicFasika === '1';
    const serverTheme = document.documentElement.dataset.serverTheme || 'sepia';
    const stored = localStorage.getItem('theme');
    const initialTheme = publicFasika
        ? 'dark'
        : ((stored === 'light' || stored === 'sepia' || stored === 'dark')
            ? stored
            : ((serverTheme === 'light' || serverTheme === 'sepia' || serverTheme === 'dark') ? serverTheme : 'sepia'));

    Alpine.store('app', {
        theme: initialTheme,
        locale: document.documentElement.lang || 'en',

        applyThemeClasses() {
            document.documentElement.classList.toggle('dark', this.theme === 'dark');
            document.documentElement.classList.toggle('theme-sepia', this.theme === 'sepia');
        },

        toggleTheme() {
            const order = ['light', 'sepia', 'dark'];
            const i = order.indexOf(this.theme);
            this.theme = order[(i + 1) % 3];
            localStorage.setItem('theme', this.theme);
            this.applyThemeClasses();
            if (window.AbiyTsom?.api) { AbiyTsom.api('/api/member/settings', { theme: this.theme }); }
        },

        setLocale(lang) {
            this.locale = lang;
            if (window.AbiyTsom?.api) {
                AbiyTsom.api('/api/member/settings', { locale: lang }).then(() => {
                    window.location.reload();
                });
            } else {
                window.location.reload();
            }
        }
    });
});

Alpine.start();

window.AbiyTsomPersistentAuth = {
    store(payload) {
        const storageKey = window.AbiyTsomPersistentConfig?.storageKey;
        if (!storageKey || !payload) return;

        try {
            window.localStorage.setItem(storageKey, payload);
        } catch (error) {}
    },

    clear() {
        const storageKey = window.AbiyTsomPersistentConfig?.storageKey;
        if (!storageKey) return;

        try {
            window.localStorage.removeItem(storageKey);
        } catch (error) {}
    },

    async restore(payload, expectedMemberToken = null) {
        const config = window.AbiyTsomPersistentConfig;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!config?.restoreUrl || !csrfToken || !payload) {
            return { success: false };
        }

        try {
            const response = await fetch(config.restoreUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    remember_token: payload,
                    expected_member_token: expectedMemberToken,
                }),
            });

            const data = await response.json().catch(() => ({}));

            return {
                success: !!data?.success && response.ok,
                rememberToken: data?.remember_token || payload,
                memberMismatch: !!data?.member_mismatch,
                networkError: false,
            };
        } catch (error) {
            return {
                success: false,
                networkError: true,
                memberMismatch: false,
            };
        }
    },

    async bootstrap() {
        const config = window.AbiyTsomPersistentConfig;
        if (!config) return;

        if (config.payload) {
            this.store(config.payload);
        }

        if (config.maskUrl && config.cleanUrl && window.location.href !== config.cleanUrl) {
            window.history.replaceState({}, '', config.cleanUrl);
        }

        if (!config.allowAutoRestore) {
            return;
        }

        let stored = null;
        try {
            stored = window.localStorage.getItem(config.storageKey);
        } catch (error) {
            stored = null;
        }

        if (!stored || window.__abiyPersistentRestoreRunning) {
            return;
        }

        window.__abiyPersistentRestoreRunning = true;

        const result = await this.restore(stored, config.expectedMemberToken || null);
        if (result.success) {
            this.store(result.rememberToken);
            window.location.replace(config.cleanUrl || window.location.href);
            return;
        }

        if (!result.memberMismatch && !result.networkError) {
            this.clear();
        }
    },
};

document.addEventListener('DOMContentLoaded', () => {
    window.AbiyTsomPersistentAuth?.bootstrap();
});

// Backfill: sync localStorage completion to DB for pre-existing members
syncTourCompletion();
