// Native controls remain usable after Splade replaces the page content.
import { confirmExport, selectedLeadIds, filteredLeadTotal } from './leadmax-confirm';
import { clearMultiSelects } from './leadmax-filters';
let lastDialog;
let dialogTrigger;
let pendingDialog;
const leadPage = (root = document) => root.querySelector('[data-lm-leads], [data-lm-lead-detail]');
const closeDialog = () => {
    // Edits in the Add KYC wizard that no Continue has saved yet are only dropped on purpose (NC-21).
    if (document.querySelector('[data-lm-kyc-wizard][data-dirty]') && !window.confirm('Discard the KYC details you have not saved?')) return;
    // So is review feedback typed into a KYC request that was not submitted (NC-23.4).
    if (document.querySelector('[data-lm-kyc-request-modal][data-dirty]') && !window.confirm('Discard the review feedback you have not submitted?')) return;
    // A file viewer opened from a KYC request goes back to that request, not to the page (NC-23.4).
    const kycReturn = document.querySelector('.lm-modal-backdrop[data-lm-return-url]')?.dataset.lmReturnUrl;
    if (kycReturn) { loadLeadState(new URL(kycReturn, location.origin)).catch(showLoadError); return; }
    const kycRequest = document.querySelector('[data-lm-kyc-request-modal]')?.dataset.request;
    pendingDialog?.abort();
    document.querySelector('.lm-modal-backdrop')?.remove();
    document.body.classList.remove('lm-dialog-open');
    lastDialog = null;
    if (dialogTrigger?.isConnected) dialogTrigger.focus();
    // After a decision redrew the list, focus goes back to the same request's ID.
    else if (kycRequest) document.querySelector(`[data-lm-kyc-request="${kycRequest}"] [data-lm-kyc-open]`)?.focus();
};
const loadLeadState = async (url, refresh = false) => {
    pendingDialog?.abort();
    const controller = new AbortController();
    pendingDialog = controller;
    const response = await fetch(url, {
        headers: { 'X-LeadMax-Fragment': 'leads', Accept: 'text/html' },
        signal: controller.signal,
    });
    if (!response.ok || response.redirected) throw new Error('Unable to load lead details. Please try again.');
    const html = await response.text();
    if (controller.signal.aborted) return;
    const content = leadPage(new DOMParser().parseFromString(html, 'text/html'));
    if (!content) throw new Error('Unable to load lead details. Please refresh and try again.');
    document.querySelector('.lm-dialog-load-error')?.remove();
    if (refresh) {
        leadPage()?.replaceWith(content);
    } else {
        const dialog = content.querySelector('.lm-modal-backdrop');
        if (!dialog) throw new Error('This lead is no longer available. Please refresh the list.');
        document.querySelector('.lm-modal-backdrop')?.remove();
        leadPage()?.append(dialog);
    }
    focusDialog();
};
const showLoadError = error => {
    if (error.name === 'AbortError') return;
    document.querySelector('.lm-dialog-load-error')?.remove();
    const message = document.createElement('div');
    message.className = 'lm-dialog-load-error';
    message.setAttribute('role', 'alert');
    message.textContent = error.message;
    document.body.append(message);
};
const initLeadTableScroll = (root = document) => {
    const top = root.querySelector('[data-lm-scroll-top]');
    const table = root.querySelector('[data-lm-table-scroll]');
    if (!top || !table || top.dataset.lmScrollReady === '1') return;
    top.dataset.lmScrollReady = '1';
    let syncing = false;
    const size = () => { top.firstElementChild.style.width = `${table.querySelector('table')?.scrollWidth || table.scrollWidth}px`; };
    const sync = (source, target) => {
        if (syncing) return;
        syncing = true;
        target.scrollLeft = source.scrollLeft;
        requestAnimationFrame(() => { syncing = false; });
    };
    size();
    top.addEventListener('scroll', () => sync(top, table), { passive: true });
    table.addEventListener('scroll', () => sync(table, top), { passive: true });
    window.addEventListener('resize', size);
};
// The select that replaces a Lead Status badge keeps the badge's colour (LeadStatusPalette).
const setStatusTone = select => {
    // The lead page's Lead Status field carries each option's tone on the option itself.
    if (select?.hasAttribute('data-lm-status-select')) { select.dataset.lmTone = select.selectedOptions[0]?.dataset.lmTone || 'unset'; return; }
    if (select?.dataset.lmInlineEdit !== 'lead_status') return;
    let tones = {};
    try { tones = JSON.parse(document.querySelector('[data-lm-inline-config]')?.dataset.statusTones || '{}'); } catch { tones = {}; }
    select.dataset.lmTone = select.value ? (tones[select.value.trim().toLowerCase()] ?? 'custom') : 'unset';
};
/* Lead Status pickers (leads list inline editor, lead page field). A native <select> cannot show
   icons in its open list, so a listbox button sits over it: the select stays the form value and
   still fires the change event the save handlers listen to. The list is position:fixed inside
   the picker, so it escapes the table's scroll box but still counts as inside the field's form. */
let statusPickerId = 0;
// The lead page's Country field uses the same picker, with each country's flag instead of a status icon.
const isCountryPicker = select => select.matches('[data-lm-country-select]');
const pickerGlyph = (select, option) => {
    if (!isCountryPicker(select)) return Object.assign(document.createElement('span'), { className: 'lm-status-glyph' });
    const flag = option?.dataset.flag;
    const glyph = flag ? Object.assign(document.createElement('img'), { className: 'lm-flag', src: flag, width: 18, height: 14, alt: '' })
        : Object.assign(document.createElement('span'), { className: 'lm-flag is-unknown', textContent: '🌐' });
    glyph.setAttribute('aria-hidden', 'true');
    return glyph;
};
const statusOptionTone = (option, select) => option?.dataset.lmTone || (option?.value ? (option.selected && select?.dataset.lmTone) || 'custom' : 'unset');
const syncStatusPicker = select => {
    const button = select.lmStatusPicker;
    if (!button) return;
    const option = select.selectedOptions[0];
    const label = option?.textContent.trim() || 'Not set';
    if (isCountryPicker(select)) button.firstElementChild.replaceWith(pickerGlyph(select, option));
    else button.dataset.lmTone = statusOptionTone(option, select);
    button.querySelector('.lm-status-pick-label').textContent = label;
    button.setAttribute('aria-label', `${select.getAttribute('aria-label') || 'Lead status'}: ${label}`);
    button.disabled = select.disabled;
    button.classList.toggle('is-saving', select.classList.contains('is-saving'));
    button.classList.toggle('is-saved', select.classList.contains('is-saved'));
};
const enhanceStatusSelect = select => {
    if (select.lmStatusPicker) return;
    const wrap = document.createElement('span');
    wrap.className = 'lm-status-pick';
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'lm-status-pick-button';
    button.setAttribute('aria-haspopup', 'listbox');
    button.setAttribute('aria-expanded', 'false');
    button.innerHTML = '<span class="lm-status-glyph" aria-hidden="true"></span><span class="lm-status-pick-label"></span><svg width="10" height="6" viewBox="0 0 10 6" fill="none" aria-hidden="true"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    const list = document.createElement('ul');
    list.className = 'lm-status-pick-list';
    list.id = `lm-status-pick-${++statusPickerId}`;
    list.setAttribute('role', 'listbox');
    list.setAttribute('aria-label', select.getAttribute('aria-label') || 'Lead status');
    list.tabIndex = -1;
    list.hidden = true;
    button.setAttribute('aria-controls', list.id);
    if (isCountryPicker(select)) {
        wrap.classList.add('is-country');
        // The button shows the flag now, so the select's separate preview flag would be a second one.
        select.parentElement.querySelector('img[data-lm-country-flag]')?.remove();
    }
    select.before(wrap);
    wrap.append(button, list, select);
    select.classList.add('lm-status-pick-native');
    select.tabIndex = -1;
    select.setAttribute('aria-hidden', 'true');
    select.lmStatusPicker = button;
    button.lmStatusSelect = select;
    list.lmStatusSelect = select;
    // The field editor and the save handlers set value, disabled and the saving classes directly.
    select.addEventListener('focus', () => { syncStatusPicker(select); button.focus(); });
    select.addEventListener('change', () => syncStatusPicker(select));
    const observer = new MutationObserver(() => syncStatusPicker(select));
    observer.observe(select, { attributes: true, attributeFilter: ['disabled', 'class', 'data-lm-tone'] });
    // Reopening the lead page editor restores the saved value without a change event.
    if (select.form) observer.observe(select.form, { attributes: true, attributeFilter: ['hidden'] });
    syncStatusPicker(select);
};
const enhanceStatusSelects = (root = document) => root.querySelectorAll('select[data-lm-inline-edit=lead_status], select[data-lm-status-select], .lm-field-editor select[data-lm-country-select]').forEach(enhanceStatusSelect);
const activateStatusOption = (list, item) => {
    if (!item) return;
    list.querySelector('.is-active')?.classList.remove('is-active');
    item.classList.add('is-active');
    list.setAttribute('aria-activedescendant', item.id);
    // Scroll the list only: scrollIntoView also scrolls the page, and a page scroll closes the list.
    if (item.offsetTop < list.scrollTop) list.scrollTop = item.offsetTop;
    else if (item.offsetTop + item.offsetHeight > list.scrollTop + list.clientHeight) list.scrollTop = item.offsetTop + item.offsetHeight - list.clientHeight;
};
const closeStatusList = (list, focusButton = false) => {
    if (!list || list.hidden) return;
    list.hidden = true;
    const button = list.lmStatusSelect.lmStatusPicker;
    button.setAttribute('aria-expanded', 'false');
    if (focusButton) button.focus();
};
const openStatusList = button => {
    const select = button.lmStatusSelect;
    const list = button.nextElementSibling;
    document.querySelectorAll('.lm-status-pick-list:not([hidden])').forEach(other => closeStatusList(other));
    list.replaceChildren(...[...select.options].map((option, index) => {
        const item = document.createElement('li');
        item.id = `${list.id}-${index}`;
        item.dataset.index = index;
        if (!isCountryPicker(select)) item.dataset.lmTone = statusOptionTone(option, select);
        item.setAttribute('role', 'option');
        item.setAttribute('aria-selected', String(option.selected));
        if (option.disabled) item.setAttribute('aria-disabled', 'true');
        item.append(pickerGlyph(select, option), option.textContent.trim() || 'Not set');
        return item;
    }));
    // Below the button when it fits, otherwise above; never wider than the screen.
    const rect = button.getBoundingClientRect();
    const below = innerHeight - rect.bottom - 12;
    const above = rect.top - 12;
    const height = Math.min(320, Math.max(below, above));
    list.style.minWidth = `${Math.max(rect.width, 160)}px`;
    list.style.maxHeight = `${height}px`;
    list.style.left = `${Math.max(8, Math.min(rect.left, innerWidth - Math.max(rect.width, 160) - 8))}px`;
    list.style.top = below >= Math.min(200, above) ? `${rect.bottom + 4}px` : '';
    list.style.bottom = below >= Math.min(200, above) ? '' : `${innerHeight - rect.top + 4}px`;
    list.hidden = false;
    button.setAttribute('aria-expanded', 'true');
    activateStatusOption(list, list.querySelector('[aria-selected=true]') || list.querySelector('[role=option]:not([aria-disabled])'));
    list.focus();
};
const chooseStatusOption = (list, item) => {
    if (!item || item.getAttribute('aria-disabled') === 'true') return;
    const select = list.lmStatusSelect;
    closeStatusList(list, true);
    if (select.selectedIndex === Number(item.dataset.index)) return;
    select.selectedIndex = Number(item.dataset.index);
    select.dispatchEvent(new Event('change', { bubbles: true }));
};
document.addEventListener('click', event => {
    const button = event.target.closest('.lm-status-pick-button');
    const item = event.target.closest('.lm-status-pick-list [role=option]');
    if (button) {
        const list = button.nextElementSibling;
        if (list.hidden) openStatusList(button); else closeStatusList(list, true);
    } else if (item) {
        chooseStatusOption(item.closest('[role=listbox]'), item);
    }
    document.querySelectorAll('.lm-status-pick-list:not([hidden])').forEach(list => { if (!list.parentElement.contains(event.target)) closeStatusList(list); });
});
// Capture phase on window, so Escape closes only the list -- not the field editor or a dialog.
window.addEventListener('keydown', event => {
    const button = event.target.closest?.('.lm-status-pick-button');
    if (button && ['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) {
        event.preventDefault();
        event.stopPropagation();
        if (!button.disabled) openStatusList(button);
        return;
    }
    const list = event.target.closest?.('.lm-status-pick-list');
    if (!list) return;
    const items = [...list.querySelectorAll('[role=option]:not([aria-disabled=true])')];
    const current = items.indexOf(list.querySelector('.is-active'));
    const move = index => { event.preventDefault(); activateStatusOption(list, items[Math.max(0, Math.min(items.length - 1, index))]); };
    if (event.key === 'ArrowDown') move(current + 1);
    else if (event.key === 'ArrowUp') move(current - 1);
    else if (event.key === 'Home') move(0);
    else if (event.key === 'End') move(items.length - 1);
    else if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); chooseStatusOption(list, list.querySelector('.is-active')); }
    else if (event.key === 'Escape') { event.preventDefault(); closeStatusList(list, true); }
    else if (event.key === 'Tab') closeStatusList(list, true);
    else if (event.key.length === 1 && /\S/.test(event.key)) {
        // Type-ahead: letters typed in quick succession spell the start of a name ("uni" -> United...);
        // a single letter again moves on to the next match.
        const now = Date.now();
        list.lmTyped = now - (list.lmTypedAt || 0) < 800 ? (list.lmTyped || '') + event.key.toLowerCase() : event.key.toLowerCase();
        list.lmTypedAt = now;
        const typed = list.lmTyped;
        const from = typed.length > 1 ? current : current + 1;
        const next = [...items.slice(Math.max(0, from)), ...items.slice(0, Math.max(0, from))].find(item => item.textContent.trim().toLowerCase().startsWith(typed));
        if (next) { event.preventDefault(); activateStatusOption(list, next); }
    }
    event.stopPropagation();
}, true);
// A fixed list would drift away from its button: close it when the page scrolls or resizes.
window.addEventListener('scroll', event => document.querySelectorAll('.lm-status-pick-list:not([hidden])').forEach(list => { if (!list.contains(event.target)) closeStatusList(list); }), true);
window.addEventListener('resize', () => document.querySelectorAll('.lm-status-pick-list:not([hidden])').forEach(list => closeStatusList(list)));
document.addEventListener('focusout', event => {
    const list = event.target.closest?.('.lm-status-pick-list');
    if (list && !list.parentElement.contains(event.relatedTarget)) closeStatusList(list);
});
const initInlineLeadEditors = (root = document) => {
    const config = root.querySelector('[data-lm-inline-config]');
    if (!config) return;
    let state = {};
    try { state = JSON.parse(atob(config.dataset.state || 'e30=')); } catch { state = {}; }
    // Found by name rather than by column index: the index moved every time a column was
    // added, and silently pointed the editors at the wrong cells when it did.
    const columns = [
        { field: 'manager_id', endpoint: 'owner', allowed: config.dataset.canAssign === '1' },
        { field: 'lead_status', endpoint: 'lead-status', allowed: true },
        { field: 'status', endpoint: 'status', allowed: true },
    ];
    root.querySelectorAll('.lm-updated-leads-table tbody tr').forEach(row => {
        const id = row.querySelector('[data-lm-select]')?.value;
        if (!id) return;
        columns.forEach(({ field, endpoint, allowed }) => {
            if (!allowed) return;
            const target = row.querySelector(`[data-lm-cell="${field}"]`);
            // A plain hidden <select> rather than a <template>: Splade rebuilds this markup client
            // side, and a <template> it creates keeps its children in childNodes, leaving .content
            // empty -- so cloning from .content threw and killed every inline control on the page.
            const proto = config.querySelector(`[data-lm-inline-proto="${field}"]`);
            if (!target || !proto || target.querySelector('[data-lm-inline-edit]')) return;
            const select = proto.cloneNode(true);
            delete select.dataset.lmInlineProto;
            select.dataset.lmInlineEdit = field;
            select.dataset.lmEndpoint = `${config.dataset.baseUrl}/${encodeURIComponent(id)}/${endpoint}`;
            const stored = state[id]?.[field];
            const displayed = target.textContent.trim();
            // A retired or tenant-specific Lead Status that is no longer in the list stays readable
            // instead of collapsing to "Not set".
            if (field === 'lead_status' && stored && ![...select.options].some((option) => option.value === stored)) {
                select.add(new Option(stored, stored));
            }
            select.value = stored ?? '';
            if (stored == null && displayed) {
                const match = [...select.options].find(option => option.textContent.trim().toLowerCase() === displayed.toLowerCase());
                if (match) select.value = match.value;
            }
            select.dataset.lmPrevious = select.value;
            setStatusTone(select);
            target.replaceChildren(select);
            if (field === 'lead_status') enhanceStatusSelect(select);
        });
    });
};
const initRenderedLeadTable = () => requestAnimationFrame(() => {
    initLeadTableScroll();
    initInlineLeadEditors();
    enhanceStatusSelects();
});
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRenderedLeadTable, { once: true });
} else {
    initRenderedLeadTable();
}
document.addEventListener('splade:rendered', initRenderedLeadTable);
const leadTableObserver = new MutationObserver(() => {
    if (document.querySelector('[data-lm-inline-config]') && !document.querySelector('[data-lm-inline-edit]')) initRenderedLeadTable();
    // Splade renders the lead page's fields after load, so its status select can arrive late.
    if (document.querySelector('select[data-lm-status-select]:not(.lm-status-pick-native)')) enhanceStatusSelects();
});
leadTableObserver.observe(document.documentElement, { childList: true, subtree: true });

