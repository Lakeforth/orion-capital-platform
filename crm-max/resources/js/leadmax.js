// Keep the LeadMax preference separate from the central CRM theme.
const themeRoot = document.documentElement;
try {
    themeRoot.dataset.leadmaxTheme = localStorage.getItem('leadmax.theme') === 'light' ? 'light' : 'dark';
} catch {
    themeRoot.dataset.leadmaxTheme = 'dark';
}

// A browser with no stored choice adopts the theme saved on the account.
const adoptAccountTheme = () => {
    const saved = document.querySelector('[data-leadmax-account-theme]')?.dataset.leadmaxAccountTheme;
    let stored = null;
    try { stored = localStorage.getItem('leadmax.theme'); } catch { /* No storage: the account choice still applies. */ }
    if (!stored && (saved === 'light' || saved === 'dark')) themeRoot.dataset.leadmaxTheme = saved;
};
document.addEventListener('DOMContentLoaded', adoptAccountTheme);
document.addEventListener('splade:rendered', adoptAccountTheme);

// Delegate from document so controls continue working after Splade navigation.
function initialiseLeadMaxSidebar(shell) {
    if (!shell || shell.dataset.leadmaxSidebarReady === 'true') return;

    const storageKey = shell.dataset.leadmaxSidebarKey;
    let closed = false;
    try { closed = localStorage.getItem(storageKey) === 'true'; } catch { /* Use the open default when browser storage is unavailable. */ }

    shell.classList.toggle('leadmax-nav-closed', closed);
    shell.querySelectorAll('[aria-controls="leadmax-sidebar"]').forEach((button) => button.setAttribute('aria-expanded', String(!closed)));
    shell.dataset.leadmaxSidebarReady = 'true';
}

function initialiseLeadMaxSidebars(root = document) {
    if (root.matches?.('[data-leadmax-shell]')) initialiseLeadMaxSidebar(root);
    root.querySelectorAll?.('[data-leadmax-shell]').forEach(initialiseLeadMaxSidebar);
}

initialiseLeadMaxSidebars();
new MutationObserver((mutations) => {
    mutations.forEach(({ addedNodes }) => addedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) initialiseLeadMaxSidebars(node);
    }));
}).observe(document.documentElement, { childList: true, subtree: true });

document.addEventListener('click', (event) => {
    const target = event.target.closest('[data-leadmax-toggle], [data-leadmax-panel], [data-leadmax-theme]');
    if (!target) return;
    const shell = target.closest('[data-leadmax-shell]');
    if (!shell) return;
    if (target.hasAttribute('data-leadmax-toggle')) {
        const mobile = window.matchMedia('(max-width: 767px)').matches;
        const open = mobile ? shell.classList.toggle('leadmax-nav-open') : !shell.classList.toggle('leadmax-nav-closed');
        shell.querySelectorAll('[aria-controls="leadmax-sidebar"]').forEach((button) => button.setAttribute('aria-expanded', String(open)));
        if (!mobile) {
            try { localStorage.setItem(shell.dataset.leadmaxSidebarKey, String(!open)); } catch { /* The toggle still works when browser storage is unavailable. */ }
        }
    } else if (target.hasAttribute('data-leadmax-panel')) {
        const collapsed = target.closest('.leadmax-panel').classList.toggle('is-collapsed');
        target.setAttribute('aria-expanded', String(!collapsed));
        target.setAttribute('aria-label', target.getAttribute('aria-label').replace(/^(Collapse|Expand)/, collapsed ? 'Expand' : 'Collapse'));
    } else {
        themeRoot.dataset.leadmaxTheme = themeRoot.dataset.leadmaxTheme === 'light' ? 'dark' : 'light';
        // The new sun/moon spins in (CSS .lm-theme-switching); restart it on quick repeat clicks.
        const icon = target.querySelector('.lm-theme-icon');
        if (icon) {
            icon.classList.remove('lm-theme-switching');
            void icon.getBoundingClientRect();
            icon.classList.add('lm-theme-switching');
            icon.addEventListener('animationend', () => icon.classList.remove('lm-theme-switching'), { once: true });
        }
        try { localStorage.setItem('leadmax.theme', themeRoot.dataset.leadmaxTheme); } catch { /* Theme still works when browser storage is unavailable. */ }
        // Also save it on the account (Account Settings > Preferences); best effort, the switch already happened.
        if (target.dataset.leadmaxPrefs) {
            fetch(target.dataset.leadmaxPrefs, { method: 'POST', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }, body: new URLSearchParams({ theme: themeRoot.dataset.leadmaxTheme }) }).catch(() => {});
            target.dataset.leadmaxAccountTheme = themeRoot.dataset.leadmaxTheme;
        }
    }
});
document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('.leadmax-nav-open').forEach((shell) => {
        shell.classList.remove('leadmax-nav-open');
        const button = shell.querySelector('[aria-controls="leadmax-sidebar"]');
        button?.setAttribute('aria-expanded', 'false');
        button?.focus();
    });
});
document.addEventListener('focusin', (event) => {
    if (event.target.matches('[data-leadmax-date]')) event.target.type = 'date';
});
document.addEventListener('focusout', (event) => {
    if (event.target.matches('[data-leadmax-date]') && !event.target.value) event.target.type = 'text';
});
/* Enter in a custom date field applies the custom range (not the form's first period button). */
document.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter' || !event.target.matches('[data-leadmax-date]')) return;
    const custom = event.target.form && event.target.form.querySelector('button[value="custom"]');
    if (!custom) return;
    event.preventDefault();
    event.target.form.requestSubmit(custom);
});
document.addEventListener('change', (event) => {
    if (!event.target.matches('[data-leadmax-date]')) return;
    const form = event.target.form;
    if (form.elements.from.value && form.elements.to.value) {
        form.requestSubmit(form.querySelector('button[value="custom"]'));
    }
});
(function () {
    function pad(n) { return n < 10 ? '0' + n : n; }
    function tick() {
        const e = document.querySelector('[data-leadmax-clock]');
        if (!e) return;
        const d = new Date();
        e.textContent = pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + '-' + d.getFullYear() + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
    }
    tick();
    setInterval(tick, 1000);
}());

(function () {
    function tickLeadClock() {
        const el = document.querySelector('[data-lm-lead-clock]');
        if (!el) return;
        const tz = el.dataset.tz || 'UTC';
        try {
            const now = new Date();
            const parts = new Intl.DateTimeFormat('en-US', { timeZone: tz, hour: 'numeric', minute: '2-digit', hour12: true }).formatToParts(now);
            const hour = parts.find((part) => part.type === 'hour')?.value || '--';
            const minute = parts.find((part) => part.type === 'minute')?.value || '--';
            const dayPeriod = parts.find((part) => part.type === 'dayPeriod')?.value || '';
            const hEl = el.querySelector('.lm-clock-h');
            const apEl = el.querySelector('.lm-clock-ampm');
            const dEl = el.querySelector('.lm-clock-date');
            if (hEl) hEl.textContent = hour + ':' + minute;
            if (apEl) apEl.textContent = dayPeriod;
            if (dEl) dEl.textContent = new Intl.DateTimeFormat('en-US', { timeZone: tz, weekday: 'short', month: 'short', day: 'numeric' }).format(now);
        } catch (e) {}
    }
    tickLeadClock();
    setInterval(tickLeadClock, 1000);
}());

