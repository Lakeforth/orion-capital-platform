// Multi-select checkbox dropdowns for the Leads advanced filter.
//
// This file owns the WIDGET only — opening, closing, select-all, option search and the chip
// summary. It never fetches anything. Ticking a box fires a native change event that bubbles to
// the page-level handler in leadmax-leads.js, which is what re-runs the query. Keeping those two
// apart means the control works the same wherever it is dropped, and the page keeps one single
// place that decides when to reload.
//
// Everything is delegated from document: Splade recompiles the page body on navigation, so a
// handler bound to an element would be lost on the next render.

const OPTION_BOXES = '.lm-ms-options input[type="checkbox"]:not([data-lm-ms-all])';

/** Rebuild the closed-state chips and the tri-state "Select all" box from what is ticked. */
const syncControl = (root) => {
    const boxes = [...root.querySelectorAll(OPTION_BOXES)];
    const picked = boxes.filter((box) => box.checked);
    const chips = root.querySelector('[data-lm-ms-chips]');

    if (chips) {
        chips.textContent = '';
        if (picked.length === 0) {
            const empty = document.createElement('span');
            empty.className = 'lm-ms-placeholder';
            empty.textContent = chips.dataset.lmMsPlaceholder || 'All';
            chips.append(empty);
        } else {
            // Two chips then a counter: enough to recognise a selection without the control
            // growing and shoving the rest of the row around.
            for (const box of picked.slice(0, 2)) {
                const chip = document.createElement('span');
                chip.className = 'lm-ms-chip';
                const text = box.closest('label')?.querySelector('.lm-ms-text');
                chip.textContent = text?.textContent.trim() || box.value;
                if (text?.dataset.lmTone) chip.dataset.lmTone = text.dataset.lmTone;
                chips.append(chip);
            }
            if (picked.length > 2) {
                const more = document.createElement('span');
                more.className = 'lm-ms-chip is-more';
                more.textContent = `+${picked.length - 2}`;
                chips.append(more);
            }
        }
    }

    const all = root.querySelector('[data-lm-ms-all]');
    if (all) {
        all.checked = boxes.length > 0 && picked.length === boxes.length;
        all.indeterminate = picked.length > 0 && !all.checked;
    }
};

document.addEventListener('change', (event) => {
    const root = event.target.closest('[data-lm-ms]');
    if (!root || !event.target.matches('input[type="checkbox"]')) return;

    if (event.target.matches('[data-lm-ms-all]')) {
        // Only the options still visible under the option search, so "Select all" after typing
        // means "all of these" rather than silently selecting things that are filtered out.
        const visible = [...root.querySelectorAll(OPTION_BOXES)].filter((box) => !box.closest('label').hidden);
        visible.forEach((box) => { box.checked = event.target.checked; });
    }

    syncControl(root);
    // The change event that got us here keeps bubbling, so the page handler re-queries once.
}, true);

// Option search. Filters the list in place; it must never reach the page handler, or typing here
// would re-run the lead query.
document.addEventListener('input', (event) => {
    if (!event.target.matches('[data-lm-ms-search]')) return;
    event.stopPropagation();

    const root = event.target.closest('[data-lm-ms]');
    const term = event.target.value.trim().toLowerCase();
    let shown = 0;
    root.querySelectorAll('[data-lm-ms-option]').forEach((label) => {
        const match = !term || label.dataset.lmMsOption.includes(term);
        label.hidden = !match;
        if (match) shown += 1;
    });
    const empty = root.querySelector('[data-lm-ms-empty]');
    if (empty) empty.hidden = shown > 0;
    // "Select all" would act on a filtered list, so hide it when searching narrows things.
    const all = root.querySelector('.lm-ms-all');
    if (all) all.hidden = shown === 0;
});