const closePhoneContactMenu = () => document.querySelector('.lm-phone-contact-menu')?.remove();
const showPhoneContactMenu = (icon, phone) => {
    closePhoneContactMenu();
    const digits = phone.replace(/\D/g, '');
    if (!digits) return;
    const menu = document.createElement('div');
    menu.className = 'lm-phone-contact-menu';
    menu.setAttribute('role', 'menu');
    // Line icons for each option (user request, 10 Oct 2026; the ☎ ◉ ➤ glyphs were unclear).
    const glyph = (paths) => `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:block">${paths}</svg>`;
    const callIcon = glyph('<path d="M20.5 16.4v2.7a1.9 1.9 0 0 1-2.1 1.9A17.6 17.6 0 0 1 3 5.6 1.9 1.9 0 0 1 4.9 3.5h2.7a1.9 1.9 0 0 1 1.9 1.6c.1.9.4 1.8.7 2.6a1.9 1.9 0 0 1-.4 2L8.6 10.9a15 15 0 0 0 4.5 4.5l1.2-1.2a1.9 1.9 0 0 1 2-.4c.8.3 1.7.6 2.6.7a1.9 1.9 0 0 1 1.6 1.9Z"/>');
    const whatsappIcon = glyph('<path d="M4 20.5 5.3 16A8.5 8.5 0 1 1 8.4 19L4 20.5Z"/><path d="M9 8.5c0 3.4 3 6.5 6.5 6.5l1-1.6-2-1-1 .9c-1.3-.5-2.3-1.5-2.8-2.8l.9-1-1-2L9 8.5Z"/>');
    const telegramIcon = glyph('<path d="M21 3.5 2.8 10.6l6.4 2.3L18 6.6l-6.7 7.6 6.6 6.3L21 3.5Z"/>');
    [[callIcon, 'Call', `tel:${phone.replace(/\s/g, '')}`], [whatsappIcon, 'WhatsApp', `https://wa.me/${digits}`], [telegramIcon, 'Telegram', `tg://resolve?phone=${digits}`]].forEach(([symbol, label, href]) => {
        const link = document.createElement('a');
        link.href = href;
        link.setAttribute('role', 'menuitem');
        if (label !== 'Call') link.target = '_blank';
        link.rel = 'noopener';
        const mark = document.createElement('span');
        mark.innerHTML = symbol; // a fixed SVG string from above, never user data
        const text = document.createElement('strong');
        text.textContent = label;
        link.append(mark, text);
        menu.append(link);
    });
    document.body.append(menu);
    const box = icon.getBoundingClientRect();
    menu.style.left = `${Math.max(8, Math.min(box.left, innerWidth - menu.offsetWidth - 8))}px`;
    menu.style.top = `${Math.min(box.bottom + 8, innerHeight - menu.offsetHeight - 8)}px`;
    menu.querySelector('a')?.focus();
};
/**
 * Copy a cell's value to the clipboard.
 *
 * Both the phone and the email cell carried data-lm-copy-value and a pointer cursor, but
 * nothing ever read them -- the only copy handler on the project is scoped to the lead
 * DETAILS grid (.lm-detail-control in leadmax.js), so neither column actually copied from
 * the list. This wires both.
 */
/**
 * Show a truncated cell's full value on hover.
 *
 * Set on demand rather than rendered into every cell: a title on a cell whose text already
 * fits is a tooltip that tells you nothing, over every column of every row. Measuring at
 * hover time also means it stays right when a column is resized or the text changes, which
 * a server-rendered title attribute would not.
 */
/* ---------------------------------------------------------------------------
   Drag a column header to move that column.

   Held in memory only, by the client's choice -- it resets on a page reload.
   It does NOT reset when the rows are swapped in place, though: filtering,
   sorting and paging all replace the table without reloading the page, and an
   arrangement that vanished every time you changed a filter would be reported
   as a bug rather than read as "not persisted".
   --------------------------------------------------------------------------- */
let columnOrder = null;            // data-lm-col keys, in the order they are shown
let dragColumn = null;

const headerCells = (table) => [...table.querySelectorAll('thead th[data-lm-col]')];

/**
 * Move one column, in every structure that has a cell per column: the colgroup
 * (so the widths travel with it under table-layout:fixed), the header row and
 * every body row.
 */
const moveColumnCells = (table, fromCell, toCell) => {
    if (fromCell === toCell) return;
    const expected = table.querySelector('thead tr')?.children.length ?? 0;
    const groups = [table.querySelector('colgroup'), ...table.querySelectorAll('thead tr, tbody tr')];

    for (const group of groups) {
        // The "no leads found" row is a single cell with a colspan; moving within it
        // would be meaningless and would corrupt the row.
        if (!group || group.children.length !== expected) continue;
        const cells = [...group.children];
        const node = cells[fromCell];
        const ref = cells[toCell];
        if (!node || !ref) continue;
        fromCell < toCell ? ref.after(node) : ref.before(node);
    }
};

const rememberColumnOrder = (table) => {
    columnOrder = headerCells(table).map((th) => th.dataset.lmCol);
};

/** Re-apply the remembered arrangement to a freshly swapped-in table. */
const restoreColumnOrder = (page) => {
    const table = page?.querySelector('.lm-updated-leads-table');
    if (!table || !columnOrder) return;

    for (let target = 0; target < columnOrder.length; target += 1) {
        const shown = headerCells(table).map((th) => th.dataset.lmCol);
        const from = shown.indexOf(columnOrder[target]);
        if (from === -1 || from === target) continue;
        // +1 everywhere: cell 0 is the select-all checkbox, which never moves.
        moveColumnCells(table, from + 1, target + 1);
    }
};

/* ---------------------------------------------------------------------------
   Column resizing: drag the right edge of a header.

   The width lives on the <col>, not the cell -- the table is table-layout:fixed,
   so the colgroup is the only thing that decides a column's width, and a width
   set on a th would be ignored. Remembered alongside the order and re-applied
   after an in-page swap, because a filter or a sort replaces the whole table and
   a width that vanished on every keystroke would be worse than no resizing.
   --------------------------------------------------------------------------- */
const MIN_COLUMN = 56;
const columnWidths = {};             // data-lm-col key -> px
let resizing = null;

const colFor = (th) => {
    const table = th.closest('table');
    const index = [...th.parentElement.children].indexOf(th);
    return table?.querySelector('colgroup')?.children[index] ?? null;
};

/** Re-apply remembered widths to a freshly swapped-in table. */
const restoreColumnWidths = (page) => {
    const table = page?.querySelector('.lm-updated-leads-table');
    if (!table) return;
    for (const th of headerCells(table)) {
        const width = columnWidths[th.dataset.lmCol];
        const col = width && colFor(th);
        if (col) col.style.width = width + 'px';
    }
};

document.addEventListener('pointerdown', (event) => {
    const grip = event.target.closest?.('.lm-col-grip');
    if (!grip || event.button !== 0) return;

    const th = grip.closest('th[data-lm-col]');
    const col = th && colFor(th);
    if (!col) return;

    event.preventDefault();
    // The header is draggable for reordering, and a mousedown on it would start
    // that drag instead of this one. Put it back on pointerup.
    th.draggable = false;
    resizing = { th, col, startX: event.clientX, startWidth: th.getBoundingClientRect().width };
    grip.setPointerCapture(event.pointerId);
    document.body.classList.add('lm-col-resizing');
});

document.addEventListener('pointermove', (event) => {
    if (!resizing) return;
    const width = Math.max(MIN_COLUMN, Math.round(resizing.startWidth + (event.clientX - resizing.startX)));
    resizing.col.style.width = width + 'px';
    columnWidths[resizing.th.dataset.lmCol] = width;
});