(function () {
    function syncSelection(page) {
        if (!page) return;
        const selected = [...page.querySelectorAll("[data-lm-select]:checked")];
        const bar = page.querySelector("[data-lm-bulk-actions]");
        if (!bar) return;
        bar.hidden = selected.length === 0 && bar.dataset.lmBulkFiltered !== "1";
        const count = bar.querySelector("[data-lm-selected-count]");
        const noun = page.dataset.lmBulkNoun || "lead";
        if (count) count.textContent = selected.length + (selected.length === 1 ? ` ${noun} selected` : ` ${noun}s selected`);
        const all = page.querySelector("[data-lm-select-all]");
        if (all) { all.checked = selected.length > 0 && selected.length === page.querySelectorAll("[data-lm-select]").length; all.indeterminate = selected.length > 0 && !all.checked; }
    }
    document.addEventListener("change", (event) => {
        const page = event.target.closest("[data-lm-leads], [data-lm-bulk-page]");
        if (!page) return;
        if (event.target.matches("[data-lm-select-all]")) page.querySelectorAll("[data-lm-select]").forEach((box) => { box.checked = event.target.checked; });
        if (event.target.matches("[data-lm-select], [data-lm-select-all]")) syncSelection(page);
    });
    document.addEventListener("click", (event) => {
        const deselect = event.target.closest("[data-lm-bulk-deselect]");
        if (deselect) { const page = deselect.closest("[data-lm-leads], [data-lm-bulk-page]"); page?.querySelectorAll("[data-lm-select], [data-lm-select-all]").forEach((box) => { box.checked = false; box.indeterminate = false; }); syncSelection(page); }
        const exportButton = event.target.closest("[data-lm-bulk-export]");
        if (exportButton) { event.preventDefault(); exportButton.closest("[data-lm-leads], [data-lm-bulk-page]")?.querySelector("[data-lm-export-selected]")?.click(); }
    });
}());

/* Lead detail action menus: delegated so Splade replacements keep working. */
(function(){
    const closeActionMenus = (except) => document.querySelectorAll("[data-lm-action-menu]").forEach((menu) => {
        if (menu !== except) { menu.hidden = true; menu.previousElementSibling?.setAttribute("aria-expanded","false"); }
    });
    document.addEventListener("click", (event) => {
        const trigger = event.target.closest("[data-lm-action-trigger]");
        if (trigger) {
            event.preventDefault();
            const menu = trigger.parentElement.querySelector("[data-lm-action-menu]");
            const opening = menu.hidden;
            closeActionMenus(menu);
            menu.hidden = !opening;
            trigger.setAttribute("aria-expanded", String(opening));
            if (opening) menu.querySelector("[data-lm-action-item]")?.focus();
            return;
        }
        if (!event.target.closest("[data-lm-action-dropdown]")) closeActionMenus();
        if (event.target.closest("[data-lm-action-item]")) closeActionMenus();
    });
    document.addEventListener("keydown", (event) => {
        const menu = event.target.closest("[data-lm-action-menu]");
        if (event.key === "Escape") { closeActionMenus(); menu?.previousElementSibling?.focus(); }
        if (!menu) return;
        const items=[...menu.querySelectorAll("[data-lm-action-item]")], index=items.indexOf(event.target);
        if (event.key === "ArrowDown") { event.preventDefault(); items[(index+1)%items.length]?.focus(); }
        if (event.key === "ArrowUp") { event.preventDefault(); items[(index-1+items.length)%items.length]?.focus(); }
    });
}());

/* Escape closes any open action menu and returns focus to its trigger. */
document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") return;
    const open = document.querySelector("[data-lm-action-menu]:not([hidden])");
    if (!open) return;
    open.hidden = true;
    open.previousElementSibling?.setAttribute("aria-expanded","false");
    open.previousElementSibling?.focus();
});