// Close on outside click and on Escape, matching the export menu's behaviour.
document.addEventListener('click', (event) => {
    document.querySelectorAll('details.lm-ms[open]').forEach((menu) => {
        if (!menu.contains(event.target)) menu.removeAttribute('open');
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const open = event.target.closest?.('details.lm-ms[open]');
    if (open) {
        open.removeAttribute('open');
        open.querySelector('summary')?.focus();
        return;
    }
    document.querySelectorAll('details.lm-ms[open]').forEach((menu) => menu.removeAttribute('open'));
});

/** Reset every multi-select on the page: untick everything and clear the option searches. */
export function clearMultiSelects(scope = document) {
    scope.querySelectorAll('[data-lm-ms]').forEach((root) => {
        root.querySelectorAll('input[type="checkbox"]').forEach((box) => {
            box.checked = false;
            box.indeterminate = false;
        });
        root.querySelectorAll('[data-lm-ms-search]').forEach((input) => { input.value = ''; });
        root.querySelectorAll('[data-lm-ms-option]').forEach((label) => { label.hidden = false; });
        const empty = root.querySelector('[data-lm-ms-empty]');
        if (empty) empty.hidden = true;
        root.removeAttribute('open');
        syncControl(root);
    });
}

// Calendar: the Add Event dialog, and the Filters toggle over the date picker. Delegated like
// everything else here, because Splade rebuilds the page body on navigation.
document.addEventListener('click', (event) => {
    if (event.target.closest('[data-lm-cal-open]')) {
        const dialog = document.querySelector('[data-lm-cal-dialog]');
        if (!dialog) return;
        dialog.hidden = false;
        document.body.classList.add('lm-dialog-open');
        dialog.querySelector('input, select')?.focus();
        return;
    }

    const backdrop = event.target.closest('[data-lm-cal-dialog]');
    if (backdrop && (event.target === backdrop || event.target.closest('[data-lm-cal-close]'))) {
        backdrop.hidden = true;
        document.body.classList.remove('lm-dialog-open');
        return;
    }

    const filters = event.target.closest('[data-lm-cal-filters]');
    if (filters) {
        const panel = document.querySelector('[data-lm-cal-find]');
        if (!panel) return;
        panel.hidden = !panel.hidden;
        filters.setAttribute('aria-expanded', String(!panel.hidden));
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const dialog = document.querySelector('[data-lm-cal-dialog]:not([hidden])');
    if (!dialog) return;
    dialog.hidden = true;
    document.body.classList.remove('lm-dialog-open');
});

/* ---------------------------------------------------------------------------
   Calendar: swap in place rather than reload.

   Switching between the summary cards, the views, the months or the day strip
   is a change of what the calendar is showing, not a change of page -- a full
   load rebuilds the whole shell and throws away the scroll position.
   --------------------------------------------------------------------------- */
const LM_CALENDAR = '[data-lm-calendar]';
let lmCalendarRequest = null;

const loadCalendar = async (url, push = true) => {
    const current = document.querySelector(LM_CALENDAR);
    if (!current) { location.assign(url); return; }

    lmCalendarRequest?.abort();            // a newer click supersedes one still in flight
    lmCalendarRequest = new AbortController();
    current.setAttribute('aria-busy', 'true');

    try {
        const response = await fetch(url, {
            headers: { 'X-LeadMax-Fragment': 'calendar', Accept: 'text/html' },
            signal: lmCalendarRequest.signal,
        });
        if (!response.ok || response.redirected) throw new Error('Unable to load the calendar.');

        const parsed = new DOMParser().parseFromString(await response.text(), 'text/html');
        const incoming = parsed.querySelector(LM_CALENDAR);
        if (!incoming) throw new Error('Unable to load the calendar.');

        const scrolled = window.scrollY;
        current.replaceWith(incoming);
        window.scrollTo({ top: scrolled });
        if (push) history.pushState({ leadmaxCalendar: true }, '', url);
    } catch (error) {
        if (error?.name === 'AbortError') return;
        // Anything unexpected falls back to a normal navigation rather than a dead control.
        location.assign(url);
    }
};

/** A link that only changes what the calendar shows: the calendar's own route, different query. */
const calendarTarget = (link) => {
    if (!link || !link.href) return null;
    const root = document.querySelector(LM_CALENDAR);
    const own = root?.getAttribute('data-lm-calendar');
    if (!own) return null;
    const url = new URL(link.href, location.origin);
    // Compared against the calendar's own route rather than the address bar: a lead page is a
    // real destination and has to navigate.
    return url.origin === location.origin && url.pathname === new URL(own, location.origin).pathname ? url : null;
};

window.addEventListener('click', (event) => {
    const link = event.target.closest(`${LM_CALENDAR} a[href]`);
    if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    const url = calendarTarget(link);
    if (!url) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    loadCalendar(url);
}, true);

// The agent picker submits itself on change; the Add Event dialog posts and must not be caught.
window.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-lm-cal-filter-form]');
    if (!form) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    const url = new URL(form.getAttribute('action') || location.pathname, location.origin);
    new FormData(form).forEach((value, key) => { if (String(value).trim() !== '') url.searchParams.set(key, value); });
    loadCalendar(url);
}, true);