const endResize = () => {
    if (!resizing) return;
    resizing.th.draggable = true;
    resizing = null;
    document.body.classList.remove('lm-col-resizing');
};
document.addEventListener('pointerup', endResize);
document.addEventListener('pointercancel', endResize);

/** Give every data column a grip. Runs again after a swap, on the new header. */
const addColumnGrips = (root = document) => {
    for (const th of root.querySelectorAll('.lm-leads-list thead th[data-lm-col]')) {
        if (th.querySelector('.lm-col-grip')) continue;
        const grip = document.createElement('span');
        grip.className = 'lm-col-grip';
        grip.setAttribute('aria-hidden', 'true');
        th.appendChild(grip);
    }
};

addColumnGrips();
document.addEventListener('DOMContentLoaded', () => addColumnGrips());

const clearDropMarks = () => document.querySelectorAll('.lm-drop-before, .lm-drop-after')
    .forEach((th) => th.classList.remove('lm-drop-before', 'lm-drop-after'));

document.addEventListener('dragstart', (event) => {
    const th = event.target.closest?.('.lm-leads-list thead th[data-lm-col]');
    if (!th) return;
    dragColumn = th;
    th.classList.add('lm-dragging');
    event.dataTransfer.effectAllowed = 'move';
    // Firefox will not start a drag at all without data on the transfer.
    event.dataTransfer.setData('text/plain', th.dataset.lmCol);
});

document.addEventListener('dragover', (event) => {
    if (!dragColumn) return;
    const th = event.target.closest?.('.lm-leads-list thead th[data-lm-col]');
    if (!th || th === dragColumn) return;
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';

    // Which side of the target it would land on, so the line sits where the column will go.
    const box = th.getBoundingClientRect();
    const after = event.clientX > box.left + box.width / 2;
    clearDropMarks();
    th.classList.add(after ? 'lm-drop-after' : 'lm-drop-before');
});

document.addEventListener('drop', (event) => {
    if (!dragColumn) return;
    const th = event.target.closest?.('.lm-leads-list thead th[data-lm-col]');
    if (!th || th === dragColumn) return;
    event.preventDefault();

    const table = th.closest('table');
    const cells = [...th.parentElement.children];
    const fromCell = cells.indexOf(dragColumn);
    let toCell = cells.indexOf(th);

    // Dropping on the right half means "after this column". Moving left to right already
    // lands after the target, so only a right-to-left move needs the nudge.
    const box = th.getBoundingClientRect();
    if (event.clientX > box.left + box.width / 2 && fromCell > toCell) toCell += 1;
    if (event.clientX <= box.left + box.width / 2 && fromCell < toCell) toCell -= 1;

    moveColumnCells(table, fromCell, toCell);
    rememberColumnOrder(table);
    clearDropMarks();
});

document.addEventListener('dragend', () => {
    dragColumn?.classList.remove('lm-dragging');
    dragColumn = null;
    clearDropMarks();
});

const TRUNCATABLE = '.lm-leads-list tbody td, .lm-leads-list thead th';
window.addEventListener('mouseover', (event) => {
    const cell = event.target.closest(TRUNCATABLE);
    // A title already here is the answer from last time; nothing more to work out.
    if (!cell || cell.hasAttribute('title')) return;

    // Measure the element that actually clips. Where a cell mixes an icon with text, that is
    // an inner element, because the text is the only part that can truncate.
    //
    // Matched by attribute rather than by shape: the phone and email cells wrap their
    // contents in a .lm-cell-row flex row, so a child combinator like `.lm-email-cell>span`
    // stops matching the moment that wrapper exists -- which is exactly how email hover came
    // to do nothing.
    const inner = cell.querySelector('[data-lm-copy-value], [data-lm-phone], .lm-agent-name, .lm-country-name, .lm-name-cell span, .lm-name-link, .lm-wrap-cell>span, .lm-lead-status, .lm-retention-status, .lm-id');

    // Only the columns that CLIP report a scrollWidth bigger than their clientWidth.
    // Several here are deliberately overflow:visible -- the phone cell so its contact
    // menu can escape, the agent and country cells, the actions column -- and their
    // text spills over the next column instead of being cut off. Those never report a
    // scroll difference at all, which is why hover answered for the email column and
    // for almost nothing else. So fall back to asking where the text actually ends
    // against where the cell's content box ends, which holds either way.
    const overflows = (node) => {
        if (!node) return false;
        if (node.scrollWidth > node.clientWidth + 1) return true;

        const range = document.createRange();
        range.selectNodeContents(node);
        const text = range.getBoundingClientRect();
        if (!text.width) return false;

        const box = cell.getBoundingClientRect();
        const pad = parseFloat(getComputedStyle(cell).paddingRight) || 0;
        return text.right > box.right - pad + 1;
    };

    // Deliberately not cached when it does NOT overflow: a column can be resized or
    // moved by a drag, and web fonts can land after the first hover.
    if (!overflows(inner) && !overflows(cell)) return;

    const text = (inner || cell).innerText.trim();
    if (text) cell.setAttribute('title', text);
});

const copyToast = (message, type = 'success') => {
    // Same look as the lead details page's toast, which is defined inside an IIFE over there
    // and so cannot be imported. The class is in the shared bundle either way.
    const node = document.createElement('div');
    node.className = `lm-detail-toast ${type}`;
    node.setAttribute('role', type === 'error' ? 'alert' : 'status');
    node.textContent = message;
    document.body.append(node);
    setTimeout(() => node.remove(), 2500);
};

const copyCellValue = async (target) => {
    const value = (target.dataset.lmCopyValue || target.textContent || '').trim();
    if (!value) return;
    try {
        await navigator.clipboard.writeText(value);
        copyToast(`Copied: ${value.slice(0, 40)}`);
    } catch {
        copyToast('Copy failed', 'error');
    }
};

window.addEventListener('click', event => {
    const icon = event.target.closest('.lm-phone-cell .lm-lead-icon');
    if (icon) {
        event.preventDefault();
        event.stopImmediatePropagation();
        showPhoneContactMenu(icon, icon.closest('.lm-phone-cell').querySelector('[data-lm-phone]')?.textContent.trim() || '');
        return;
    }
    if (!event.target.closest('.lm-phone-contact-menu')) closePhoneContactMenu();

    // The mail icon is a real mailto link and keeps its own job; only the text copies.
    const copyable = event.target.closest('.lm-phone-cell [data-lm-copy-value], .lm-email-cell [data-lm-copy-value]');
    if (copyable && !event.target.closest('a')) {
        event.preventDefault();
        event.stopImmediatePropagation();
        copyCellValue(copyable);
    }
}, true);

/**
 * Refresh the parts that describe the current result set but sit OUTSIDE .lm-leads-list, so an
 * in-place swap does not leave them describing the previous query.
 *
 * Each of these is a wrong-data bug rather than a cosmetic one: a stale KPI href silently
 * re-applies filters the user has since cleared, a stale bulk bar offers "select all matching"
 * over a count that no longer matches, and stale row menus act on leads that have scrolled out
 * of the result set.
 */
const syncOutsideList = (page, parsed, metricsReplaced) => {
    const incomingRoot = parsed.querySelector('[data-lm-leads]');
    if (incomingRoot) {
        // Arms "select all matching" in the bulk bar; wrong value here means bulk actions
        // target the wrong set.
        incomingRoot.hasAttribute('data-lm-bulk-all')
            ? page.setAttribute('data-lm-bulk-all', incomingRoot.getAttribute('data-lm-bulk-all'))
            : page.removeAttribute('data-lm-bulk-all');
    }

    // The card COUNTS are deliberately filter-independent, but their hrefs carry the live query
    // string, so they still have to move even when the cards themselves are not replaced.
    if (!metricsReplaced) {
        const from = [...parsed.querySelectorAll('.lm-leads-kpis .lm-kpi-link')];
        const to = [...page.querySelectorAll('.lm-leads-kpis .lm-kpi-link')];
        if (from.length === to.length) {
            to.forEach((link, index) => {
                link.setAttribute('href', from[index].getAttribute('href'));
                const active = from[index].hasAttribute('aria-current');
                active ? link.setAttribute('aria-current', 'true') : link.removeAttribute('aria-current');
                link.closest('article')?.classList.toggle('is-active', active);
            });
        }
    }

    // One menu per row, rendered as siblings of the list rather than inside it.
    const menus = [...parsed.querySelectorAll('.lm-row-menu')];
    if (menus.length || page.querySelector('.lm-row-menu')) {
        page.querySelectorAll('.lm-row-menu').forEach(node => node.remove());
        menus.forEach(menu => page.append(menu));
    }
};

/**
 * @param {object} [opts]
 * @param {boolean} [opts.keepHeader] Swap only the results, leaving the list header in place.
 *        Live search needs this: the search box sits inside that header, so replacing the whole
 *        section would tear the input out from under the cursor on every keystroke.
 * @param {AbortSignal} [opts.signal] Lets a newer request cancel one still in flight, so a slow
 *        early response cannot land after a faster later one and show stale rows.
 */
const loadLeadResults = async (url, updateMetrics = true, push = true, opts = {}) => {
    const page = document.querySelector('[data-lm-leads]');
    const current = page?.querySelector('.lm-leads-list');
    if (!page || !current) return location.assign(url);
    const filtersOpen = !page.querySelector('[data-lm-filter-panel]')?.hidden;
    const response = await fetch(url, { headers: { 'X-LeadMax-Fragment': 'leads', Accept: 'text/html' }, signal: opts.signal });
        if (!response.ok || response.redirected) throw new Error('Unable to load leads for this period.');
        const parsed = new DOMParser().parseFromString(await response.text(), 'text/html');
        const incoming = parsed.querySelector('.lm-leads-list');
        if (!incoming) throw new Error('Unable to refresh the leads table.');
        if (updateMetrics) {
            const currentMetrics = page.querySelector('.lm-leads-kpis');
            const incomingMetrics = parsed.querySelector('.lm-leads-kpis');
            if (currentMetrics && incomingMetrics) currentMetrics.replaceWith(incomingMetrics);
        }
        // How far the table is scrolled sideways. Every swap replaces the scroller, so without
        // this a sort sent the table back to the first column -- and the columns you were
        // looking at when you clicked the header are the ones you wanted to keep seeing.
        const scrolledTo = current.querySelector('[data-lm-table-scroll]')?.scrollLeft ?? 0;

        if (opts.keepHeader) {
            // Replace only what the results actually change. The header (search box, filter
            // button) and the filter panel are left exactly as they are.
            const countTo = current.querySelector('.lm-count');
            const countFrom = incoming.querySelector('.lm-count');
            if (countTo && countFrom) {
                countTo.textContent = countFrom.textContent;
                // Carry data-lm-total across too: the export confirmation reads it, and a stale
                // number there would state the wrong figure after a live search or card filter.
                countTo.dataset.lmTotal = countFrom.dataset.lmTotal ?? '';
            }

            // [data-lm-inline-config] carries data-state: the per-lead manager/status map the
            // Agent, Lead Status and Retention Status dropdowns read to pick their selected
            // option. It describes THIS result set, so it has to travel with the rows. Left
            // behind, a row whose lead is not in the stale map gets no match and falls back to
            // the first option — an assigned lead silently reading "Unassigned".
            // querySelectorAll, not querySelector: there are two paginations now, above the
            // table and below it. Taking only the first left the bottom one showing the
            // previous page's numbers after a live filter or a sort.
            for (const selector of ['.lm-leads-toprow', '.lm-leads-scroll-top', '.lm-leads-scroll', '.lm-pagination-bottom', '.lm-bulk-actions', '[data-lm-inline-config]']) {
                const from = [...incoming.querySelectorAll(selector)];
                const to = [...current.querySelectorAll(selector)];
                const shared = Math.min(from.length, to.length);
                for (let i = 0; i < shared; i += 1) to[i].replaceWith(from[i]);
                // e.g. pagination appearing again once there is more than one page
                for (let i = shared; i < from.length; i += 1) current.append(from[i]);
                // e.g. results now fit on one page
                for (let i = shared; i < to.length; i += 1) to[i].remove();
            }
        } else {
            current.replaceWith(incoming);
        }
        // The swap brings a table in the server's column order; put it back the way the
        // user arranged it before anything measures or paints it.
        restoreColumnOrder(page);
        restoreColumnWidths(page);
        addColumnGrips(page);
        syncOutsideList(page, parsed, updateMetrics);
        const nextFilterPanel = page.querySelector('[data-lm-filter-panel]');
        if (nextFilterPanel) nextFilterPanel.hidden = !filtersOpen;
        page.querySelector('[data-lm-filters]')?.setAttribute('aria-expanded', String(filtersOpen));
        initLeadTableScroll(page);
        if (scrolledTo) {
            // Both the table and the strip above it: they mirror each other, and setting one
            // does not drive the other here because neither has been scrolled by a user yet.
            const table = page.querySelector('[data-lm-table-scroll]');
            const strip = page.querySelector('[data-lm-scroll-top]');
            if (table) table.scrollLeft = scrolledTo;
            if (strip) strip.scrollLeft = scrolledTo;
        }
        initInlineLeadEditors(page);
        const selectedPeriod = new URL(url).searchParams.get('period');
        page.querySelectorAll('.leadmax-dashboard-periods > button[name="period"]').forEach(button => button.classList.toggle('is-selected', button.value === selectedPeriod));
    if (push) history.pushState({ leadmaxPeriod: true }, '', url);
};