/* Lead detail actions (copy link, send credentials, block toggles, field copy). Delegated so they survive Splade navigation. */
(function () {
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    const toast = (message, type = 'success') => {
        const node = document.createElement('div');
        node.className = `lm-detail-toast ${type}`;
        node.setAttribute('role', type === 'error' ? 'alert' : 'status');
        node.textContent = message;
        document.body.append(node);
        setTimeout(() => node.remove(), 3500);
    };
    const request = async (url, options = {}) => {
        const response = await fetch(url, { ...options, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf(), ...(options.headers || {}) } });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(Object.values(data.errors ?? {}).flat().join(' ') || data.message || 'Something went wrong. Please try again.');
        return data;
    };
    document.addEventListener('click', async (event) => {
        const copyLink = event.target.closest('[data-lm-copy-link]');
        const sendCredentials = event.target.closest('[data-lm-send-credentials]');
        const post = event.target.closest('[data-lm-post]');
        const fieldCopy = event.target.closest('.lm-detail-control .lm-control-copy');
        const button = copyLink || sendCredentials || post;
        if (button) {
            event.preventDefault();
            if (button.dataset.lmConfirm && !window.confirm(button.dataset.lmConfirm)) return;
            button.disabled = true;
            try {
                if (copyLink) {
                    const data = await request(copyLink.dataset.lmCopyLink);
                    await navigator.clipboard.writeText(data.url);
                    toast('Auto-login link copied to clipboard (valid for 24 hours)');
                } else if (sendCredentials) {
                    await request(sendCredentials.dataset.lmSendCredentials, { method: 'POST' });
                    toast('Credentials email sent');
                } else {
                    const body = new FormData();
                    body.append('action', post.dataset.lmPostAction);
                    await request(post.dataset.lmPost, { method: 'POST', body });
                    location.reload();
                }
            } catch (error) {
                toast(error.message, 'error');
            } finally {
                button.disabled = false;
            }
            return;
        }
        if (fieldCopy) {
            // data-lm-copy-value carries the plain value when the display is formatted, so copying
            // a phone number gives +972505043163 rather than the dashed +972-505043163.
            const shown = fieldCopy.closest('.lm-detail-control').querySelector(':scope > span');
            const value = shown?.dataset.lmCopyValue || shown?.textContent.trim();
            if (!value || value === 'Not selected' || value === 'Not provided') return;
            try {
                await navigator.clipboard.writeText(value);
                toast(`Copied: ${value.slice(0, 40)}`);
            } catch {
                toast('Copy failed', 'error');
            }
        }
    });

    /* Inline field editing on the lead details grid: click a value (or its chevron / calendar) to edit, Enter or ✓ saves, Escape or ✕ cancels. */
    const closeEditors = (except) => document.querySelectorAll('[data-lm-field-form]:not([hidden])').forEach((form) => {
        if (form === except) return;
        form.hidden = true;
        form.closest('[data-lm-field]')?.classList.remove('is-editing');
    });
    const openEditor = (field, add = false) => {
        const form = field.querySelector(add ? '[data-lm-field-add-form]' : '[data-lm-field-form]:not([data-lm-field-add-form])');
        if (!form) return;
        closeEditors(form);
        form.hidden = false;
        field.classList.add('is-editing');
        const input = form.querySelector('[name=value]');
        // Splade renders through Vue, which sets value/selected as properties, so form.reset() would blank the field; restore the saved value instead.
        if (input) input.value = add ? '' : (input.dataset.lmValue ?? '');
        // Re-sync a country flag preview, so reopening after a cancelled pick does not leave the
        // flag showing the country that was discarded.
        if (input?.matches('[data-lm-country-select]')) input.dispatchEvent(new Event('change', { bubbles: true }));
        input?.focus();
        if (input?.showPicker && ['date', 'datetime-local'].includes(input.type)) { try { input.showPicker(); } catch { /* needs a user gesture */ } }
    };
    document.addEventListener('click', (event) => {
        const edit = event.target.closest('[data-lm-field-edit]');
        const add = event.target.closest('[data-lm-field-add]');
        const cancel = event.target.closest('[data-lm-field-cancel]');
        if (edit || add) {
            event.preventDefault();
            openEditor((edit || add).closest('[data-lm-field]'), !!add);
            return;
        }
        if (cancel) {
            closeEditors();
            return;
        }
        if (event.target.closest('[data-lm-field-focus]')) {
            const first = document.querySelector('[data-lm-detail-fields] [data-lm-field].is-editable');
            const fields = document.querySelector('[data-lm-detail-fields]');
            if (fields?.hidden) document.querySelector('[data-lm-detail-collapse]')?.click();
            first?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (first) openEditor(first);
            return;
        }
        if (event.target.closest('[data-lm-print]')) { window.print(); return; }
        if (!event.target.closest('[data-lm-field-form]')) closeEditors();
    });
    document.addEventListener('keydown', (event) => {
        if (event.target.matches?.('[data-lm-field-display][data-lm-field-edit]') && (event.key === 'Enter' || event.key === ' ')) {
            event.preventDefault();
            openEditor(event.target.closest('[data-lm-field]'));
        } else if (event.key === 'Escape' && event.target.closest?.('[data-lm-field-form]')) {
            closeEditors();
            event.target.closest('[data-lm-field]')?.querySelector('[data-lm-field-display]')?.focus();
        }
    });
    // Selects used to save the moment an option was picked, while the editor still showed its Save
    // and Cancel buttons -- so an accidental pick committed, and Cancel had nothing left to discard.
    // They now wait for Save like every other field.
    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('[data-lm-field-form]');
        if (!form) return;
        event.preventDefault();
        const field = form.closest('[data-lm-field]');
        const body = new FormData(form);
        form.classList.add('is-saving');
        form.querySelectorAll('button').forEach((button) => { button.disabled = true; });
        try {
            const data = await request(form.action, { method: 'POST', body });
            if (data.reload) { sessionStorage.setItem('lm-field-toast', data.message || 'Saved'); location.reload(); return; }
            const display = field.querySelector('[data-lm-field-display]');
            display.textContent = data.display ?? display.dataset.lmPlaceholder;
            display.classList.toggle('lm-field-empty', data.display == null);
            const input = form.querySelector('[name=value]');
            if (input) input.dataset.lmValue = data.value ?? '';
            closeEditors();
            field.classList.add('is-saved');
            setTimeout(() => field.classList.remove('is-saved'), 1200);
            toast(data.message || 'Saved');
        } catch (error) {
            toast(error.message, 'error');
            form.querySelector('[name=value]')?.focus();
        } finally {
            form.classList.remove('is-saving');
            form.querySelectorAll('button').forEach((button) => { button.disabled = false; });
        }
    });
    try { const pending = sessionStorage.getItem('lm-field-toast'); if (pending) { sessionStorage.removeItem('lm-field-toast'); toast(pending); } } catch { /* storage unavailable */ }

    /* Extra phones/emails (remove), comment delete, and comment emoji / @mention / attachment helpers. */
    document.addEventListener('click', async (event) => {
        const remove = event.target.closest('[data-lm-field-remove]');
        const del = event.target.closest('[data-lm-delete]');
        const insert = event.target.closest('[data-lm-insert]');
        const copied = event.target.closest('.lm-detail-page [data-lm-copy-text]');
        const clearFile = event.target.closest('[data-lm-comment-file-clear]');
        if (remove) {
            if (!window.confirm(`Remove ${remove.closest('li')?.querySelector('a')?.textContent || 'this entry'}?`)) return;
            const body = new FormData();
            body.append('field', remove.dataset.lmFieldName);
            body.append('remove', remove.dataset.lmFieldRemove);
            try { await request(remove.closest('[data-lm-field]').querySelector('[data-lm-field-form]').action, { method: 'POST', body }); sessionStorage.setItem('lm-field-toast', 'Removed'); location.reload(); } catch (error) { toast(error.message, 'error'); }
        } else if (del) {
            if (del.dataset.lmConfirm && !window.confirm(del.dataset.lmConfirm)) return;
            try { await request(del.dataset.lmDelete, { method: 'DELETE' }); sessionStorage.setItem('lm-field-toast', 'Comment deleted'); location.reload(); } catch (error) { toast(error.message, 'error'); }
        } else if (insert) {
            const textarea = insert.closest('form')?.querySelector('textarea[name=note]');
            if (!textarea) return;
            const start = textarea.selectionStart ?? textarea.value.length;
            const end = textarea.selectionEnd ?? start;
            textarea.setRangeText(insert.dataset.lmInsert, start, end, 'end');
            textarea.focus();
        } else if (copied) {
            toast('Copied to clipboard');
        } else if (clearFile) {
            const form = clearFile.closest('form');
            const input = form.querySelector('[data-lm-comment-file]');
            input.value = '';
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
    document.addEventListener('change', (event) => {
        if (!event.target.matches('[data-lm-comment-file]')) return;
        const form = event.target.closest('form');
        const file = event.target.files?.[0];
        const name = form.querySelector('[data-lm-comment-file-name]');
        if (file && file.size > 10 * 1024 * 1024) { toast('That file is larger than 10 MB.', 'error'); event.target.value = ''; }
        const chosen = event.target.files?.[0];
        name.textContent = chosen ? `📎 ${chosen.name}` : '';
        name.hidden = !chosen;
        form.querySelector('[data-lm-comment-file-clear]').hidden = !chosen;
    });

    /* The details chevron hides the status row and field grid for this view only; the tabs chevron
       hides the tab content. A lead always opens with its details showing (user, 11 Oct 2026): a
       collapse remembered per browser hid them on every refresh but not after a tab click. */
    try { localStorage.removeItem('lm-detail-collapsed'); } catch { /* storage unavailable */ } // what an older build remembered
    const collapsed = () => document.querySelector('[data-lm-detail-collapse]')?.getAttribute('aria-expanded') === 'false';
    const applyCollapse = (hide) => {
        const button = document.querySelector('[data-lm-detail-collapse]');
        if (!button) return;
        document.querySelectorAll('[data-lm-detail-fields], .lm-detail-offline').forEach((node) => { node.hidden = hide; });
        button.setAttribute('aria-expanded', String(!hide));
        button.setAttribute('aria-label', hide ? 'Expand details' : 'Collapse details');
        button.classList.toggle('is-collapsed', hide);
    };
    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-lm-detail-collapse]')) applyCollapse(!collapsed());
        const tabs = event.target.closest('[data-lm-tabs-collapse]');
        if (tabs) {
            const body = document.querySelector('[data-lm-tab-body]');
            if (!body) return;
            body.hidden = !body.hidden;
            tabs.setAttribute('aria-expanded', String(!body.hidden));
            tabs.classList.toggle('is-collapsed', body.hidden);
        }
    });
}());