// Back and forward move between calendar states rather than reloading the shell.
window.addEventListener('popstate', () => {
    if (document.querySelector(LM_CALENDAR)) loadCalendar(location.href, false);
});

/* ---------------------------------------------------------------------------
   Assets: swap in place rather than reload.

   Sorting a column, picking a category, searching or paging changes what the
   table shows, not which page you are on -- a full load rebuilds the shell and
   throws away where you were in a 264-row list.
   --------------------------------------------------------------------------- */
const LM_ASSETS = '[data-lm-assets]';
let lmAssetsRequest = null;

const loadAssets = async (url, push = true) => {
    const current = document.querySelector(LM_ASSETS);
    if (!current) { location.assign(url); return; }

    lmAssetsRequest?.abort();           // a newer click supersedes one still in flight
    lmAssetsRequest = new AbortController();
    current.setAttribute('aria-busy', 'true');

    try {
        const response = await fetch(url, {
            headers: { 'X-LeadMax-Fragment': 'assets', Accept: 'text/html' },
            signal: lmAssetsRequest.signal,
        });
        if (!response.ok || response.redirected) throw new Error('Unable to load the assets.');

        const parsed = new DOMParser().parseFromString(await response.text(), 'text/html');
        const incoming = parsed.querySelector(LM_ASSETS);
        if (!incoming) throw new Error('Unable to load the assets.');

        const scrolled = window.scrollY;
        current.replaceWith(incoming);
        window.scrollTo({ top: scrolled });
        if (push) history.pushState({ leadmaxAssets: true }, '', url);
    } catch (error) {
        if (error?.name === 'AbortError') return;
        // Anything unexpected falls back to a normal navigation rather than a dead control.
        location.assign(url);
    }
};

/** A link that only changes what the table shows: this page's own route, different query. */
const assetsTarget = (link) => {
    if (!link || !link.href) return null;
    const root = document.querySelector(LM_ASSETS);
    const own = root?.getAttribute('data-lm-assets');
    if (!own) return null;
    const url = new URL(link.href, location.origin);
    // Compared against the page's own route rather than the address bar, so the Markups link
    // in the footnote is still a real destination and navigates.
    return url.origin === location.origin && url.pathname === new URL(own, location.origin).pathname ? url : null;
};

window.addEventListener('click', (event) => {
    const link = event.target.closest(`${LM_ASSETS} a[href]`);
    if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    const url = assetsTarget(link);
    if (!url) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    loadAssets(url);
}, true);

window.addEventListener('submit', (event) => {
    const root = event.target.closest(LM_ASSETS);
    if (!root) return;

    // The search box is a GET form: same swap as a link.
    const search = event.target.closest('.lm-tools-search');
    if (search) {
        event.preventDefault();
        event.stopImmediatePropagation();
        const url = new URL(search.getAttribute('action') || root.getAttribute('data-lm-assets'), location.origin);
        new FormData(search).forEach((value, key) => {
            String(value).trim() === '' ? url.searchParams.delete(key) : url.searchParams.set(key, value);
        });
        loadAssets(url);
        return;
    }

    // The on/off switch POSTs and then redirects back, which was a full reload of its own.
    // Post it, then pull the table down again so the row reflects the new state.
    const toggle = event.target.closest('.lm-asset-switch');
    if (!toggle) return;
    event.preventDefault();
    event.stopImmediatePropagation();

    const button = toggle.querySelector('button');
    if (button) button.disabled = true;

    fetch(toggle.getAttribute('action'), {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
        body: new FormData(toggle),
        redirect: 'manual',
    })
        .then(() => loadAssets(location.href, false))
        .catch(() => location.reload());
}, true);

// Back and forward move between sorts rather than reloading the shell.
window.addEventListener('popstate', () => {
    if (document.querySelector(LM_ASSETS)) loadAssets(location.href, false);
});