const periodUrl = (form, submitter) => {
    const url = new URL(location.href);
    ['period', 'from', 'to', 'page', 'lead', 'dialog', 'tab'].forEach(key => url.searchParams.delete(key));
    const data = new FormData(form, submitter || undefined);
    // The period form carries the active filters as hidden inputs, and a multi-select carries
    // several under one name -- set() would keep only the last, so changing period would
    // quietly narrow the filters it is supposed to preserve.
    data.forEach((value, key) => {
        if (String(value).trim() === '') return;
        key.endsWith('[]') ? url.searchParams.append(key, value) : url.searchParams.set(key, value);
    });
    return url;
};
const runPeriodFilter = (event, form, submitter) => {
    event.preventDefault();
    event.stopImmediatePropagation();
    const url = periodUrl(form, submitter);
    loadLeadResults(url, true).catch(showLoadError);
};
// Capture on window before Splade sees the GET form, otherwise it starts its global page loader.
window.addEventListener('click', event => {
    const button = event.target.closest('.leadmax-dashboard-periods > button[name="period"]');
    if (!button) return;
    runPeriodFilter(event, button.form, button);
}, true);
window.addEventListener('submit', event => {
    const form = event.target.closest('.leadmax-dashboard-periods');
    if (!form) return;
    runPeriodFilter(event, form, event.submitter);
}, true);

// The KPI cards are the stage filter. Swap the results in place rather than following the link:
// a full navigation re-runs the sidebar's restore-from-localStorage, so a collapsed sidebar
// flashes open and snaps shut again on every card click.
//
// They stay real <a href> elements so middle-click, ctrl-click and "open in new tab" still work
// — hence the modifier check before taking the event over. Capture on window, like the period
// buttons, so Splade's global page loader never sees it.
window.addEventListener('click', event => {
    const card = event.target.closest('.lm-kpi-link');
    if (!card) return;
    if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    // updateMetrics: true — the totals themselves do not change (they are period-scoped and
    // ignore the stage), but replacing the block is what moves the selected highlight.
    loadLeadResults(new URL(card.href, location.origin), true).catch(showLoadError);
}, true);

// Everything the advanced filter owns. Anything else on the URL -- the period, the custom date
// range, the lead being viewed -- belongs to another control and has to survive untouched.
const FILTER_KEYS = ['search', 'lead_status', 'country', 'source', 'created_by', 'manager_id',
    'ftd', 'kyc', 'comment_from', 'comment_to', 'sort', 'direction', 'per_page', 'status'];

const filterUrl = form => {
    const url = new URL(location.href);
    url.searchParams.delete('page');                 // a narrower set may not have this page
    FILTER_KEYS.forEach(key => {
        url.searchParams.delete(key);
        url.searchParams.delete(`${key}[]`);         // multi-selects post as country[]
    });
    const data = new FormData(form);
    data.forEach((value, key) => {
        if (String(value).trim() === '') return;
        key.endsWith('[]') ? url.searchParams.append(key, value) : url.searchParams.set(key, value);
    });
    return url;
};
// Apply lead filters without handing the GET form to Splade's full-page loader.
window.addEventListener('submit', event => {
    const form = event.target.closest('[data-lm-filter-form]');
    if (!form) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    loadLeadResults(filterUrl(form), false).catch(showLoadError);
}, true);

// Live filtering. The advanced filter has no submit button by design: changing any control
// re-queries straight away.
//
// keepHeader keeps the search box and the open panel exactly as they are. updateMetrics is on
// only for date changes, because the KPI cards are deliberately computed WITHOUT the list
// filters (see index()) but the period is the outer window they are counted over — so a date
// change is the one thing here that genuinely moves those numbers.
//
// History is replaced, never pushed: a push per tick would bury the previous page.
let liveFilterTimer = null;
let liveFilterRequest = null;
const runLiveFilter = (form, { metrics = false, delay = 220 } = {}) => {
    clearTimeout(liveFilterTimer);
    liveFilterTimer = setTimeout(() => {
        liveFilterRequest?.abort();
        liveFilterRequest = new AbortController();

        const url = filterUrl(form);
        url.searchParams.delete('page'); // a narrower set may not have the page you were on
        const busy = form.querySelector('[data-lm-filter-busy]');
        if (busy) busy.textContent = 'Filtering…';

        loadLeadResults(url, metrics, false, { keepHeader: true, signal: liveFilterRequest.signal })
            .then(() => {
                history.replaceState({ leadmaxPeriod: true }, '', url);
                if (busy) busy.textContent = '';
            })
            .catch(error => {
                if (error?.name === 'AbortError') return;   // superseded by a later change
                if (busy) busy.textContent = '';
                showLoadError(error);
            });
    }, delay);
};

document.addEventListener('change', event => {
    const form = event.target.closest('[data-lm-filter-form]')
        || (event.target.matches?.('[data-lm-filter-bound]') ? event.target.form : null);
    if (!form) return;
    // The search box re-queries on input, and would otherwise fire a second identical request
    // when it loses focus.
    if (event.target.matches('input[name="search"]')) return;
    // Always recount: the cards sit under the filter and report the filtered set, so every
    // control here moves them, not just the dates that move the reporting window.
    runLiveFilter(form, { metrics: true });
});
// Typing a date by hand fires input long before it is a usable date, so wait longer here than
// for a checkbox and let change handle the picker.
document.addEventListener('input', event => {
    const input = event.target.closest('[data-lm-filter-date]');
    if (!input) return;
    runLiveFilter(input.closest('[data-lm-filter-form]'), { metrics: true, delay: 600 });
});

// Column sorting. Same in-place swap as everything else on this page, so sorting does not
// reload or lose the search box. keepHeader is safe here and wanted: the new <thead> arrives
// with the table, carrying the updated arrow state.
window.addEventListener('click', event => {
    const link = event.target.closest('[data-lm-sort]');
    if (!link) return;
    if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    loadLeadResults(new URL(link.href, location.origin), false, true, { keepHeader: true }).catch(showLoadError);
}, true);

