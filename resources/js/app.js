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
    const serverTheme = document.documentElement.dataset.serverTheme || 'sepia';
    const stored = localStorage.getItem('theme');
    const initialTheme = (stored === 'light' || stored === 'sepia' || stored === 'dark')
        ? stored
        : ((serverTheme === 'light' || serverTheme === 'sepia' || serverTheme === 'dark') ? serverTheme : 'sepia');

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

// Backfill: sync localStorage completion to DB for pre-existing members
syncTourCompletion();