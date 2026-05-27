const cookieKey = 'ke_cookie_preferences';
const accessibilityKey = 'ke_accessibility';

function readJson(key, fallback = {}) {
    try {
        return JSON.parse(localStorage.getItem(key) || '') || fallback;
    } catch {
        return fallback;
    }
}

function writeJson(key, value) {
    localStorage.setItem(key, JSON.stringify(value));
}

function applyTheme(theme) {
    const value = theme || 'auto';
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    document.documentElement.dataset.theme = value;
    document.documentElement.classList.toggle('dark', value === 'dark' || (value === 'auto' && prefersDark));
}

function setupCookieModal() {
    const modal = document.querySelector('[data-cookie-modal]');

    if (!modal) {
        return;
    }

    const openButtons = document.querySelectorAll('[data-cookie-open]');
    const categories = modal.querySelectorAll('[data-cookie-category]');
    const current = readJson(cookieKey, { necessary: true, analytics: false, marketing: false, hasDecision: false });
    const setVisible = (visible) => modal.classList.toggle('hidden', !visible);
    const syncInputs = (state) => {
        categories.forEach((input) => {
            input.checked = Boolean(state[input.dataset.cookieCategory]);
        });
    };
    const save = (state) => {
        writeJson(cookieKey, { necessary: true, analytics: false, marketing: false, ...state, hasDecision: true });
        setVisible(false);
    };

    syncInputs(current);
    openButtons.forEach((button) => button.addEventListener('click', () => setVisible(true)));
    modal.querySelector('[data-cookie-necessary]')?.addEventListener('click', () => save({ analytics: false, marketing: false }));
    modal.querySelector('[data-cookie-all]')?.addEventListener('click', () => save({ analytics: true, marketing: true }));
    modal.querySelector('[data-cookie-save]')?.addEventListener('click', () => {
        const state = { necessary: true };
        categories.forEach((input) => {
            state[input.dataset.cookieCategory] = input.checked;
        });
        save(state);
    });
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            setVisible(false);
        }
    });

    if (!current.hasDecision) {
        setVisible(true);
    }
}

function setupAccessibilityModal() {
    const modal = document.querySelector('[data-accessibility-modal]');

    if (!modal) {
        return;
    }

    const openButtons = document.querySelectorAll('[data-accessibility-open]');
    const current = readJson(accessibilityKey, { theme: 'auto' });
    const setVisible = (visible) => modal.classList.toggle('hidden', !visible);

    modal.querySelector(`[name="theme"][value="${current.theme || 'auto'}"]`)?.setAttribute('checked', 'checked');
    openButtons.forEach((button) => button.addEventListener('click', () => setVisible(true)));
    modal.querySelector('[data-accessibility-close]')?.addEventListener('click', () => setVisible(false));
    modal.querySelector('[data-accessibility-save]')?.addEventListener('click', () => {
        const theme = modal.querySelector('[name="theme"]:checked')?.value || 'auto';
        writeJson(accessibilityKey, { theme });
        applyTheme(theme);
        setVisible(false);
    });
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            setVisible(false);
        }
    });

    applyTheme(current.theme);
}

function setupDashboardTabs() {
    const tabs = document.querySelectorAll('[data-dashboard-tab]');
    const panels = document.querySelectorAll('[data-dashboard-panel]');

    if (!tabs.length || !panels.length) {
        return;
    }

    const show = (target) => {
        tabs.forEach((tab) => {
            const active = tab.dataset.dashboardTab === target;
            tab.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        panels.forEach((panel) => {
            panel.classList.toggle('hidden', panel.dataset.dashboardPanel !== target);
        });
    };

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => show(tab.dataset.dashboardTab));
    });

    const preferredTab = window.location.hash.replace('#', '');
    const errorPanel = [...panels].find((panel) => panel.querySelector('.text-red-700'));
    const initialTab = errorPanel?.dataset.dashboardPanel
        || ([...tabs].some((tab) => tab.dataset.dashboardTab === preferredTab) ? preferredTab : 'aanvragen');
    show(initialTab);
}

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    applyTheme(readJson(accessibilityKey, { theme: 'auto' }).theme);
});

setupCookieModal();
setupAccessibilityModal();
setupDashboardTabs();