// Live search: filter the table as the term is typed, across name, phone, email and lead ID.
//
// keepHeader is what makes this possible — the search box lives inside the list header, so a
// normal swap would replace the input the user is typing into and drop focus every keystroke.
//
// History is replaced rather than pushed: pushing per keystroke would bury the previous page
// under dozens of entries and make Back useless.
let liveSearchTimer = null;
let liveSearchRequest = null;
document.addEventListener('input', event => {
    const input = event.target.closest('input[data-lm-filter-input]');
    if (!input?.form) return;

    clearTimeout(liveSearchTimer);
    liveSearchTimer = setTimeout(() => {
        liveSearchRequest?.abort();
        liveSearchRequest = new AbortController();

        const url = filterUrl(input.form);
        url.searchParams.delete('page'); // a narrower result set may not have the page you were on

        // Search is one of the list filters, so the cards count the matches too.
        loadLeadResults(url, true, false, { keepHeader: true, signal: liveSearchRequest.signal })
            .then(() => history.replaceState({ leadmaxPeriod: true }, '', url))
            .catch(error => { if (error?.name !== 'AbortError') showLoadError(error); });
    }, 250);
});
window.addEventListener('click', event => {
    const reset = event.target.closest('[data-lm-filter-reset]');
    if (!reset || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    const form = reset.closest('[data-lm-filter-form]');
    // Clear what is on screen as well as what is sent. The panel sits outside the swapped
    // region, so nothing re-renders it — left alone it would keep showing the old selection
    // against unfiltered results.
    form.querySelectorAll('select').forEach(select => { select.selectedIndex = 0; });
    form.querySelectorAll('input[type="date"]').forEach(input => { input.value = ''; });
    clearMultiSelects(form);
    clearTimeout(liveFilterTimer);           // do not let a pending tick re-apply what we cleared
    liveFilterRequest?.abort();
    loadLeadResults(new URL(reset.href), true, false, { keepHeader: true })
        .then(() => history.replaceState({ leadmaxPeriod: true }, '', reset.href))
        .catch(showLoadError);
}, true);
// Leads list paging swaps the table in place, the same way the period and filter controls do.
window.addEventListener('click', event => {
    const link = event.target.closest('[data-lm-leads] a.lm-page[href]');
    if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    const url = new URL(link.href);
    if (url.origin !== location.origin) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    loadLeadResults(url, false, true, { keepHeader: true }).catch(() => location.assign(url));
}, true);
// Back and forward across period, filter and page changes re-render the table rather than leaving
// the address bar pointing at a page the user cannot see. Restoring must not push its own entry.
window.addEventListener('popstate', () => {
    if (!document.querySelector('[data-lm-leads]')) return;
    loadLeadResults(location.href, true, false).catch(() => location.reload());
});
// Detail action buttons are navigation-shaped buttons for consistent menu styling.
// Capture modal destinations before the shared navigation handler starts a full page load.
window.addEventListener('click', event => {
    const item = event.target.closest('[data-lm-lead-detail] [data-lm-href]');
    if (!item || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    const url = new URL(item.dataset.lmHref, location.href);
    if (url.origin !== location.origin || url.pathname !== location.pathname || !url.searchParams.has('dialog')) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    dialogTrigger = item;
    closeMenus();
    loadLeadState(url).catch(showLoadError);
}, true);
// Modal links fetch their state without replacing the document or changing the list/detail URL.
document.addEventListener('click', event => {
    const link = event.target.closest('[data-lm-leads] a[href], [data-lm-lead-detail] a[href]');
    if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    const url = new URL(link.href);
    if (url.origin !== location.origin || url.pathname !== location.pathname) return;
    if (!url.searchParams.has('lead') && !url.searchParams.has('dialog')) {
        // The KYC request modal closes in place, so the history keeps its filters, page and focus.
        if (link.matches('[data-lm-dialog-close], .lm-close') && link.closest('[data-lm-dialog]')?.querySelector('[data-lm-kyc-request-modal]')) {
            event.preventDefault();
            event.stopPropagation();
            closeDialog();
            return;
        }
        // Detail tabs, the action-menu entries that jump to one, and the pagers inside a tab (tp)
        // swap the panel in place rather than reloading the document. A tab pager on the default
        // tab carries only tp, so both keys count. Falls back to a normal load if the fetch fails.
        if ((url.searchParams.has('tab') || url.searchParams.has('tp')) && link.closest('[data-lm-lead-detail]')) {
            event.preventDefault();
            event.stopPropagation();
            closeMenus();
            history.pushState({ lmLeadTab: true }, '', url);
            loadLeadState(url, true)
                .then(() => { if (url.hash) document.querySelector(url.hash)?.scrollIntoView(); })
                .catch(() => location.assign(url));
            return;
        }
        if (link.closest('[data-lm-dialog]')) { event.preventDefault(); event.stopPropagation(); closeDialog(); }
        return;
    }
    event.preventDefault();
    event.stopPropagation();
    if (!link.closest('[data-lm-dialog]')) dialogTrigger = link;
    closeMenus();
    loadLeadState(url).catch(showLoadError);
}, true);
// Back and forward between tabs re-render the panel, so the view matches the address bar.
window.addEventListener('popstate', () => {
    const tab = document.querySelector('[data-lm-lead-detail] .lm-detail-tabs a[href]');
    if (!tab || new URL(tab.href).pathname !== location.pathname) return;
    loadLeadState(location.href, true).catch(() => location.reload());
});
const focusDialog = () => {
    const dialog = document.querySelector('[data-lm-dialog]');
    document.body.classList.toggle('lm-dialog-open', !!dialog);
    if (dialog && dialog !== lastDialog) { lastDialog = dialog; dialog.focus(); }
};
new MutationObserver(focusDialog).observe(document.body, { childList: true, subtree: true });
focusDialog();
const closeMenus = () => document.querySelectorAll('.lm-row-menu').forEach(menu => { menu.hidden = true; document.querySelector(`[data-lm-row-menu="${menu.id}"]`)?.setAttribute('aria-expanded', 'false'); });
// Confirm before downloading, stating the exact number of leads. Tenant admins could not tell
// whether a click exported their selection or the whole filtered set, so the dialog says which.
// Kept out of the delegated click listener so that listener stays synchronous.
/**
 * Export URL for whatever is on screen right now.
 *
 * Built from the address bar rather than from the link's rendered href. The export control sits
 * outside the region the live filter swaps, so its server-rendered href is whatever the filters
 * were when the page last loaded in full. Trusting it would mean the dialog stating one count
 * and the CSV containing a different set of leads.
 */
const exportUrl = (link) => {
    const url = new URL(link.href, location.origin);
    url.search = '';
    new URL(location.href).searchParams.forEach((value, key) => {
        if (['lead', 'tab', 'dialog', 'page'].includes(key)) return;   // view state, not filters
        url.searchParams.append(key, value);
    });
    return url;
};

const runExport = async (link) => {
    const ids = selectedLeadIds();
    const ok = await confirmExport({
        selected: ids.length,
        total: filteredLeadTotal(),
        destination: 'download',
    });
    if (!ok) return;
    const url = exportUrl(link);
    ids.forEach((id) => url.searchParams.append('ids[]', id));
    location.assign(url);
};
document.addEventListener('click', event => {
    if(event.target.closest('[data-lm-toast-close]'))event.target.closest('.lm-owner-toast').remove();
    const toggle = event.target.closest('[data-lm-row-menu]');
    if (toggle) {
        const menu = document.getElementById(toggle.dataset.lmRowMenu), open = menu.hidden;
        closeMenus();
        if (open) { const box = toggle.getBoundingClientRect(); menu.hidden = false; menu.style.left = `${Math.max(8, Math.min(box.right - 248, innerWidth - 256))}px`; menu.style.top = `${Math.min(box.bottom, innerHeight - menu.offsetHeight - 8)}px`; toggle.setAttribute('aria-expanded','true'); }
    } else if (!event.target.closest('.lm-row-menu')) closeMenus();
    if (event.target.closest('[data-lm-filters]')) { const button = event.target.closest('[data-lm-filters]'); const panel = document.querySelector('[data-lm-filter-panel]'); panel.hidden = !panel.hidden; button.setAttribute('aria-expanded', String(!panel.hidden)); }
    if (event.target.matches('.lm-modal-backdrop')) closeDialog();
    if (event.target.closest('[data-lm-all-members]')) { document.querySelector('.lm-members').style.maxHeight = 'none'; const input=document.querySelector('[data-lm-member-search]'); input.value=''; input.dispatchEvent(new Event('input',{bubbles:true})); }
    const exportLink=event.target.closest('[data-lm-export-selected]');
    if (exportLink) { event.preventDefault(); runExport(exportLink); }
});
document.addEventListener('input',event=>{if(event.target.matches('[data-lm-member-search]'))document.querySelectorAll('[data-lm-member]').forEach(x=>x.hidden=!x.dataset.lmMember.includes(event.target.value.toLowerCase()));});
document.addEventListener('change', event => {
    if (event.target.matches?.('[data-lm-status-select]')) setStatusTone(event.target);
    const inline = event.target.closest('[data-lm-inline-edit]');
    if (inline) {
        const previous = inline.dataset.lmPrevious ?? inline.defaultValue;
        setStatusTone(inline);
        inline.disabled = true;
        inline.classList.add('is-saving');
        const body = new FormData();
        body.append(inline.dataset.lmInlineEdit, inline.value);
        fetch(inline.dataset.lmEndpoint, { method: 'POST', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }, body })
            .then(async response => { const data = await response.json(); if (!response.ok) throw new Error(Object.values(data.errors ?? {}).flat().join(' ') || data.message || 'Unable to update this lead.'); inline.dataset.lmPrevious = inline.value; inline.classList.add('is-saved'); setTimeout(() => inline.classList.remove('is-saved'), 1200); })
            .catch(error => { inline.value = previous; setStatusTone(inline); showLoadError(error); })
            .finally(() => { inline.disabled = false; inline.classList.remove('is-saving'); });
    }
    if(event.target.matches('[data-lm-select-all]'))document.querySelectorAll('[data-lm-select]').forEach(x=>x.checked=event.target.checked);
    if(event.target.matches('[data-lm-activity-filter]'))document.querySelectorAll('[data-lm-activity]').forEach(x=>x.hidden=!!event.target.value&&x.dataset.lmActivity!==event.target.value);
    if(event.target.matches('input[name=attachment]'))event.target.closest('label').querySelector('[data-lm-file-label]').textContent=event.target.files[0]?.name || 'Attach file';
});
document.addEventListener('keydown',event=>{
    const dialog=document.querySelector('[data-lm-dialog]');
    // Escape closes an open menu first (a KYC row menu, Bulk Actions, a dropdown in a dialog), then the dialog.
    const openMenu=event.key==='Escape'&&document.querySelector('.lm-kyc-row-menu[open], .lm-kyc-bulk[open], [data-lm-dialog] details[open]');
    if(openMenu){event.preventDefault();openMenu.removeAttribute('open');openMenu.querySelector('summary')?.focus();return;}
    if(event.key==='Escape'){closeMenus();if(dialog)closeDialog();}
    if(event.key==='Tab'&&dialog){const nodes=[...dialog.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea,summary,[tabindex="0"]')].filter(x=>x.getClientRects().length);if(!nodes.length)return;const first=nodes[0],last=nodes.at(-1);if(event.shiftKey&&(document.activeElement===first||document.activeElement===dialog)){event.preventDefault();last.focus();}else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}}
});
document.addEventListener('submit',async event=>{
    const form=event.target.closest('[data-lm-submit]');if(!form)return;event.preventDefault();
    const buttons=[...form.querySelectorAll('button[type=submit],button:not([type])')],error=form.querySelector('.lm-form-error');
    let saved = false;
    // data-lm-busy: the button's in-flight label ("Updating…"), restored if the save fails.
    buttons.forEach(x=>{x.disabled=true;if(x.dataset.lmBusy){x.dataset.lmIdle=x.textContent;x.textContent=x.dataset.lmBusy;}});error.hidden=true;
    try{const response=await fetch(form.getAttribute('action'),{method:'POST',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},body:new FormData(form)});const data=await response.json();if(!response.ok)throw new Error(Object.values(data.errors??{}).flat().join(' ')||data.message||'Unable to save.');saved = true;const destination=new URL(form.dataset.lmSuccess);if(data.id)destination.searchParams.set('lead',data.id);if(!leadPage()){location.assign(destination);return;}await loadLeadState(destination, true);}catch(e){error.textContent=saved?'Saved successfully, but the view could not refresh. Close this dialog and refresh the Leads page.':e.message;error.hidden=false;if(!saved)buttons.forEach(x=>{x.disabled=false;if(x.dataset.lmIdle)x.textContent=x.dataset.lmIdle;});}
});

document.addEventListener('input',event=>{if(event.target.matches('[data-lm-status-note]'))event.target.parentElement.querySelector('[data-lm-note-count]').textContent=event.target.value.length+'/500';});


// Bulk actions: the ticked rows, or (after "Select all N matching leads") every lead the current filters match.
const bulkBar = el => el.closest('[data-lm-leads]')?.querySelector('[data-lm-bulk-actions]');
const bulkResult = (page, text, isError = false) => {
    const box = page.querySelector('[data-lm-bulk-result]');
    if (!box) return;
    box.textContent = text;
    box.classList.toggle('is-error', isError);
    box.hidden = !text;
};
const setMatchingMode = (page, on) => {
    const bar = page.querySelector('[data-lm-bulk-actions]');
    if (!bar) return;
    page.dataset.lmBulkAll = on ? '1' : '';
    const button = bar.querySelector('[data-lm-select-matching]');
    const count = bar.querySelector('[data-lm-selected-count]');
    const rows = page.querySelectorAll('[data-lm-select]').length;
    const allTicked = rows > 0 && page.querySelectorAll('[data-lm-select]:checked').length === rows;
    const total = Number(bar.dataset.lmTotal || 0);
    if (button) button.hidden = on || !allTicked || total <= rows;
    const scope = bar.dataset.lmBulkFiltered === '1' ? 'filtered ' : '';
    if (on && count) count.textContent = ! page.querySelector('[data-lm-select]:checked')
        ? `All ${total.toLocaleString()} ${scope}${total === 1 ? 'lead' : 'leads'}: choose an action, or tick rows to act on just those`
        : `All ${total.toLocaleString()} matching leads selected`;
};
// With a filter or search active the bar stays up and acts on every filtered lead until rows are ticked.
const restoreFilteredMode = page => {
    const bar = page.querySelector('[data-lm-bulk-actions]');
    if (!bar) return;
    const ticked = page.querySelectorAll('[data-lm-select]:checked').length;
    if (!ticked && bar.dataset.lmBulkFiltered === '1') { bar.hidden = false; setMatchingMode(page, true); } else setMatchingMode(page, false);
};
// "Bulk action" opens the bar without waiting for a selection: with no rows ticked the action
// applies to every lead the list is currently showing, which runBulk already supports via
// matching mode -- it posts the list's own filters alongside all=1.
window.addEventListener('click', event => {
    const button = event.target.closest('[data-lm-bulk-open]');
    if (!button) return;
    event.preventDefault();
    const page = button.closest('[data-lm-leads]');
    const bar = page?.querySelector('[data-lm-bulk-actions]');
    if (!bar) return;

    // The same button closes it again. Deliberately not restoreFilteredMode(): that reopens
    // the bar whenever the list is filtered and nothing is ticked, which is exactly the state
    // you are in when you click this to dismiss it.
    if (!bar.hidden) {
        bar.hidden = true;
        setMatchingMode(page, false);
        bulkResult(page, '');
        button.setAttribute('aria-expanded', 'false');
        button.focus();
        return;
    }

    bar.hidden = false;
    setMatchingMode(page, true);
    bulkResult(page, '');
    button.setAttribute('aria-expanded', 'true');
    bar.querySelector('select, button')?.focus();
}, true);

const runBulk = async (page, fields, question) => {
    const bar = page.querySelector('[data-lm-bulk-actions]');
    const all = page.dataset.lmBulkAll === '1';
    const ids = [...page.querySelectorAll('[data-lm-select]:checked')].map(box => box.value);
    const count = all ? Number(bar.dataset.lmTotal || 0) : ids.length;
    if (!count) return false;
    if (question && !confirm(question.replace('{n}', count.toLocaleString() + (count === 1 ? ' lead' : ' leads')))) return false;
    const body = new FormData();
    Object.entries(fields).forEach(([key, value]) => body.append(key, value));
    if (all) {
        body.append('all', '1');
        // The list's own filters, unchanged: the new value travels as `value`, so nothing here collides with them.
        new URLSearchParams(location.search).forEach((value, key) => { if (!['page', 'lead', 'dialog', 'tab', 'action', 'value', 'password', 'all', 'ids[]'].includes(key)) body.append(key, value); });
    } else ids.forEach(id => body.append('ids[]', id));
    bulkResult(page, 'Working…');
    try {
        const response = await fetch(bar.dataset.lmBulkUrl, { method: 'POST', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }, body });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(Object.values(data.errors ?? {}).flat().join(' ') || data.message || 'Could not apply the change.');
        await loadLeadState(location.href, true);
        bulkResult(document.querySelector('[data-lm-leads]'), data.message);
        return true;
    } catch (error) {
        bulkResult(page, error.message, true);
        return false;
    }
};
document.addEventListener('change', event => {
    const page = event.target.closest('[data-lm-leads]');
    if (!page) return;
    if (event.target.matches('[data-lm-select], [data-lm-select-all]')) { restoreFilteredMode(page); bulkResult(page, ''); }
    const select = event.target.closest('[data-lm-bulk-set]');
    if (!select || !select.value) return;
    const label = select.selectedOptions[0]?.textContent.trim();
    const question = { assign: `Assign {n} to ${label}?`, lead_status: `Set the lead status of {n} to "${label}"?`, stage: `Set the stage of {n} to "${label}"?` }[select.dataset.lmBulkSet];
    runBulk(page, { action: select.dataset.lmBulkSet, value: select.value }, question).finally(() => { if (select.isConnected) select.value = ''; });
});
document.addEventListener('click', event => {
    const page = event.target.closest('[data-lm-leads]');
    if (!page) return;
    if (event.target.closest('[data-lm-select-matching]')) setMatchingMode(page, true);
    if (event.target.closest('[data-lm-bulk-deselect]')) { restoreFilteredMode(page); page.querySelector('[data-lm-bulk-delete-form]')?.setAttribute('hidden', ''); }
    if (event.target.closest('[data-lm-bulk-delete]')) { const form = page.querySelector('[data-lm-bulk-delete-form]'); form.hidden = false; form.elements.password.focus(); }
    if (event.target.closest('[data-lm-bulk-delete-cancel]')) page.querySelector('[data-lm-bulk-delete-form]').hidden = true;
});
document.addEventListener('submit', event => {
    const form = event.target.closest('[data-lm-bulk-delete-form]');
    if (!form) return;
    event.preventDefault();
    runBulk(form.closest('[data-lm-leads]'), { action: 'delete', password: form.elements.password.value }, 'Delete {n}? This cannot be undone.');
});

// Adjust Balance dialog: show the balance the signed amount would leave, before applying it.
document.addEventListener('input', (event) => {
    const input = event.target.closest('[data-lm-adjust-amount]');
    if (!input) return;
    const box = input.closest('[data-lm-adjust]');
    const preview = box?.querySelector('[data-lm-adjust-preview]');
    if (!preview) return;
    const value = input.value.trim();
    if (!/^[+-]?\d{1,9}(\.\d{1,2})?$/.test(value) || Number(value) === 0) { preview.textContent = '—'; return; }
    const next = Math.round((Number(box.dataset.balance) + Number(value)) * 100) / 100;
    preview.textContent = next < 0 ? 'Not allowed: the balance would go below zero' : next.toLocaleString(undefined, { style: 'currency', currency: 'USD' });
});

/* Add KYC wizard (NC-13/14/15). Each Continue saves that step into the lead's draft on the
   server and only then moves on; the Address step saves and submits. Review is the receipt of
   what the server accepted, never a page shown before it did. */
const kycWizardSteps=['personal','identity','address','review'];
// The step that submits: Address. One KYC needs both an ID and a proof of address.
const kycLastStep=(form,step)=>step==='address';
const kycShowStep=(form,step)=>{
    form.dataset.step=step;
    form.querySelectorAll('[data-lm-kyc-step]').forEach(panel=>{panel.hidden=panel.dataset.lmKycStep!==step;});
    form.querySelectorAll('[data-lm-kyc-stage]').forEach(stage=>stage.dataset.lmKycStage===step?stage.setAttribute('aria-current','step'):stage.removeAttribute('aria-current'));
    form.querySelector('[data-lm-kyc-back]').hidden=step==='personal'||step==='review';
    form.querySelector('[data-lm-kyc-next]').textContent=kycLastStep(form,step)?'Submit KYC':'Continue';
    form.querySelector(`[data-lm-kyc-step="${step}"]`)?.querySelector('input:not([type=hidden]),select')?.focus();
};
const kycProgress=(form,value)=>{form.querySelector('[data-lm-kyc-progress]').value=value;form.querySelector('[data-lm-kyc-progress-label]').textContent=value+'% of the process complete';};
// XHR rather than fetch so a step carrying files can report upload progress.
const kycPost=(url,body,onProgress)=>new Promise((resolve,reject)=>{
    const xhr=new XMLHttpRequest();
    xhr.open('POST',url);
    xhr.setRequestHeader('Accept','application/json');xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
    if(onProgress)xhr.upload.addEventListener('progress',e=>{if(e.lengthComputable)onProgress(Math.round(e.loaded/e.total*100));});
    xhr.addEventListener('load',()=>{
        let data={};try{data=JSON.parse(xhr.responseText);}catch{/* An empty or HTML body falls through to the generic message. */}
        if(xhr.status<200||xhr.status>=300){const error=new Error(Object.values(data.errors??{}).flat().join(' ')||data.message||'Unable to save.');error.fields=Object.keys(data.errors??{});reject(error);return;}
        resolve(data);
    });
    xhr.addEventListener('error',()=>reject(new Error('Unable to save. Check the connection and try again.')));
    xhr.send(body);
});
// What was picked for an upload slot, before it is saved: name, type, size, a thumbnail for an
// image, and Remove to take it back out (the upload contract's preview and Replace/Remove).
const kycFilePreview=input=>{
    const box=input.parentElement,label=box.querySelector('[data-lm-file-name]'),file=input.files[0];
    box.querySelector('[data-lm-kyc-preview]')?.remove();
    label.dataset.empty??=label.textContent;
    if(!file){label.textContent=label.dataset.empty;return;}
    label.textContent=`${file.name} · ${file.type==='application/pdf'?'PDF':(file.type.split('/')[1]||'file').toUpperCase()} · ${Math.ceil(file.size/1024)} KB`;
    const preview=document.createElement('span');preview.className='lm-kyc-file-preview';preview.dataset.lmKycPreview='';
    if(file.type.startsWith('image/')){const img=new Image();img.alt='Preview of '+file.name;img.src=URL.createObjectURL(file);img.addEventListener('load',()=>URL.revokeObjectURL(img.src),{once:true});preview.append(img);}
    else{const chip=document.createElement('span');chip.className='lm-badge';chip.textContent='PDF';preview.append(chip);}
    const remove=document.createElement('button');remove.type='button';remove.className='lm-button';remove.textContent='Remove';remove.dataset.lmKycRemove='';remove.setAttribute('aria-label','Remove '+file.name);
    preview.append(remove);box.append(preview);
};
document.addEventListener('click',async event=>{
    const refresh=event.target.closest('[data-lm-kyc-refresh]');
    if(refresh){event.preventDefault();await loadLeadState(new URL(refresh.href),true);return;}
    const remove=event.target.closest('[data-lm-kyc-remove]');
    if(remove){event.preventDefault();const input=remove.closest('.lm-kyc-drop').querySelector('input[type=file]');input.value='';kycFilePreview(input);input.focus();return;}
    const next=event.target.closest('[data-lm-kyc-next]'),back=event.target.closest('[data-lm-kyc-back]');
    const form=(next||back)?.closest('[data-lm-kyc-wizard]');if(!form)return;
    event.preventDefault();
    const step=form.dataset.step,error=form.querySelector('.lm-form-error');
    error.hidden=true;form.querySelectorAll('[aria-invalid]').forEach(field=>field.removeAttribute('aria-invalid'));
    if(back){kycShowStep(form,kycWizardSteps[Math.max(0,kycWizardSteps.indexOf(step)-1)]);return;}
    const token=form.querySelector('[name=_token]').value,body=new FormData();
    body.append('_token',token);body.append('step',step);
    form.querySelector(`[data-lm-kyc-step="${step}"]`).querySelectorAll('input,select,textarea').forEach(field=>{
        if(!field.name||field.disabled)return;
        if(field.type==='file'){if(field.files[0])body.append(field.name,field.files[0]);return;}
        if((field.type==='radio'||field.type==='checkbox')&&!field.checked)return;
        body.append(field.name,field.value);
    });
    const last=kycLastStep(form,step);
    next.disabled=true;next.textContent=last?'Submitting…':'Saving…';
    try{
        const uploading=[...body.values()].some(value=>value instanceof File);
        const saved=await kycPost(form.getAttribute('action'),body,uploading?percent=>{next.textContent=`Uploading ${percent}%…`;}:null);
        form.dataset.version=saved.version;delete form.dataset.dirty;
        if(!last){kycProgress(form,saved.progress);kycShowStep(form,kycWizardSteps[kycWizardSteps.indexOf(step)+1]);return;}
        const submit=new FormData();submit.append('_token',token);submit.append('version',form.dataset.version);
        const done=await kycPost(form.dataset.submitUrl,submit);
        kycProgress(form,100);
        form.querySelector('[data-lm-kyc-receipt-state]').textContent=done.label;
        form.querySelector('[data-lm-kyc-receipt-label]').textContent=done.label;
        form.querySelector('[data-lm-kyc-receipt-by]').textContent=done.submitted_by;
        form.querySelector('[data-lm-kyc-receipt-at]').textContent=done.submitted_at;
        form.querySelector('[data-lm-kyc-receipt-email]').hidden=!done.emailed;
        if(done.document)form.querySelector('[data-lm-kyc-view]').href=form.dataset.documentUrl+done.document;
        form.querySelector('[data-lm-kyc-actions]').hidden=true;form.querySelector('[data-lm-kyc-done]').hidden=false;
        kycShowStep(form,'review');
    }catch(e){
        error.textContent=e.message;error.hidden=false;
        // Take the user to the step that holds the first field the server rejected.
        (e.fields||[]).forEach(name=>form.querySelector(`[name="${name}"]`)?.setAttribute('aria-invalid','true'));
        const owner=form.querySelector('[aria-invalid]')?.closest('[data-lm-kyc-step]');
        if(owner&&owner.dataset.lmKycStep!==form.dataset.step)kycShowStep(form,owner.dataset.lmKycStep);
        form.querySelector('[aria-invalid]')?.focus();
    }finally{
        if(!next.closest('[hidden]')){next.disabled=false;next.textContent=kycLastStep(form,form.dataset.step)?'Submit KYC':'Continue';}
    }
});
document.addEventListener('input',event=>{const wizard=event.target.closest('[data-lm-kyc-wizard]');if(wizard)wizard.dataset.dirty='1';});
document.addEventListener('change',event=>{
    const wizard=event.target.closest('[data-lm-kyc-wizard]');if(wizard)wizard.dataset.dirty='1';
    // A declared single legal name has no last name: the field is disabled, so it is not sent.
    const single=event.target.closest('[data-lm-kyc-single]');
    if(single){const last=single.form.querySelector('[name=last_name]');last.disabled=single.checked;last.required=!single.checked;return;}
    // "Other" asks for the document's name; another type drops what was typed.
    const idType=event.target.closest('[data-lm-kyc-id-type]');
    if(idType){const name=idType.form.querySelector('[data-lm-kyc-id-other]'),input=name.querySelector('input');name.hidden=idType.value!=='other';input.required=!name.hidden;if(name.hidden)input.value='';return;}
    const type=event.target.closest('[data-lm-kyc-doc-type]');
    if(type){const other=type.form.querySelector('[data-lm-kyc-other]');other.hidden=type.value!=='others';if(other.hidden)other.querySelector('input').value='';return;}
    const file=event.target.closest('[data-lm-kyc-file-input]');
    if(file)kycFilePreview(file);
});

/* NC-23 KYC History. The toolbar filters on the server over the whole set: typing waits 300 ms,
   Enter and every other control apply at once, and only the cards, table and pager are swapped
   so the search box keeps its focus. A selection is kept per lead and filter set (sessionStorage),
   so it survives paging and is dropped when the filters change. */
let kycFilterTimer;
let kycFilterRequest;
const kycSelectionKey=results=>`lm-kyc-selection:${results.dataset.lmKycLead}:${results.dataset.lmKycFilterKey}`;
const kycSelection=results=>{if(!results)return new Set();try{return new Set(JSON.parse(sessionStorage.getItem(kycSelectionKey(results))||'[]'));}catch{return new Set();}};
const kycSyncSelection=()=>{
    const results=document.querySelector('[data-lm-kyc-results]');
    if(!results)return;
    const chosen=kycSelection(results),boxes=[...results.querySelectorAll('[data-lm-kyc-select]')];
    boxes.forEach(box=>{box.checked=chosen.has(box.value);box.closest('.lm-kyc-request-row')?.classList.toggle('is-selected',box.checked);});
    const all=results.querySelector('[data-lm-kyc-select-all]');
    if(all){const on=boxes.filter(box=>box.checked).length;all.checked=boxes.length>0&&on===boxes.length;all.indeterminate=on>0&&on<boxes.length;}
    // Only write what changed: this runs from a MutationObserver, and rewriting the same text
    // would be a mutation of its own, firing it again without end.
    document.querySelectorAll('[data-lm-kyc-selected-count]').forEach(count=>{const text=`${chosen.size} selected`;if(count.textContent!==text)count.textContent=text;});
    document.querySelectorAll('[data-lm-kyc-export-selected]').forEach(button=>{button.disabled=chosen.size===0;});
};
new MutationObserver(()=>{if(document.querySelector('[data-lm-kyc-results]'))kycSyncSelection();}).observe(document.body,{childList:true,subtree:true});
const kycApplyFilter=async form=>{
    clearTimeout(kycFilterTimer);
    const url=new URL(form.action);
    new FormData(form).forEach((value,key)=>{if(value!=='')url.searchParams.append(key,value);});
    const current=document.querySelector('[data-lm-kyc-results]');
    if(current)sessionStorage.removeItem(kycSelectionKey(current));
    kycFilterRequest?.abort();
    const controller=new AbortController();
    kycFilterRequest=controller;
    current?.classList.add('lm-kyc-results-busy');
    try{
        const response=await fetch(url,{headers:{'X-LeadMax-Fragment':'leads',Accept:'text/html'},signal:controller.signal});
        if(!response.ok||response.redirected)throw new Error('Unable to filter the KYC history.');
        const doc=new DOMParser().parseFromString(await response.text(),'text/html');
        const next=doc.querySelector('[data-lm-kyc-results]');
        if(!next)throw new Error('Unable to filter the KYC history.');
        document.querySelector('[data-lm-kyc-results]')?.replaceWith(next);
        // The fields stay as typed (and focused); only what follows from the result is refreshed.
        const fresh=doc.querySelector('[data-lm-kyc-filter]');
        const end=fresh?.querySelector('.lm-kyc-toolbar-end');
        if(end)form.querySelector('.lm-kyc-toolbar-end')?.replaceWith(end);
        form.querySelectorAll(':scope > .lm-form-error').forEach(old=>old.remove());
        const error=fresh?.querySelector(':scope > .lm-form-error');
        if(error)form.append(error);
        const to=form.querySelector('[name=kh_to]');
        if(error)to?.setAttribute('aria-invalid','true');else to?.removeAttribute('aria-invalid');
        history.pushState({lmLeadTab:true},'',url);
        kycSyncSelection();
    }catch(e){
        if(e.name!=='AbortError')location.assign(url);
    }finally{
        document.querySelector('[data-lm-kyc-results]')?.classList.remove('lm-kyc-results-busy');
    }
};
document.addEventListener('input',event=>{
    const search=event.target.closest('[data-lm-kyc-search]');
    if(!search)return;
    clearTimeout(kycFilterTimer);
    kycFilterTimer=setTimeout(()=>kycApplyFilter(search.form),300);
});
document.addEventListener('submit',event=>{
    const form=event.target.closest('[data-lm-kyc-filter]');
    if(!form)return;
    event.preventDefault();
    kycApplyFilter(form);
});
document.addEventListener('change',event=>{
    const form=event.target.closest('[data-lm-kyc-filter]');
    if(form&&!event.target.matches('[data-lm-kyc-search], [data-lm-ms-search]')){kycApplyFilter(form);return;}
    const box=event.target.closest('[data-lm-kyc-select]'),all=event.target.closest('[data-lm-kyc-select-all]');
    if(!box&&!all)return;
    const results=event.target.closest('[data-lm-kyc-results]'),chosen=kycSelection(results);
    // The header box selects the rows on this page only, never every record of the client.
    (all?[...results.querySelectorAll('[data-lm-kyc-select]')]:[box]).forEach(item=>{
        if(all)item.checked=all.checked;
        if(item.checked)chosen.add(item.value);else chosen.delete(item.value);
    });
    sessionStorage.setItem(kycSelectionKey(results),JSON.stringify([...chosen]));
    kycSyncSelection();
});
// Export downloads through fetch so a refused scope is reported, never saved as an empty file.
const kycDownload=async(url,origin)=>{
    const say=message=>{
        const toolbar=document.querySelector('[data-lm-kyc-filter]');
        if(!toolbar){window.alert(message);return;}
        toolbar.querySelector('[data-lm-kyc-export-error]')?.remove();
        const error=document.createElement('p');
        error.className='lm-form-error';error.setAttribute('role','alert');error.dataset.lmKycExportError='';error.textContent=message;
        toolbar.append(error);
    };
    origin?.setAttribute('aria-busy','true');
    try{
        const response=await fetch(url,{headers:{Accept:'text/csv'}});
        if(!response.ok){let message='The export could not be created.';try{message=(await response.json()).message||message;}catch{/* not JSON */}say(message);return;}
        const name=(response.headers.get('content-disposition')||'').match(/filename="?([^";]+)"?/)?.[1]||'kyc-history.csv';
        const link=document.createElement('a');
        link.href=URL.createObjectURL(await response.blob());link.download=name;
        document.body.append(link);link.click();link.remove();
        setTimeout(()=>URL.revokeObjectURL(link.href),1000);
    }catch{say('The export could not be downloaded. Check the connection and try again.');}
    finally{origin?.removeAttribute('aria-busy');}
};
document.addEventListener('click',async event=>{
    const exportLink=event.target.closest('[data-lm-kyc-export]'),exportSelected=event.target.closest('[data-lm-kyc-export-selected]');
    if(!exportLink&&!exportSelected)return;
    event.preventDefault();
    event.stopPropagation();
    const labels={title:'Export KYC history',nouns:['KYC request','KYC requests']};
    if(exportSelected){
        const ids=[...kycSelection(document.querySelector('[data-lm-kyc-results]'))];
        exportSelected.closest('details')?.removeAttribute('open');
        if(!ids.length||!await confirmExport({...labels,selected:ids.length}))return;
        const url=new URL(document.querySelector('[data-lm-kyc-filter] [data-lm-kyc-export]').href);
        ids.forEach(id=>url.searchParams.append('ids[]',id));
        await kycDownload(url,exportSelected);
        return;
    }
    const total=Number(exportLink.dataset.lmKycExportCount)||0;
    if(!total||!await confirmExport({...labels,total,scope:"in this lead's KYC history, with the current filters,"}))return;
    await kycDownload(exportLink.href,exportLink);
},true);
// A row or Bulk Actions menu closes when something else is clicked, or once a link in it is used.
document.addEventListener('click',event=>{
    document.querySelectorAll('.lm-kyc-row-menu[open], .lm-kyc-bulk[open]').forEach(menu=>{if(!menu.contains(event.target)||event.target.closest('a'))menu.removeAttribute('open');});
},true);
// The row menu is fixed to the viewport, so it is placed under its ⋯ when it opens (above it when
// there is no room below), kept on screen, and closed if the table or page scrolls away under it.
document.addEventListener('toggle',event=>{
    const menu=event.target;
    if(!menu.matches?.('.lm-kyc-row-menu')||!menu.open)return;
    document.querySelectorAll('.lm-kyc-row-menu[open]').forEach(other=>{if(other!==menu)other.removeAttribute('open');});
    const list=menu.querySelector('.lm-kyc-row-menu-list'),button=menu.querySelector('summary').getBoundingClientRect();
    const width=list.offsetWidth,height=list.offsetHeight;
    list.style.left=`${Math.max(8,Math.min(button.right-width,innerWidth-width-8))}px`;
    list.style.top=`${button.bottom+4+height>innerHeight-8?Math.max(8,button.top-height-4):button.bottom+4}px`;
},true);
document.addEventListener('scroll',event=>{
    if(event.target.closest?.('.lm-kyc-row-menu-list'))return;
    document.querySelectorAll('.lm-kyc-row-menu[open]').forEach(menu=>menu.removeAttribute('open'));
},true);