/* Shared filter panels: a [data-lm-filter-toggle] button shows/hides the panel named by aria-controls. */
document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-lm-filter-toggle]');
    if (!toggle) return;
    const panel = document.getElementById(toggle.getAttribute('aria-controls'));
    if (!panel) return;
    panel.hidden = !panel.hidden;
    toggle.setAttribute('aria-expanded', String(!panel.hidden));
    if (!panel.hidden) panel.querySelector('select, input:not([type=hidden])')?.focus();
});

/* Navbar language flag: follow the same language the translator applies (saved choice first, then the googtrans cookie). */
(function () {
    const currentLanguage = () => {
        let saved = '';
        try { saved = localStorage.getItem('admin_translate_language') || ''; } catch { /* storage unavailable */ }
        if (saved) return saved;
        const cookie = document.cookie.match(/(?:^|; )googtrans=([^;]*)/);
        const parts = cookie ? decodeURIComponent(cookie[1]).split('/') : [];
        return parts[2] || 'en';
    };
    const syncFlag = (language = currentLanguage()) => {
        const dropdown = document.getElementById('qsLangDropdown');
        if (!dropdown) return;
        const item = dropdown.querySelector(`[data-leadmax-lang="${language}"]`) || dropdown.querySelector('[data-leadmax-lang="en"]');
        const flag = document.getElementById('qsLangFlagImg');
        if (item && flag) {
            flag.src = `https://flagcdn.com/28x21/${item.dataset.leadmaxFlag}.png`;
            flag.alt = item.dataset.leadmaxLabel;
        }
        dropdown.querySelectorAll('[data-leadmax-lang]').forEach((button) => button.classList.toggle('is-active', button === item));
    };
    document.addEventListener('click', (event) => {
        const item = event.target.closest('#qsLangDropdown [data-leadmax-lang]');
        const dropdown = document.getElementById('qsLangDropdown');
        if (item) {
            const select = document.getElementById('admin-language-switcher');
            syncFlag(item.dataset.leadmaxLang);
            dropdown.removeAttribute('open');
            // Save it on the account too. A beacon, because applying the language reloads the page.
            if (dropdown?.dataset.leadmaxPrefs && navigator.sendBeacon) {
                const body = new FormData();
                body.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                body.append('locale', item.dataset.leadmaxLang);
                navigator.sendBeacon(dropdown.dataset.leadmaxPrefs, body);
                dropdown.dataset.accountLocale = item.dataset.leadmaxLang;
            }
            if (select) {
                select.value = item.dataset.leadmaxLang;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
        } else if (dropdown?.open && !dropdown.contains(event.target)) {
            dropdown.removeAttribute('open');
        }
    });
    document.addEventListener('input', (event) => {
        if (!event.target.matches('#qsLangSearch')) return;
        const query = event.target.value.toLowerCase().trim();
        document.querySelectorAll('#qsLangGrid .leadmax-lang-item').forEach((button) => { button.style.display = query && !button.dataset.search.includes(query) ? 'none' : ''; });
    });
    // <details> toggle does not bubble, so listen in the capture phase.
    document.addEventListener('toggle', (event) => {
        if (event.target.id !== 'qsLangDropdown' || !event.target.open) return;
        const search = document.getElementById('qsLangSearch');
        if (!search) return;
        search.value = '';
        search.dispatchEvent(new Event('input', { bubbles: true }));
        setTimeout(() => search.focus(), 50);
    }, true);
    document.addEventListener('DOMContentLoaded', () => syncFlag());
    document.addEventListener('splade:rendered', () => syncFlag());
    syncFlag();
}());

/* Invoice dialog: add another line item row with the next index. */
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-lm-add-invoice-line]');
    if (!button) return;
    const lines = button.closest('form').querySelector('.lm-invoice-lines');
    const rows = lines.querySelectorAll('[data-lm-invoice-line]');
    if (rows.length >= 20) return;
    const row = rows[rows.length - 1].cloneNode(true);
    row.querySelectorAll('input').forEach((input) => {
        input.name = input.name.replace(/items\[\d+\]/, `items[${rows.length}]`);
        input.value = input.name.endsWith('[quantity]') ? '1' : '';
    });
    lines.append(row);
    row.querySelector('input')?.focus();
});

/* Lead detail menu items that navigate are buttons (so every item shares the menu's button styling). */
document.addEventListener('click', (event) => {
    const item = event.target.closest('[data-lm-href]');
    if (!item) return;
    if (item.dataset.lmTarget === '_blank') window.open(item.dataset.lmHref, '_blank', 'noopener');
    else window.location.assign(item.dataset.lmHref);
});

/* Notification dropdown and page: tabs, dismiss and close (delegated; the old inline script died after Splade navigation). */
(function () {
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    const syncGroups = (scope) => {
        scope.querySelectorAll('.lm-notif-group').forEach((group) => { group.hidden = !group.querySelector('[data-lm-notif-card]:not([hidden])'); });
        const none = scope.querySelector('[data-lm-notif-none]');
        if (none) none.hidden = !!scope.querySelector('[data-lm-notif-card]:not([hidden])') || !scope.querySelector('[data-lm-notif-card]');
    };
    document.addEventListener('click', async (event) => {
        const tab = event.target.closest('[data-lm-notif-tab]');
        if (tab) {
            const panel = tab.closest('[data-lm-notif-panel]');
            panel.querySelectorAll('[data-lm-notif-tab]').forEach((button) => { button.classList.toggle('active', button === tab); button.setAttribute('aria-selected', String(button === tab)); });
            panel.querySelectorAll('[data-lm-notif-card]').forEach((card) => { card.hidden = tab.dataset.lmNotifTab === 'unread' && card.dataset.notifRead === 'true'; });
            syncGroups(panel);
            return;
        }
        if (event.target.closest('[data-lm-notif-close]')) {
            event.target.closest('details')?.removeAttribute('open');
            return;
        }
        const dismiss = event.target.closest('[data-lm-notif-dismiss]');
        if (dismiss) {
            event.preventDefault();
            const card = dismiss.closest('[data-lm-notif-card]');
            card.hidden = true;
            syncGroups(card.closest('[data-lm-notif-panel], [data-lm-notif-page]'));
            try {
                const response = await fetch(dismiss.dataset.lmNotifDismiss, { method: 'POST', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() } });
                if (!response.ok) throw new Error();
                card.remove();
            } catch {
                card.hidden = false;
            }
        }
    });
}());

