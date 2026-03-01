/**
 * Member app tour (Driver.js) — multi-page guided tour.
 * Tour content comes from window.AbiyTsomTourContent (set by Blade).
 * Phases: home → calendar → day → settings.
 * State is kept in sessionStorage (survives reload, clears on tab close).
 */

const TOUR_STORAGE_KEY  = 'member_tour_completed';
const TOUR_STEP_KEY     = 'member_tour_step';
const TOUR_PHASE_KEY    = 'member_tour_phase';
const TOUR_DAY_URL_KEY  = 'member_tour_day_url';

// ─── localStorage / server completion ─────────────────────────────────────

export function isTourCompleted() {
    if (window.AbiyTsomTourCompleted === true) return true;
    // Server explicitly says not completed — clear stale localStorage so admin
    // resets take effect immediately.
    if (window.AbiyTsomTourCompleted === false) {
        try { localStorage.removeItem(TOUR_STORAGE_KEY); } catch {}
        return false;
    }
    // Server value unknown — fall back to localStorage.
    try { return localStorage.getItem(TOUR_STORAGE_KEY) === '1'; } catch { return false; }
}

function setTourCompletedLocal() {
    try { localStorage.setItem(TOUR_STORAGE_KEY, '1'); } catch {}
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

export function syncTourCompletion() {
    if (window.AbiyTsomTourCompleted === true) return;
    try {
        if (localStorage.getItem(TOUR_STORAGE_KEY) === '1') {
            setTourCompleted();
        }
    } catch {}
}

// ─── sessionStorage phase / step helpers ──────────────────────────────────

function savePhase(phase) { try { sessionStorage.setItem(TOUR_PHASE_KEY, phase); } catch {} }
function getPhase()       { try { return sessionStorage.getItem(TOUR_PHASE_KEY); } catch { return null; } }
function saveStep(idx)    { try { sessionStorage.setItem(TOUR_STEP_KEY, String(idx)); } catch {} }

function clearPhase() {
    try {
        sessionStorage.removeItem(TOUR_PHASE_KEY);
        sessionStorage.removeItem(TOUR_STEP_KEY);
        sessionStorage.removeItem(TOUR_DAY_URL_KEY);
    } catch {}
}

function getSavedStep(stepsLength) {
    try {
        const saved = sessionStorage.getItem(TOUR_STEP_KEY);
        if (saved !== null) {
            const idx = parseInt(saved, 10) || 0;
            sessionStorage.removeItem(TOUR_STEP_KEY);
            return Math.max(0, Math.min(idx, stepsLength - 1));
        }
    } catch {}
    return 0;
}

// ─── z-index helper for language dropdown ────────────────────────────────

function setLangDropdownZIndex(zIndex) {
    try {
        const el = document.querySelector('[data-tour="language"] .fixed');
        if (el) el.style.zIndex = zIndex;
    } catch {}
}

// ─── Phase navigation ─────────────────────────────────────────────────────

const PHASE_ORDER = ['home', 'calendar', 'day', 'settings'];

/** Called when a phase completes (Done button on last step). */
function navigateAfterPhase(phase) {
    const base      = window.AbiyTsom?.baseUrl ?? '';
    const nextPhase = PHASE_ORDER[PHASE_ORDER.indexOf(phase) + 1];

    if (!nextPhase) {
        // All phases done — mark complete and return to home.
        clearPhase();
        setTourCompleted();
        window.location.href = base + '/member/home';
        return;
    }

    savePhase(nextPhase);

    switch (nextPhase) {
        case 'calendar':
            window.location.href = base + '/member/calendar';
            break;

        case 'day': {
            // Navigate directly to today's day cell link.
            const todayLink = document.querySelector('#current-day a')?.href;
            if (todayLink) {
                try { sessionStorage.setItem(TOUR_DAY_URL_KEY, todayLink); } catch {}
                window.location.href = todayLink;
            } else {
                // No today entry — skip day phase and go to settings.
                savePhase('settings');
                window.location.href = base + '/member/settings';
            }
            break;
        }

        case 'settings':
            window.location.href = base + '/member/settings';
            break;
    }
}

/** Called when the user arrives at home mid-tour (redirects to saved phase page). */
function navigateToPhase(phase) {
    const base = window.AbiyTsom?.baseUrl ?? '';
    switch (phase) {
        case 'calendar':
            window.location.href = base + '/member/calendar';
            break;
        case 'day': {
            const saved = (() => { try { return sessionStorage.getItem(TOUR_DAY_URL_KEY); } catch { return null; } })();
            if (saved) {
                window.location.href = saved;
            } else {
                // No saved day URL — go back through calendar so it can pick up today's link.
                savePhase('calendar');
                window.location.href = base + '/member/calendar';
            }
            break;
        }
        case 'settings':
            window.location.href = base + '/member/settings';
            break;
    }
}

// ─── Step builders ────────────────────────────────────────────────────────

function buildHomeSteps(c) {
    return [
        {
            popover: {
                title:       c?.home?.welcome?.title  ?? 'Welcome',
                description: c?.home?.welcome?.desc   ?? '',
                side: 'bottom', align: 'center',
            },
        },
        {
            element: '[data-tour="language"]',
            popover: {
                title:       c?.home?.language?.title ?? 'Language',
                description: c?.home?.language?.desc  ?? '',
                side: 'bottom', align: 'end',
            },
        },
        {
            element: '[data-tour="theme"]',
            popover: {
                title:       c?.home?.theme?.title    ?? 'Theme',
                description: c?.home?.theme?.desc     ?? '',
                side: 'bottom', align: 'end',
            },
        },
        {
            element: '[data-tour="home-countdown"]',
            popover: {
                title:       c?.home?.countdown?.title ?? 'Easter Countdown',
                description: c?.home?.countdown?.desc  ?? '',
                side: 'bottom', align: 'center',
            },
        },
        {
            element: '[data-tour="view-today"]',
            popover: {
                title:       c?.home?.viewToday?.title ?? 'Your Daily Content',
                description: c?.home?.viewToday?.desc  ?? '',
                side: 'bottom', align: 'start',
            },
        },
    ].filter((s) => !s.element || document.querySelector(s.element));
}

function buildCalendarSteps(c) {
    return [
        {
            element: '[data-tour="cal-legend"]',
            popover: {
                title:       c?.calendar?.legend?.title ?? 'Day Status',
                description: c?.calendar?.legend?.desc  ?? '',
                side: 'bottom', align: 'center',
            },
        },
        {
            element: '[data-tour="cal-week"]',
            popover: {
                title:       c?.calendar?.week?.title ?? 'Weekly Themes',
                description: c?.calendar?.week?.desc  ?? '',
                side: 'bottom', align: 'start',
            },
        },
        {
            element: '[data-tour="cal-today"]',
            popover: {
                title:       c?.calendar?.today?.title ?? 'Today',
                description: c?.calendar?.today?.desc  ?? '',
                side: 'bottom', align: 'center',
            },
        },
    ].filter((s) => !s.element || document.querySelector(s.element));
}

function buildDaySteps(c) {
    return [
        {
            element: '[data-tour="day-header"]',
            popover: {
                title:       c?.day?.header?.title ?? "Today's Reading",
                description: c?.day?.header?.desc  ?? '',
                side: 'bottom', align: 'start',
            },
        },
        {
            element: '[data-tour="day-bible"]',
            popover: {
                title:       c?.day?.bible?.title ?? 'Bible Reading',
                description: c?.day?.bible?.desc  ?? '',
                side: 'bottom', align: 'start',
            },
        },
        {
            element: '[data-tour="day-mezmur"]',
            popover: {
                title:       c?.day?.mezmur?.title ?? 'Mezmur',
                description: c?.day?.mezmur?.desc  ?? '',
                side: 'bottom', align: 'start',
            },
        },
        {
            element: '[data-tour="day-sinksar"]',
            popover: {
                title:       c?.day?.sinksar?.title ?? 'Sinksar',
                description: c?.day?.sinksar?.desc  ?? '',
                side: 'bottom', align: 'start',
            },
        },
        {
            element: '[data-tour="day-book"]',
            popover: {
                title:       c?.day?.book?.title ?? 'Spiritual Reading',
                description: c?.day?.book?.desc  ?? '',
                side: 'bottom', align: 'start',
            },
        },
        {
            element: '[data-tour="day-references"]',
            popover: {
                title:       c?.day?.references?.title ?? 'References',
                description: c?.day?.references?.desc  ?? '',
                side: 'bottom', align: 'start',
            },
        },
        {
            element: '[data-tour="day-checklist"]',
            popover: {
                title:       c?.day?.checklist?.title ?? 'Daily Checklist',
                description: c?.day?.checklist?.desc  ?? '',
                side: 'bottom', align: 'start',
            },
        },
        {
            element: '[data-tour="day-custom"]',
            popover: {
                title:       c?.day?.custom?.title ?? 'Custom Activities',
                description: c?.day?.custom?.desc  ?? '',
                side: 'bottom', align: 'start',
            },
        },
        {
            element: '[data-tour="day-checklist"]',
            popover: {
                title:       c?.day?.privacy?.title ?? 'Your Privacy',
                description: c?.day?.privacy?.desc  ?? '',
                side: 'top', align: 'center',
            },
        },
    ].filter((s) => !s.element || document.querySelector(s.element));
}

function buildSettingsSteps(c) {
    return [
        {
            element: '[data-tour="settings-whatsapp"]',
            popover: {
                title:       c?.settings?.whatsapp?.title ?? 'WhatsApp Reminders',
                description: c?.settings?.whatsapp?.desc  ?? '',
                side: 'bottom', align: 'start',
            },
        },
        {
            element: '[data-tour="settings-telegram"]',
            popover: {
                title:       c?.settings?.telegram?.title ?? 'Telegram Bot',
                description: c?.settings?.telegram?.desc  ?? '',
                side: 'bottom', align: 'start',
            },
        },
        {
            element: '[data-tour="settings-custom"]',
            popover: {
                title:       c?.settings?.custom?.title ?? 'Custom Activities',
                description: c?.settings?.custom?.desc  ?? '',
                side: 'bottom', align: 'start',
            },
        },
        {
            element: '[data-tour="settings-passcode"]',
            popover: {
                title:       c?.settings?.passcode?.title ?? 'Passcode Lock',
                description: c?.settings?.passcode?.desc  ?? '',
                side: 'bottom', align: 'start',
            },
        },
        {
            element: '[data-tour="settings-tour"]',
            popover: {
                title:       c?.settings?.done?.title ?? "You're All Set!",
                description: c?.settings?.done?.desc  ?? '',
                side: 'bottom', align: 'start',
            },
        },
    ].filter((s) => !s.element || document.querySelector(s.element));
}

// ─── Core tour runner ─────────────────────────────────────────────────────

async function runPhaseTour(phase, steps) {
    if (steps.length === 0) {
        navigateAfterPhase(phase);
        return;
    }

    const resumeStep = getSavedStep(steps.length);

    try {
        const { driver } = await import('driver.js');
        const isMobile   = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent) || window.innerWidth < 768;
        const content    = window.AbiyTsomTourContent;

        // Flag set only when user intentionally clicks Done on the last step.
        // Page refresh/unload also triggers onDestroyed — this guards against
        // treating an unload as tour completion.
        let phaseCompleted = false;

        // Attach onNextClick to the last step so we know the user clicked Done.
        let driverObj;
        const stepsWithCompletion = steps.map((step, i) =>
            i === steps.length - 1
                ? {
                    ...step,
                    popover: {
                        ...step.popover,
                        onNextClick: () => {
                            phaseCompleted = true;
                            driverObj?.destroy();
                        },
                    },
                  }
                : step
        );

        driverObj = driver({
            showProgress:            true,
            allowClose:              false,          // overlay click does NOT close the tour
            showButtons:             ['next', 'previous'], // no X button — tour must be completed
            disableActiveInteraction: true,          // highlighted element is view-only, not clickable
            smoothScroll:            true,
            overlayOpacity:          0.6,
            stagePadding:   isMobile ? 8 : 10,
            popoverOffset:  isMobile ? 8 : 10,
            nextBtnText:    content?.next         ?? 'Next',
            prevBtnText:    content?.prev         ?? 'Back',
            doneBtnText:    content?.done         ?? 'Done',
            progressText:   content?.progressText ?? '{{current}} of {{total}}',
            steps: stepsWithCompletion,

            onHighlightStarted: (element, _step, opts) => {
                // Save step for language-change resume (best-effort — some Driver.js builds
                // don't expose state.activeIndex, so we fall back to a no-op).
                const idx = opts?.state?.activeIndex;
                if (typeof idx === 'number') saveStep(idx);
                // Boost language dropdown z-index above the Driver.js popover (z-index 1 billion).
                if (element?.dataset?.tour === 'language') {
                    setLangDropdownZIndex('1000000001');
                } else {
                    setLangDropdownZIndex('9999');
                }
            },

            onDestroyed: () => {
                try { sessionStorage.removeItem(TOUR_STEP_KEY); } catch {}
                setLangDropdownZIndex('9999');
                // Only advance the tour if the user intentionally clicked Done.
                // Refreshing the page also fires onDestroyed — without this guard
                // a refresh on the settings phase would call setTourCompleted()
                // and make the tour vanish permanently.
                if (phaseCompleted) {
                    navigateAfterPhase(phase);
                }
            },
        });

        driverObj.drive(resumeStep);
    } catch (e) {
        console.warn('Tour: failed to start phase', phase, e);
    }
}