/* NC-23.4/23.5 KYC request modal. Tabs, document and side selection and the Reviewed marks
   change in place, so a reason typed for a denial survives them. Accept and Deny confirm
   inline, then post one request-level decision; the lead view and this modal are reloaded
   from the server, which shows the read-only receipt. */
const kycModalUrl=modal=>{
    const url=new URL(modal.dataset.selfUrl,location.origin);
    const tab=modal.querySelector('[data-lm-kyc-tab][aria-selected=true]')?.dataset.lmKycTab;
    const doc=modal.querySelector('[data-lm-kyc-doc][aria-pressed=true]')?.dataset.lmKycDoc;
    if(tab)url.searchParams.set('kh_tab',tab);
    if(doc)url.searchParams.set('doc',doc);
    return url;
};
const kycFeedbackKey=modal=>`lm-kyc-feedback:${modal.dataset.request}`;
// Reloading after a conflict keeps what the reviewer typed: it is put back once the modal is redrawn.
const kycKeepFeedback=modal=>{
    const reason=modal.querySelector('[data-lm-kyc-feedback=reason]')?.value||'',note=modal.querySelector('[data-lm-kyc-feedback=note]')?.value||'';
    if(reason||note)sessionStorage.setItem(kycFeedbackKey(modal),JSON.stringify({reason,note}));
};
// Opening a document in the read-only viewer leaves this request for a moment: what was typed
// and where the body was scrolled are kept, and put back when the viewer's Close returns here.
window.addEventListener('click',event=>{
    const link=event.target.closest('[data-lm-kyc-view-file]');
    const modal=link?.closest('[data-lm-kyc-request-modal]');
    if(!modal)return;
    kycKeepFeedback(modal);
    sessionStorage.setItem(`lm-kyc-scroll:${modal.dataset.request}`,String(modal.querySelector('.lm-kyc-request-body')?.scrollTop||0));
    delete modal.dataset.dirty;
},true);
new MutationObserver(()=>{
    const modal=document.querySelector('[data-lm-kyc-request-modal]:not([data-restored])');
    if(!modal)return;
    modal.dataset.restored='';
    kycFitTimeline(modal);
    const scrollKey=`lm-kyc-scroll:${modal.dataset.request}`,scroll=sessionStorage.getItem(scrollKey);
    if(scroll!==null){sessionStorage.removeItem(scrollKey);const body=modal.querySelector('.lm-kyc-request-body');if(body)body.scrollTop=Number(scroll)||0;}
    let kept;try{kept=JSON.parse(sessionStorage.getItem(kycFeedbackKey(modal))||'null');}catch{kept=null;}
    sessionStorage.removeItem(kycFeedbackKey(modal));
    const reason=modal.querySelector('[data-lm-kyc-feedback=reason]');
    if(!kept||!reason)return;
    reason.value=kept.reason;modal.querySelector('[data-lm-kyc-feedback=note]').value=kept.note;
    modal.querySelector('[data-lm-kyc-confirm="rejected"]').hidden=false;
    modal.dataset.dirty='1';
}).observe(document.body,{childList:true,subtree:true});
const kycShowTab=(modal,key,focus=false)=>{
    modal.querySelectorAll('[data-lm-kyc-tab]').forEach(tab=>{const on=tab.dataset.lmKycTab===key;tab.setAttribute('aria-selected',String(on));tab.tabIndex=on?0:-1;if(on&&focus)tab.focus();});
    modal.querySelectorAll('[data-lm-kyc-panel]').forEach(panel=>{panel.hidden=panel.dataset.lmKycPanel!==key;});
    if(key==='timeline')kycFitTimeline(modal);
};
// The modal does not scroll (user, 10 Oct 2026): Details shows one section at a time, chosen from its
// row of buttons, and the Timeline shows as many events as fit, newest first, with Newer and Older.
const kycShowSection=(modal,key,focus=false)=>{
    modal.querySelectorAll('[data-lm-kyc-section]').forEach(button=>{const on=button.dataset.lmKycSection===key;button.setAttribute('aria-pressed',String(on));if(on&&focus)button.focus();});
    modal.querySelectorAll('[data-lm-kyc-section-panel]').forEach(panel=>{panel.hidden=panel.dataset.lmKycSectionPanel!==key;});
};
const kycFitTimeline=(modal,step=0)=>{
    const panel=modal?.querySelector('[data-lm-kyc-panel="timeline"]');
    const list=panel?.querySelector('[data-lm-kyc-timeline]'),pager=panel?.querySelector('[data-lm-kyc-timeline-pager]');
    if(!panel||panel.hidden||!list||!pager)return;
    const items=[...list.children];
    // Measure every event at full size, then cut pages to the room the body really has.
    items.forEach(item=>{item.hidden=false;});
    pager.hidden=false;
    const body=panel.closest('.lm-kyc-request-body'),style=getComputedStyle(body);
    const room=body.clientHeight-parseFloat(style.paddingTop)-parseFloat(style.paddingBottom)-pager.offsetHeight-8;
    const pages=[];let start=0,used=0;
    items.forEach((item,index)=>{const h=item.offsetHeight;if(index>start&&used+h>room){pages.push([start,index]);start=index;used=0;}used+=h;});
    pages.push([start,items.length]);
    const current=Math.min(Math.max(0,Number(list.dataset.page||0)+step),pages.length-1);
    list.dataset.page=String(current);
    const [from,to]=pages[current];
    items.forEach((item,index)=>{item.hidden=index<from||index>=to;});
    pager.hidden=pages.length<2;
    pager.querySelector('[data-lm-kyc-timeline-range]').textContent=`Events ${from+1}–${to} of ${items.length}`;
    pager.querySelector('[data-lm-kyc-timeline-page="-1"]').disabled=current===0;
    pager.querySelector('[data-lm-kyc-timeline-page="1"]').disabled=current===pages.length-1;
};
document.addEventListener('click',event=>{
    const modal=event.target.closest('[data-lm-kyc-request-modal]');
    if(!modal)return;
    const section=event.target.closest('[data-lm-kyc-section]');
    if(section){kycShowSection(modal,section.dataset.lmKycSection);return;}
    const page=event.target.closest('[data-lm-kyc-timeline-page]');
    if(page)kycFitTimeline(modal,Number(page.dataset.lmKycTimelinePage));
});
window.addEventListener('resize',()=>{const modal=document.querySelector('[data-lm-kyc-request-modal]');if(modal)kycFitTimeline(modal);});
// Another document in the same request, from the card list or the compact selector (NC-24.1):
// the preview changes, the request never does, and the two controls agree.
const kycSelectDoc=(modal,id)=>{
    modal.querySelectorAll('[data-lm-kyc-doc]').forEach(item=>item.setAttribute('aria-pressed',String(item.dataset.lmKycDoc===id)));
    modal.querySelectorAll('[data-lm-kyc-docview]').forEach(view=>{view.hidden=view.dataset.lmKycDocview!==id;});
    const select=modal.querySelector('[data-lm-kyc-doc-select]');
    if(select&&select.value!==id)select.value=id;
};
document.addEventListener('change',event=>{
    const select=event.target.closest('[data-lm-kyc-doc-select]');
    if(select)kycSelectDoc(select.closest('[data-lm-kyc-request-modal]'),select.value);
});
const kycToken=modal=>modal.querySelector('[data-lm-kyc-token]')?.value||'';
const kycDecide=async(modal,decision)=>{
    const error=modal.querySelector('[data-lm-kyc-error]'),label=modal.querySelector(`[data-lm-kyc-submit="${decision}"]`);
    const buttons=[...modal.querySelectorAll('.lm-kyc-request-footer button')],wasDisabled=buttons.map(button=>button.disabled),idle=label.textContent;
    error.hidden=true;error.querySelector('button')?.remove();
    // Both decisions are held while one is in flight; the layout does not move.
    buttons.forEach(button=>{button.disabled=true;});
    label.textContent='Submitting…';
    const body=new FormData();
    body.append('_token',kycToken(modal));body.append('decision',decision);body.append('version',modal.dataset.version);
    body.append('idempotency_key',modal.dataset.key);
    if(decision==='rejected'){
        body.append('rejection_reason',modal.querySelector('[data-lm-kyc-feedback=reason]').value);
        body.append('notes',modal.querySelector('[data-lm-kyc-feedback=note]').value);
    }
    try{
        const response=await fetch(modal.dataset.decisionUrl,{method:'POST',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},body});
        const data=await response.json().catch(()=>({}));
        if(!response.ok){const failure=new Error(Object.values(data.errors??{}).flat().join(' ')||data.message||'The decision could not be saved. Nothing was changed.');failure.conflict=response.status===409;throw failure;}
        delete modal.dataset.dirty;
        await loadLeadState(kycModalUrl(modal),true);
    }catch(e){
        if(e.name==='AbortError')return;
        error.textContent=e.message;
        if(e.conflict){
            // The request or the client's level moved on: say so, keep the feedback, and offer a fresh look.
            const reload=document.createElement('button');
            reload.type='button';reload.className='lm-button';reload.textContent='Reload request';
            reload.addEventListener('click',()=>{kycKeepFeedback(modal);delete modal.dataset.dirty;loadLeadState(kycModalUrl(modal),true).catch(showLoadError);});
            error.append(' ',reload);
        }
        error.hidden=false;
        buttons.forEach((button,index)=>{button.disabled=wasDisabled[index];});
        label.textContent=idle;
    }
};
document.addEventListener('click',async event=>{
    const modal=event.target.closest('[data-lm-kyc-request-modal]');
    if(!modal)return;
    const tab=event.target.closest('[data-lm-kyc-tab]');
    if(tab){kycShowTab(modal,tab.dataset.lmKycTab);return;}
    const doc=event.target.closest('[data-lm-kyc-doc]');
    if(doc){kycSelectDoc(modal,doc.dataset.lmKycDoc);return;}
    const side=event.target.closest('[data-lm-kyc-side]');
    if(side){
        const view=side.closest('[data-lm-kyc-docview]');
        view.querySelectorAll('[data-lm-kyc-side]').forEach(button=>{const on=button===side;button.setAttribute('aria-pressed',String(on));button.classList.toggle('primary',on);});
        view.querySelectorAll('[data-lm-kyc-sideview]').forEach(figure=>{figure.hidden=figure.dataset.lmKycSideview!==side.dataset.lmKycSide;});
        return;
    }
    const ask=event.target.closest('[data-lm-kyc-ask]');
    if(ask){
        modal.querySelectorAll('[data-lm-kyc-confirm]').forEach(panel=>{panel.hidden=panel.dataset.lmKycConfirm!==ask.dataset.lmKycAsk;});
        const panel=modal.querySelector(`[data-lm-kyc-confirm="${ask.dataset.lmKycAsk}"]`);
        (panel.querySelector('textarea')||panel.querySelector('[data-lm-kyc-submit]'))?.focus();
        return;
    }
    const cancel=event.target.closest('[data-lm-kyc-cancel]');
    if(cancel){const panel=cancel.closest('[data-lm-kyc-confirm]');panel.hidden=true;modal.querySelector(`[data-lm-kyc-ask="${panel.dataset.lmKycConfirm}"]`)?.focus();return;}
    const submit=event.target.closest('[data-lm-kyc-submit]');
    if(submit)await kycDecide(modal,submit.dataset.lmKycSubmit);
});
// The read-only file viewer's zoom: Fit to width, or a percentage of the file's real size
// (the readout always says the actual scale). A PDF is zoomed by the browser's PDF viewer.
const kycViewerZoom=(view,zoom)=>{
    const img=view.querySelector('img[data-lm-kyc-zoom-target]'),pdf=view.querySelector('object[data-base]');
    const readout=view.querySelector('[data-lm-kyc-zoom-value]'),fit=view.querySelector('[data-lm-kyc-fit]'),stage=view.querySelector('[data-lm-kyc-stage-view]');
    const fitting=zoom===null;
    stage.dataset.fit=fitting?'1':'0';
    fit?.setAttribute('aria-pressed',String(fitting));
    if(img){
        if(fitting)img.style.width='';
        else img.style.width=`${Math.round(img.naturalWidth*zoom/100)}px`;
        const actual=img.naturalWidth?Math.round(img.getBoundingClientRect().width/img.naturalWidth*100):100;
        readout.textContent=`${fitting?actual:zoom}%`;
        view.dataset.zoom=String(fitting?actual:zoom);
    }else if(pdf){
        // The page stays where the reader is (NC-24.4); only the zoom changes.
        pdf.data=`${pdf.dataset.base}#page=${Number(view.dataset.page||1)}&${fitting?'view=FitH':`zoom=${zoom}`}`;
        readout.textContent=fitting?'Fit':`${zoom}%`;
        view.dataset.zoom=String(fitting?100:zoom);
    }
};
document.addEventListener('click',event=>{
    const view=event.target.closest('.lm-kyc-viewer-body');
    if(!view)return;
    if(event.target.closest('[data-lm-kyc-fit]')){kycViewerZoom(view,null);return;}
    const step=event.target.closest('[data-lm-kyc-zoom]');
    if(step){const now=Number(view.dataset.zoom||100);kycViewerZoom(view,Math.min(400,Math.max(25,Math.round((now+Number(step.dataset.lmKycZoom))/25)*25)));}
});
// Show the fitted image's real scale as soon as it has loaded.
document.addEventListener('load',event=>{
    if(event.target.matches?.('img[data-lm-kyc-zoom-target]'))kycViewerZoom(event.target.closest('.lm-kyc-viewer-body'),null);
},true);
// NC-24.4: real PDF pages (the zoom is kept), an in-dialog full view, and a retry when a file does not load.
document.addEventListener('click',event=>{
    const page=event.target.closest('[data-lm-kyc-page]');
    if(page){
        const view=page.closest('.lm-kyc-viewer-body'),group=page.closest('[data-lm-kyc-pages]'),total=Number(group.dataset.lmKycPages)||1;
        const next=Math.min(total,Math.max(1,Number(view.dataset.page||1)+Number(page.dataset.lmKycPage)));
        view.dataset.page=String(next);
        group.querySelector('[data-lm-kyc-page-value]').textContent=`Page ${next} of ${total}`;
        group.querySelector('[data-lm-kyc-page="-1"]').disabled=next===1;
        group.querySelector('[data-lm-kyc-page="1"]').disabled=next===total;
        const stage=view.querySelector('[data-lm-kyc-stage-view]');
        kycViewerZoom(view,stage?.dataset.fit==='1'?null:Number(view.dataset.zoom||100));
        return;
    }
    const expand=event.target.closest('[data-lm-kyc-expand]');
    if(expand){
        const dialog=expand.closest('.lm-kyc-viewer-dialog'),on=!dialog.classList.contains('is-expanded');
        dialog.classList.toggle('is-expanded',on);
        expand.setAttribute('aria-pressed',String(on));
        expand.setAttribute('aria-label',on?'Leave the full view':'Expand the file to the whole window');
        return;
    }
    const retry=event.target.closest('[data-lm-kyc-retry]');
    if(retry){
        const box=retry.closest('[data-lm-kyc-load-error]'),img=box.previousElementSibling;
        const url=new URL(img.dataset.src||img.src,location.href);
        url.searchParams.set('retry',String(Date.now()));
        box.remove();img.hidden=false;img.src=url.href;
    }
});
// A file that does not load says so, with Retry -- never a broken image or another file in its place.
document.addEventListener('error',event=>{
    const img=event.target;
    if(!img.matches?.('img.lm-kyc-file-frame, img.lm-kyc-preview-frame')||img.nextElementSibling?.matches('[data-lm-kyc-load-error]'))return;
    img.dataset.src??=img.src;
    img.hidden=true;
    const box=document.createElement('div');
    box.className='lm-kyc-load-error';box.setAttribute('role','alert');box.dataset.lmKycLoadError='';
    box.innerHTML='<p>The file could not be loaded. Nothing else is shown in its place.</p><button type="button" class="lm-button" data-lm-kyc-retry>Retry</button>';
    img.after(box);
},true);