/* Chat: Enter sends (Shift+Enter for a new line), keep the thread scrolled to the latest message, and poll for new messages. */
(function () {
    const scrollDown = () => { const window_ = document.querySelector('[data-lm-chat-window]'); if (window_) window_.scrollTop = window_.scrollHeight; };
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || event.shiftKey || !event.target.closest('[data-lm-chat-compose] textarea')) return;
        event.preventDefault();
        const form = event.target.form;
        if (event.target.value.trim()) form.requestSubmit();
    });
    document.addEventListener('submit', (event) => {
        const form = event.target.closest('[data-lm-chat-compose]');
        if (form) form.querySelector('.lm-chat-send')?.setAttribute('disabled', '');
    });
    document.addEventListener('DOMContentLoaded', scrollDown);
    document.addEventListener('splade:rendered', scrollDown);
    scrollDown();
    setInterval(async () => {
        const window_ = document.querySelector('[data-lm-chat-window]');
        if (!window_ || document.hidden) return;
        try {
            const response = await fetch(window_.dataset.lmChatPoll, { headers: { 'X-LeadMax-Fragment': 'chat', Accept: 'text/html' } });
            if (!response.ok) return;
            const next = new DOMParser().parseFromString(await response.text(), 'text/html').querySelector('[data-lm-chat-stream]');
            const current = window_.querySelector('[data-lm-chat-stream]');
            if (!next || !current || next.dataset.lmChatLast === current.dataset.lmChatLast) return;
            const atBottom = window_.scrollHeight - window_.scrollTop - window_.clientHeight < 80;
            current.replaceWith(next);
            if (atBottom) scrollDown();
        } catch { /* try again on the next tick */ }
    }, 8000);
}());

/* Lead detail tab sections: the chevron collapses its section body. */
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-lm-tab-collapse]');
    if (!button) return;
    const section = button.closest('.lm-kv-card, .lm-tab-table, .lm-tab-signals');
    const body = section?.querySelector(':scope > .lm-tab-collapsible') ?? section?.querySelector('.lm-tab-collapsible');
    if (!body) return;
    body.hidden = !body.hidden;
    button.setAttribute('aria-expanded', String(!body.hidden));
});

/* Copy-to-clipboard buttons (API key modal) and confirm-before-submit buttons. */
document.addEventListener('click', async (event) => {
    const copy = event.target.closest('[data-lm-copy-text]');
    if (copy) {
        try { await navigator.clipboard.writeText(copy.dataset.lmCopyText); copy.classList.add('is-copied'); setTimeout(() => copy.classList.remove('is-copied'), 1200); } catch { /* clipboard blocked */ }
        return;
    }
    const confirmSubmit = event.target.closest('[data-lm-confirm-submit]');
    if (confirmSubmit && !window.confirm(confirmSubmit.dataset.lmConfirmSubmit)) event.preventDefault();

    // Edit Manager: one dialog serves every row, so whatever was clicked fills it in and
    // points the form at that manager. Delegated, because the table is re-rendered.
    const edit = event.target.closest('[data-lm-edit-manager]');
    if (edit) {
        const modal = document.getElementById('lm-edit-manager-modal');
        const form = modal && modal.querySelector('[data-lm-edit-manager-form]');
        if (!form) return;

        form.setAttribute('action', edit.dataset.lmEditManager);
        const set = (name, value) => {
            const field = form.elements[name];
            if (field) field.value = value ?? '';
        };
        set('first_name', edit.dataset.firstName);
        set('last_name', edit.dataset.lastName);
        set('email', edit.dataset.email);
        set('phone', edit.dataset.phone);
        set('team', edit.dataset.team);
        set('status', edit.dataset.status);
        // Always blank: a filled-looking password box would imply it is about to be changed.
        set('password', '');
        set('password_confirmation', '');

        modal.style.display = 'flex';
        form.elements.first_name?.focus();
    }
});

/* Upload Leads: drag and drop, chosen-file label and auto-submit, placeholder selects, polling while an import runs. */
const lmUploadPicked = (input) => {
    const file = input.files && input.files[0];
    if (!file) return;
    const form = input.closest('[data-lm-upload-form]');
    const label = form && form.querySelector('[data-lm-upload-label]');
    if (label) label.textContent = `Uploading ${file.name}…`;
    if (form) form.submit();
};
document.addEventListener('change', (event) => {
    if (event.target.matches('[data-lm-upload-input]')) lmUploadPicked(event.target);
    if (event.target.matches('.lm-up-select select')) event.target.classList.toggle('is-placeholder', event.target.value === '');
    if (event.target.matches('select[data-lm-autosubmit], input[data-lm-autosubmit]')) event.target.form.submit();
    if (event.target.matches('[data-lm-logo-input]')) lmLogoPicked(event.target);
});
['dragenter', 'dragover'].forEach((type) => document.addEventListener(type, (event) => {
    const zone = event.target.closest && event.target.closest('[data-lm-dropzone]');
    if (!zone) return;
    event.preventDefault();
    zone.classList.add('is-dragging');
}));
document.addEventListener('dragleave', (event) => {
    const zone = event.target.closest && event.target.closest('[data-lm-dropzone]');
    if (zone && !zone.contains(event.relatedTarget)) zone.classList.remove('is-dragging');
});
document.addEventListener('drop', (event) => {
    const zone = event.target.closest && event.target.closest('[data-lm-dropzone]');
    if (!zone) return;
    event.preventDefault();
    zone.classList.remove('is-dragging');
    const input = zone.querySelector('[data-lm-upload-input]');
    if (!input || !event.dataTransfer.files.length) return;
    input.files = event.dataTransfer.files;
    lmUploadPicked(input);
});
setInterval(() => {
    const page = document.querySelector('[data-lm-refresh]');
    if (page && !document.hidden && !document.querySelector('.lm-up-more[open]')) window.location.reload();
}, 5000);

/* Lead Splitter rule dialog: custom countries list and per-method recipient hints. */
const lmRuleFormSync = (form) => {
    const region = form.querySelector('[data-lm-rule-region]');
    const countries = form.querySelector('[data-lm-rule-countries]');
    if (region && countries) countries.hidden = region.value !== 'custom';
    const method = form.querySelector('[data-lm-rule-method]');
    if (!method) return;
    form.querySelectorAll('[data-lm-rule-hint] [data-method]').forEach((hint) => { hint.hidden = hint.dataset.method !== method.value; });
    const agents = form.querySelector('[data-lm-rule-agents]');
    if (agents) agents.dataset.method = method.value;
};
document.addEventListener('change', (event) => {
    const form = event.target.closest('[data-lm-rule-form]');
    if (form && event.target.matches('[data-lm-rule-region], [data-lm-rule-method]')) lmRuleFormSync(form);
});
const lmRuleFormInit = () => document.querySelectorAll('[data-lm-rule-form]').forEach(lmRuleFormSync);
new MutationObserver(lmRuleFormInit).observe(document.documentElement, { childList: true, subtree: true });
lmRuleFormInit();