// ─── Public API ───────────────────────────────────────────────────────────

/**
 * Start or resume the tour from the home page.
 * force=true restarts the whole tour from step 0.
 */
export async function startMemberTour(force = false) {
    // If a blocking modal (fundraising popup) is still visible, wait for it to
    // close before starting the tour so they don't overlap.
    if (document.body.classList.contains('fund-body-lock')) {
        await new Promise(resolve => {
            window.addEventListener('fundraising-ready', resolve, { once: true });
        });
        // Extra pause for the modal's exit animation to finish.
        await new Promise(resolve => setTimeout(resolve, 350));
    }

    if (force) {
        clearPhase();
    }

    if (!force && isTourCompleted()) {
        return;
    }

    // If the user is mid-tour on a different phase, redirect there.
    const savedPhase = getPhase();
    if (savedPhase && savedPhase !== 'home') {
        navigateToPhase(savedPhase);
        return;
    }

    savePhase('home');
    const steps = buildHomeSteps(window.AbiyTsomTourContent);
    if (steps.length < 2) return;
    await runPhaseTour('home', steps);
}

/**
 * Called on non-home member pages (calendar, day, settings).
 * Only runs if the saved tour phase matches this page.
 */
export async function continuePageTour(pageName) {
    // Check the active phase FIRST — if we're mid-tour the session phase is the
    // source of truth, even if the DB/localStorage completed flag is stale.
    const savedPhase = getPhase();
    if (savedPhase !== pageName) return;

    // Phase matches — we are actively mid-tour. Skip the completed check so a
    // stale tour_completed_at from a previous run doesn't block continuation.

    const content = window.AbiyTsomTourContent;
    let steps;

    switch (pageName) {
        case 'calendar': steps = buildCalendarSteps(content); break;
        case 'day':      steps = buildDaySteps(content);      break;
        case 'settings': steps = buildSettingsSteps(content); break;
        default: return;
    }

    await runPhaseTour(pageName, steps);
}