// The tabs follow the ARIA tabs pattern: arrows, Home and End move between them.
document.addEventListener('keydown',event=>{
    const tab=event.target.closest?.('[data-lm-kyc-tab]');
    if(!tab||!['ArrowLeft','ArrowRight','Home','End'].includes(event.key))return;
    event.preventDefault();
    const tabs=[...tab.parentElement.querySelectorAll('[data-lm-kyc-tab]')],index=tabs.indexOf(tab);
    const next=event.key==='Home'?tabs[0]:event.key==='End'?tabs.at(-1):tabs[(index+(event.key==='ArrowRight'?1:-1)+tabs.length)%tabs.length];
    kycShowTab(tab.closest('[data-lm-kyc-request-modal]'),next.dataset.lmKycTab,true);
});
document.addEventListener('input',event=>{
    const field=event.target.closest('[data-lm-kyc-feedback]');
    if(!field)return;
    const modal=field.closest('[data-lm-kyc-request-modal]');
    if([...modal.querySelectorAll('[data-lm-kyc-feedback]')].some(item=>item.value.trim()))modal.dataset.dirty='1';else delete modal.dataset.dirty;
});
// "Reviewed" on one document version: saved at once, and Accept follows what the server says.
document.addEventListener('change',async event=>{
    const box=event.target.closest('[data-lm-kyc-ack]');
    if(!box)return;
    const modal=box.closest('[data-lm-kyc-request-modal]'),error=modal.querySelector('[data-lm-kyc-error]');
    const body=new FormData();
    body.append('_token',kycToken(modal));body.append('reviewed',box.checked?'1':'0');body.append('version',modal.dataset.version);
    box.disabled=true;error.hidden=true;
    try{
        const response=await fetch(box.dataset.url,{method:'POST',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},body});
        const data=await response.json().catch(()=>({}));
        if(!response.ok)throw new Error(data.message||'The review mark could not be saved.');
        modal.dataset.version=data.version;
        const chip=modal.querySelector(`[data-lm-kyc-ack-chip="${box.dataset.lmKycAck}"]`);
        if(chip){chip.textContent=data.reviewed?'Reviewed ✓':'Not reviewed';chip.classList.toggle('is-ok',data.reviewed);}
        const option=modal.querySelector(`[data-lm-kyc-doc-select] option[value="${box.dataset.lmKycAck}"]`);
        if(option)option.textContent=`${option.dataset.base} · ${data.reviewed?'Reviewed':'Not reviewed'}${option.dataset.warn}`;
        if(data.bundle)modal.querySelectorAll('[data-lm-kyc-bundle]').forEach(node=>{node.textContent=data.bundle;});
        const accept=modal.querySelector('[data-lm-kyc-ask="approved"]');
        if(accept)accept.disabled=!data.can_accept;
        const blocker=modal.querySelector('[data-lm-kyc-blocker]');
        if(blocker)blocker.textContent=data.blocker||'';
    }catch(e){
        box.checked=!box.checked;
        error.textContent=e.message;error.hidden=false;
    }finally{
        box.disabled=false;
    }
});
