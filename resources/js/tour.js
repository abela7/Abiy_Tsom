/**
 * Member app tour (Driver.js) — language & theme on home screen.
 * Tour content comes from window.AbiyTsomTourContent (set by Blade).
 * Completion is stored server-side (tour_completed_at) and synced to localStorage.
 */

const TOUR_STORAGE_KEY = 'member_tour_completed';
const TOUR_STEP_KEY    = 'member_tour_step'; // sessionStorage — survives reload, not tab close

export function isTourCompleted() {
    if (window.AbiyTsomTourCompleted === true) return true;
    // Server explicitly says not completed — clear any stale localStorage so admin
    // resets take effect immediately and syncTourCompletion does not re-backfill.
    if (window.AbiyTsomTourCompleted === false) {
        try { localStorage.removeItem(TOUR_STORAGE_KEY); } catch {}
        return false;
    }
    // Server value unknown (page without tour tracking) — fall back to localStorage.
    try {
        return localStorage.getItem(TOUR_STORAGE_KEY) === '1';
    } catch {
        return false;
    }
}

function setTourCompletedLocal() {
    try {
        localStorage.setItem(TOUR_STORAGE_KEY, '1');
    } catch {}
}

export async function setTourCompleted() {
    setTourCompletedLocal();
    try {
        if (window.AbiyTsom?.api) {
            await window.AbiyTsom.api('/api/member/tour/complete', {});
        }
    } catch {}
}

export async function resetTour() {
    try {
        localStorage.removeItem(TOUR_STORAGE_KEY);
        if (window.AbiyTsom?.api) {
            await window.AbiyTsom.api('/api/member/tour/reset', {});
        }
    } catch {}
}

/**
 * Sync localStorage tour completion to the server.
 * Runs on page load for members who completed the tour before server-side
 * tracking was introduced — their localStorage has '1' but DB has NULL.
 */
export function syncTourCompletion() {
    if (window.AbiyTsomTourCompleted === true) return; // already in sync
    try {
        if (localStorage.getItem(TOUR_STORAGE_KEY) === '1') {
            setTourCompleted(); // silently backfill the DB record
        }
    } catch {}
}

/** Boost the language dropdown above the Driver.js overlay when on the language step. */
function setLangDropdownZIndex(zIndex) {
    try {
        const el = document.querySelector('[data-tour="language"] .fixed');
        if (el) el.style.zIndex = zIndex;
    } catch {}
}

export async function startMemberTour(force = false) {
    if (!force && isTourCompleted()) return;

    const content = window.AbiyTsomTourContent;
    if (!content) return;

    const steps = [
        {
            popover: {
                title: content.welcome?.title ?? 'Welcome',
                description: content.welcome?.desc ?? '',
                side: 'bottom',
                align: 'center',
            },
        },
        {
            element: '[data-tour="language"]',
            popover: {
                title: content.language?.title ?? 'Language',
                description: content.language?.desc ?? '',
                side: 'bottom',
                align: 'end',
            },
        },
        {
            element: '[data-tour="theme"]',
            popover: {
                title: content.theme?.title ?? 'Theme',
                description: content.theme?.desc ?? '',
                side: 'bottom',
                align: 'end',
            },
        },
    ].filter((s) => !s.element || document.querySelector(s.element));

    if (steps.length < 2) return;

    try {
        const { driver } = await import('driver.js');

        const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent) || window.innerWidth < 768;

        // Resume from saved step (e.g. after language change triggers a page reload).
        let resumeStep = 0;
        try {
            const saved = sessionStorage.getItem(TOUR_STEP_KEY);
            if (saved !== null) {
                resumeStep = Math.max(0, Math.min(parseInt(saved, 10) || 0, steps.length - 1));
                sessionStorage.removeItem(TOUR_STEP_KEY);
            }
        } catch {}

        const driverObj = driver({
            showProgress: true,
            allowClose: false,      // overlay click does NOT close the tour
            smoothScroll: true,
            overlayOpacity: 0.6,
            stagePadding: isMobile ? 8 : 10,
            popoverOffset: isMobile ? 8 : 10,
            nextBtnText: content.next ?? 'Next',
            prevBtnText: content.prev ?? 'Back',
            doneBtnText: content.done ?? 'Done',
            progressText: content.progressText ?? '{{current}} of {{total}}',
            steps,
            onHighlightStarted: (element, _step, opts) => {
                // Persist current step so a page reload (e.g. language switch) can resume here.
                const idx = opts?.state?.activeIndex ?? 0;
                try { sessionStorage.setItem(TOUR_STEP_KEY, String(idx)); } catch {}

                // Language dropdown is position:fixed with z-index 9999, which sits below
                // the Driver.js overlay (~10000). Boost it when the language step is active.
                if (element?.dataset?.tour === 'language') {
                    setLangDropdownZIndex('100002');
                } else {
                    setLangDropdownZIndex('9999');
                }
            },
            onDestroyed: () => {
                // Clear resume key and z-index fix, then mark tour done.
                try { sessionStorage.removeItem(TOUR_STEP_KEY); } catch {}
                setLangDropdownZIndex('9999');
                setTourCompleted();
            },
        });

        driverObj.drive(resumeStep);
    } catch (e) {
        console.warn('Tour: failed to start', e);
    }
}