/* Report map zoom buttons (Conversion by GEO, Campaigns & GEO). */
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-lm-map-zoom]');
    if (!button) return;
    const svg = button.closest('section')?.querySelector('[data-lm-map-svg]');
    if (!svg) return;
    const zoom = Math.min(2.5, Math.max(1, (parseFloat(svg.style.getPropertyValue('--zoom')) || 1) + (button.dataset.lmMapZoom === 'in' ? 0.25 : -0.25)));
    svg.style.setProperty('--zoom', String(zoom));
});

/* Report From/To inputs: show a date picker on focus (placeholder text otherwise) and apply once a date is picked. */
document.addEventListener('focusin', (event) => {
    if (event.target.matches('[data-lm-date-submit]')) event.target.type = 'date';
});
document.addEventListener('change', (event) => {
    if (event.target.matches('[data-lm-date-submit]') && event.target.value) event.target.form.submit();
});

/* Settings logo: preview the picked file before saving. */
function lmLogoPicked(input) {
    const file = input.files && input.files[0];
    // Each logo slot (default and light mode) previews into its own box.
    const box = input.closest('.lm-set-logo')?.querySelector('[data-lm-logo-preview]');
    if (!file || !box) return;
    const img = document.createElement('img');
    img.src = URL.createObjectURL(file);
    img.alt = 'Logo preview';
    box.replaceChildren(img);
}

/* Top bar: refresh reloads the current page; opening search focuses its field. */
document.addEventListener('click', (event) => {
});
document.addEventListener('toggle', (event) => {
    if (event.target.matches && event.target.matches('.leadmax-search-menu') && event.target.open) event.target.querySelector('input[name=q]')?.focus();
}, true);

/* Country selects show the chosen country's flag beside them. Each option carries its own
   data-flag, so the country list never has to be repeated here. */
document.addEventListener('change', (event) => {
    const select = event.target.closest && event.target.closest('[data-lm-country-select]');
    if (!select) return;
    const flag = select.parentElement?.querySelector('img[data-lm-country-flag]');
    if (!flag) return;
    const src = select.selectedOptions[0]?.dataset.flag || '';
    if (src) flag.src = src;
    flag.hidden = !src;
});

/* Faster navigation: prefetch LeadMax pages on hover and show a loading bar as soon as a page or form is requested. */
(function () {
    if (!document.querySelector('.leadmax-shell, .leadmax-topbar')) return;
    const skip = '(export|download|logo|history|template|copy-link|attachment|invoices|logout|chat)'; // chat views mark messages read
    if (window.HTMLScriptElement && HTMLScriptElement.supports && HTMLScriptElement.supports('speculationrules')) {
        const rules = document.createElement('script');
        rules.type = 'speculationrules';
        rules.textContent = JSON.stringify({ prefetch: [{ where: { and: [{ href_matches: '/panel/leadmax/*' }, ...skip.slice(1, -1).split('|').map((word) => ({ not: { href_matches: '/panel/leadmax/*' + word + '*' } })), { not: { selector_matches: '[download], [target=_blank], [data-lm-no-prefetch]' } }] }, eagerness: 'moderate' }] });
        document.head.appendChild(rules);
    } else {
        const done = new Set();
        const pattern = new RegExp(skip);
        document.addEventListener('mouseover', (event) => {
            const link = event.target.closest && event.target.closest('a[href^="/panel/leadmax"], a[href^="' + location.origin + '/panel/leadmax"]');
            if (!link || link.hasAttribute('download') || link.target === '_blank' || pattern.test(link.href) || done.has(link.href)) return;
            done.add(link.href);
            const hint = document.createElement('link');
            hint.rel = 'prefetch';
            hint.href = link.href;
            document.head.appendChild(hint);
        });
    }

    const bar = document.createElement('div');
    bar.className = 'lm-loading-bar';
    bar.setAttribute('aria-hidden', 'true');
    document.body.appendChild(bar);
    const start = () => document.documentElement.classList.add('lm-is-loading');
    const stop = () => document.documentElement.classList.remove('lm-is-loading');
    document.addEventListener('click', (event) => {
        const link = event.target.closest && event.target.closest('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        if (link.target === '_blank' || link.hasAttribute('download') || link.origin !== location.origin || link.getAttribute('href').startsWith('#') || /export|download|history|template/.test(link.pathname)) return;
        if (link.pathname === location.pathname && link.search === location.search && link.hash) return;
        start();
    });
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (event.defaultPrevented || form.target === '_blank' || /export|download/.test(form.action)) return;
        // A save makes every page prefetched so far a snapshot from before it: drop the rules
        // (and with them their prefetches) so the page after the save is fetched fresh. The
        // server's Clear-Site-Data "prefetchCache" on the save does the same for newer Chrome.
        if ((form.getAttribute('method') || 'get').toLowerCase() !== 'get') document.querySelectorAll('script[type="speculationrules"]').forEach((rules) => rules.remove());
        start();
    });
    window.addEventListener('pageshow', stop);
})();

/* ----------------------------------------------------------------- KYC review
 *
 * The Update KYC Status panel's rejection reason.
 *
 * Document-level delegation, like everything else here: the lead details tabs
 * are swapped in as fragments, so a handler bound to a row at load time is
 * bound to a row that no longer exists by the time anyone clicks it.
 */
(() => {
    // Approve / Reject and the Update KYC Status panel are data-lm-submit forms
    // (leadmax-leads.js), which refresh the tab from the server. Choosing Rejected
    // in the panel reveals the reason the client will be shown, which is required.
    document.addEventListener('change', (event) => {
        const select = event.target.closest('[data-lm-kyc-target]');
        if (!select) return;
        const reason = select.form?.querySelector('[data-lm-kyc-reason]');
        if (!reason) return;
        const rejecting = select.value === 'rejected';
        reason.hidden = !rejecting;
        reason.querySelector('textarea').required = rejecting;
    });
    // Staff add documents through the Add KYC wizard (leadmax-leads.js), which replaced
    // the single "+ Upload Document" picker.
})();

/*
 * Live bell. Polls the unread totals every 30s while the tab is visible, keeps the badge and
 * its label in step, and announces newly arrived notifications once: a fast 5s swing (it plays
 * under reduced motion too, at the user's request), one toast for the batch and a polite live-region line.
 * The first poll only learns what is already there, so a backlog is never replayed as new.
 * Sent as XHR so an expired session does not keep the poll URL as the post-login destination.
 */
(function () {
    const known = { ids: null };
    let timer = null;
    let toastTimer = null;
    const label = (items) => {
        if (items.length === 1) return items[0].title;
        return items.every((item) => item.type === 'lead_created') ? `${items.length} new leads received` : `${items.length} new notifications`;
    };
    const announce = (bell, items) => {
        const icon = bell.querySelector('summary svg, summary img');
        if (icon) {
            icon.classList.remove('lm-bell-ring');
            void icon.getBoundingClientRect();
            icon.classList.add('lm-bell-ring');
            const settle = (event) => {
                if (event.target !== icon) return; // the clapper's own swing bubbles up too
                icon.classList.remove('lm-bell-ring');
                icon.removeEventListener('animationend', settle);
            };
            icon.addEventListener('animationend', settle);
        }
        const text = label(items);
        const live = document.querySelector('[data-lm-notif-live]');
        if (live) live.textContent = text;
        const toast = document.querySelector('[data-lm-notif-toast]');
        if (toast) {
            toast.querySelector('[data-lm-notif-toast-text]').textContent = text;
            // One colour for the batch: red if anything failed, green if all of it completed, else blue.
            const tones = items.map((item) => item.tone || 'new');
            toast.dataset.tone = tones.includes('error') ? 'error' : tones.every((tone) => tone === 'completed') ? 'completed' : 'new';
            toast.hidden = false;
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => { toast.hidden = true; }, 6000);
        }
        const banner = document.querySelector('[data-lm-incoming-banner]');
        const leads = items.filter((item) => item.type === 'lead_created').length;
        if (banner && leads) {
            const count = Number(banner.dataset.lmIncomingNew || 0) + leads;
            banner.dataset.lmIncomingNew = String(count);
            banner.querySelector('[data-lm-incoming-count]').textContent = `${count} new lead${count === 1 ? '' : 's'}`;
            banner.hidden = false;
        }
    };
    const poll = async () => {
        const bell = document.querySelector('[data-lm-notif-summary]');
        if (!bell || document.hidden) return;
        let data;
        try {
            const response = await fetch(bell.dataset.lmNotifSummary, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (response.status === 401 || response.status === 419) { clearInterval(timer); return; }
            if (!response.ok) return;
            data = await response.json();
        } catch { return; }
        const badge = bell.querySelector('[data-lm-notif-badge]');
        if (badge) {
            badge.textContent = data.notifications > 99 ? '99+' : String(data.notifications);
            badge.hidden = data.notifications < 1;
        }
        bell.querySelector('summary')?.setAttribute('aria-label', `Notifications, ${data.notifications} unread`);
        const items = Array.isArray(data.latest) ? data.latest : [];
        // Right after sign-in: one summary of what is waiting, not a toast per item.
        if (bell.hasAttribute('data-lm-login-summary')) {
            bell.removeAttribute('data-lm-login-summary');
            if (data.notifications > 0) {
                announce(bell, [{ title: `You have ${data.notifications} unread notification${data.notifications === 1 ? '' : 's'}`, type: 'summary' }]);
            }
        }
        if (known.ids === null) {
            known.ids = new Set(items.map((item) => item.id));
            return;
        }
        const fresh = items.filter((item) => !known.ids.has(item.id));
        items.forEach((item) => known.ids.add(item.id));
        if (fresh.length) announce(bell, fresh);
    };
    poll();
    timer = setInterval(poll, 30000);
    // Splade swaps pages without reloading this script: poll at once when a page arrives that
    // has not been polled yet (first CRM page after sign-in) instead of waiting for the timer.
    document.addEventListener('splade:rendered', () => {
        if (known.ids === null || document.querySelector('[data-lm-login-summary]')) poll();
    });
}());

/*
 * Every CRM table sorts the same way (user request, 10 Oct 2026). Each header gets the up/down
 * arrows the Leads list uses; a click sorts that table's rows in the browser -- numbers, money and
 * dates by value, text A-Z, a second click reverses. Columns that already sort on the server (the
 * Leads list, Assets, the lead Activity Log) keep their links; in such a table only the columns the
 * server cannot sort (Assets' per-customer Bid/Ask) sort the rows on the page. [data-lm-no-sort]
 * opts a table out. Every header shows the arrows, an empty table's too; rows that do not line up with the
 * header (an empty state, a group heading) stay put after the sorted ones. With pagination, the
 * rows on the current page are the ones sorted.
 */
(() => {
    const ARROWS = '<svg class="lm-sort-arrows" viewBox="0 0 11 14" width="14" height="17.8" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path class="up" d="M3 9.5V2.2M1.2 4L3 2.2 4.8 4"/><path class="down" d="M8 4.5v7.3M9.8 10 8 11.8 6.2 10"/></svg>';
    const UNSORTABLE = /^(actions?|details?|select|)$/i;
    const keyOf = (text) => {
        const value = text.trim();
        const number = value.replace(/[\s,$€£%+]|USD|EUR|GBP/g, '').replace(/^\((.*)\)$/, '-$1');
        if (/^-?\d+(\.\d+)?$/.test(number)) return [0, parseFloat(number)];
        if (/\d{4}-\d{2}-\d{2}|\d{1,2}\/\d{1,2}\/\d{2,4}/.test(value) && !Number.isNaN(Date.parse(value))) return [0, Date.parse(value)];
        return [1, value.toLowerCase()];
    };
    const compare = (a, b) => (a[0] !== b[0] ? a[0] - b[0] : a[0] === 0 ? a[1] - b[1] : a[1].localeCompare(b[1], undefined, { numeric: true }));
    const enhance = (table) => {
        if (table.dataset.lmSortReady || table.matches('.lm-updated-leads-table, [data-lm-no-sort]')) return;
        const head = table.tHead?.rows[0];
        if (!head || !table.tBodies[0]) return;
        table.dataset.lmSortReady = '1';
        [...head.cells].forEach((th) => {
            if (th.colSpan > 1 || th.classList.contains('lm-sortable') || UNSORTABLE.test(th.textContent.trim()) || th.querySelector('input, button, select, a')) return;
            addArrows(th);
        });
    };
    const addArrows = (cell) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'lm-th-sort';
        button.append(...cell.childNodes);
        button.insertAdjacentHTML('beforeend', ARROWS);
        cell.classList.add('lm-sortable', 'lm-client-sort');
        cell.setAttribute('aria-sort', 'none');
        cell.append(button);
    };
    // The lead page's tables are CSS grids (.lm-tab-table): a heading row of cells, then .lm-tab-row rows.
    const enhanceGrid = (grid) => {
        const head = grid.querySelector('.lm-tab-thead');
        if (!head || grid.dataset.lmSortReady || grid.matches('[data-lm-no-sort]')) return;
        grid.dataset.lmSortReady = '1';
        [...head.children].forEach((cell) => {
            if (cell.classList.contains('lm-sortable') || UNSORTABLE.test(cell.textContent.trim()) || cell.querySelector('input, button, select, a')) return;
            addArrows(cell);
        });
    };
    const scan = (root) => {
        if (root.matches?.('.leadmax-content table')) enhance(root);
        root.querySelectorAll?.('.leadmax-content table').forEach(enhance);
        if (root.matches?.('.lm-tab-table')) enhanceGrid(root);
        root.querySelectorAll?.('.lm-tab-table').forEach(enhanceGrid);
    };
    // Grid rows sort within each run between other elements (a KYC attempt heading, the pager), so
    // groups stay together and the pager stays last.
    const sortGrid = (cell) => {
        const head = cell.parentElement;
        const column = [...head.children].indexOf(cell);
        const direction = cell.classList.contains('is-asc') ? 'desc' : 'asc';
        head.querySelectorAll('.lm-client-sort').forEach((other) => {
            other.classList.remove('is-sorted', 'is-asc', 'is-desc');
            other.setAttribute('aria-sort', 'none');
        });
        cell.classList.add('is-sorted', `is-${direction}`);
        cell.setAttribute('aria-sort', direction === 'asc' ? 'ascending' : 'descending');
        const box = head.parentElement;
        let run = [];
        const flush = () => {
            const anchor = run.at(-1)?.nextSibling ?? null;
            run.map((row, index) => ({ row, index, key: keyOf(row.children[column]?.textContent || '') }))
                .sort((a, b) => (compare(a.key, b.key) * (direction === 'asc' ? 1 : -1)) || a.index - b.index)
                .forEach(({ row }) => box.insertBefore(row, anchor));
            run = [];
        };
        [...box.children].forEach((element) => (element.classList.contains('lm-tab-row') ? run.push(element) : flush()));
        flush();
    };
    document.addEventListener('click', (event) => {
        const button = event.target.closest('.lm-th-sort');
        if (!button) return;
        const gridCell = button.closest('.lm-tab-thead > *');
        if (gridCell) {
            sortGrid(gridCell);
            return;
        }
        const th = button.closest('th');
        const table = th.closest('table');
        const column = [...th.parentElement.cells].indexOf(th);
        const direction = th.classList.contains('is-asc') ? 'desc' : 'asc';
        table.querySelectorAll('th.lm-client-sort').forEach((cell) => {
            cell.classList.remove('is-sorted', 'is-asc', 'is-desc');
            cell.setAttribute('aria-sort', 'none');
        });
        th.classList.add('is-sorted', `is-${direction}`);
        th.setAttribute('aria-sort', direction === 'asc' ? 'ascending' : 'descending');
        const body = table.tBodies[0];
        const width = th.parentElement.cells.length;
        const all = [...body.rows];
        const rows = all.filter((row) => row.cells.length === width).map((row, index) => ({ row, index, key: keyOf(row.cells[column]?.textContent || '') }));
        rows.sort((a, b) => (compare(a.key, b.key) * (direction === 'asc' ? 1 : -1)) || a.index - b.index);
        body.append(...rows.map(({ row }) => row), ...all.filter((row) => row.cells.length !== width));
    });
    document.addEventListener('DOMContentLoaded', () => scan(document));
    document.addEventListener('splade:rendered', () => scan(document));
    new MutationObserver((mutations) => mutations.forEach(({ addedNodes }) => addedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) scan(node);
    }))).observe(document.documentElement, { childList: true, subtree: true });
    scan(document);
})();

/*
 * Show / hide on every CRM password field (user request, 10 Oct 2026): an eye button inside the
 * field's right edge switches it between hidden and plain text. Fields in dialogs that load later
 * are picked up as they arrive.
 */
(() => {
    const svg = (paths) => `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths}</svg>`;
    const EYE = svg('<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/>');
    const EYE_OFF = svg('<path d="m3 3 18 18M10.6 5.6c.5-.1.9-.1 1.4-.1 6 0 9.5 6.5 9.5 6.5a16.3 16.3 0 0 1-2.6 3.4M6.6 6.6C3.9 8.3 2.5 12 2.5 12s3.5 6.5 9.5 6.5c1.8 0 3.4-.6 4.8-1.4M9.9 9.9a3 3 0 0 0 4.2 4.2"/>');
    const WAND = svg('<path d="m21.6 3.6-1.2-1.2a1.2 1.2 0 0 0-1.7 0L2.4 18.6a1.2 1.2 0 0 0 0 1.7l1.2 1.3a1.2 1.2 0 0 0 1.7 0L21.6 5.4a1.2 1.2 0 0 0 0-1.8M14 7l3 3M5 6v4M19 14v4M10 2v2M7 8H3M21 16h-4M11 3H9"/>');
    const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    // 16 letters and digits from the browser's secure random source; bytes of 248 and over are
    // dropped so every character is equally likely, and a draw without a capital, a small letter
    // and a digit is redrawn.
    const generatePassword = () => {
        for (;;) {
            let value = '';
            const bytes = new Uint8Array(32);
            while (value.length < 16) {
                crypto.getRandomValues(bytes);
                for (const byte of bytes) if (byte < 248 && value.length < 16) value += ALPHABET[byte % 62];
            }
            if (/[A-Z]/.test(value) && /[a-z]/.test(value) && /\d/.test(value)) return value;
        }
    };
    // Fill the field and its "…_confirmation" partner, then show both so the password can be noted.
    const fillGenerated = (input) => {
        const value = generatePassword();
        const confirm = input.form?.querySelector(`[name="${input.name}_confirmation"]`);
        [input, confirm].filter(Boolean).forEach((field) => {
            field.value = value;
            field.dispatchEvent(new Event('input', { bubbles: true })); // Splade forms read v-model from input events
            field.dispatchEvent(new Event('change', { bubbles: true }));
            if (field.type !== 'password') return;
            const eye = field.closest('.lm-pw-wrap')?.querySelector('.lm-pw-toggle')
                ?? (field.id ? document.querySelector(`[data-toggle-password][data-target="${field.id}"]`) : null);
            if (eye) eye.click(); else field.type = 'text';
        });
        input.focus();
        input.select();
    };
    const enhance = (root) => {
        root.querySelectorAll('.leadmax-shell input[type="password"]:not([data-lm-pw])').forEach((input) => {
            input.dataset.lmPw = '1';
            const wrap = document.createElement('span');
            wrap.className = 'lm-pw-wrap';
            input.replaceWith(wrap);
            wrap.append(input);
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'lm-pw-toggle';
            button.setAttribute('aria-label', 'Show password');
            button.setAttribute('aria-pressed', 'false');
            button.innerHTML = EYE;
            button.addEventListener('click', () => {
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                button.innerHTML = show ? EYE_OFF : EYE;
                button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                button.setAttribute('aria-pressed', String(show));
                input.focus();
            });
            wrap.append(button);
        });
        // A Generate button on every field that sets a new password (data-lm-pw-generate):
        // CRM-Max puts it beside the eye, the classic admin as a link under the field.
        root.querySelectorAll('[data-lm-pw-generate]:not([data-lm-pw-gen])').forEach((input) => {
            input.dataset.lmPwGen = '1';
            const button = document.createElement('button');
            button.type = 'button';
            button.setAttribute('aria-label', 'Generate a password');
            button.title = 'Generate a 16-character password';
            button.addEventListener('click', () => fillGenerated(input));
            const wrap = input.closest('.lm-pw-wrap');
            if (wrap) {
                button.className = 'lm-pw-generate';
                button.innerHTML = WAND;
                wrap.classList.add('has-generate');
                wrap.append(button);
            } else {
                button.className = 'mt-1 text-xs font-medium text-indigo-600 hover:text-indigo-800';
                button.textContent = 'Generate password';
                (input.closest('label') ?? input).after(button);
            }
        });
    };
    new MutationObserver((mutations) => mutations.forEach(({ addedNodes }) => addedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) enhance(node.parentElement ?? node); // the parent, so a bare added input counts too
    }))).observe(document.documentElement, { childList: true, subtree: true });
    enhance(document);
})();
