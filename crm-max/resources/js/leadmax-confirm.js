// Export confirmation dialog.
//
// Shared by both export destinations (download and Google Drive), hence its own module rather
// than living in either one.
//
// Built in JS instead of server-rendered like the other lead dialogs, because the count it has
// to state depends on which rows are ticked — something only the browser knows. It reuses the
// existing .lm-modal-backdrop / .lm-dialog classes so it looks like every other dialog here.

/**
 * Ask the user to confirm an export, stating exactly how many leads it covers.
 *
 * @param {object} o
 * @param {number} o.selected    rows ticked in the table; 0 means "no selection"
 * @param {number} o.total       leads matching the current filters
 * @param {'download'|'drive'} o.destination
 * @returns {Promise<boolean>} resolves true to proceed, false if cancelled
 */
export function confirmExport({ selected = 0, total = 0, destination = 'download', title = 'Export leads', nouns = ['lead', 'leads'], scope = 'matching the current filters' } = {}) {
    const count = selected || total;
    const noun = count === 1 ? nouns[0] : nouns[1];

    return new Promise((resolve) => {
        const previouslyFocused = document.activeElement;

        const backdrop = document.createElement('div');
        // .lm-modal-backdrop for the shared overlay styling; .lm-export-backdrop to centre this
        // dialog, since the shared rule top-pads by 137px for the much taller lead dialogs.
        backdrop.className = 'lm-modal-backdrop lm-export-backdrop';
        backdrop.innerHTML = `
            <section role="dialog" aria-modal="true" aria-labelledby="lm-export-title" class="lm-dialog lm-export-dialog" tabindex="-1">
                <header class="lm-dialog-heading">
                    <div>
                        <h2 id="lm-export-title">Export leads</h2>
                        <p data-export-summary></p>
                    </div>
                </header>
                <div class="lm-export-dialog-body">
                    <p data-export-destination></p>
                    <p class="lm-export-hint" data-export-hint hidden></p>
                </div>
                <footer class="lm-dialog-footer">
                    <button type="button" class="lm-button" data-export-cancel>Cancel</button>
                    <button type="button" class="lm-button primary" data-export-confirm></button>
                </footer>
            </section>`;

        // Text set via textContent, never innerHTML: the counts are numbers, but the habit
        // matters in a file that renders into a dialog.
        backdrop.querySelector('#lm-export-title').textContent = title;
        backdrop.querySelector('[data-export-summary]').textContent = selected
            ? `${count} selected ${noun} will be exported.`
            : `All ${count.toLocaleString()} ${noun} ${scope} will be exported.`;

        backdrop.querySelector('[data-export-destination]').textContent = destination === 'drive'
            ? 'A CSV file will be saved to your Google Drive.'
            : 'A CSV file will download to this device.';

        // The whole point of the complaint: people could not tell whether they were exporting
        // everything. Say so explicitly when nothing is ticked.
        if (!selected) {
            const hint = backdrop.querySelector('[data-export-hint]');
            hint.textContent = `To export only some ${nouns[1]}, close this and tick the rows you want first.`;
            hint.hidden = false;
        }

        backdrop.querySelector('[data-export-confirm]').textContent =
            selected ? `Export ${count} ${noun}` : `Export all ${count.toLocaleString()}`;

        const close = (result) => {
            document.removeEventListener('keydown', onKey, true);
            backdrop.remove();
            document.body.classList.remove('lm-dialog-open');
            // Put focus back where it was, or the page loses its place for keyboard users.
            if (previouslyFocused instanceof HTMLElement) previouslyFocused.focus();
            resolve(result);
        };

        const onKey = (event) => {
            if (event.key === 'Escape') { event.preventDefault(); close(false); return; }
            if (event.key !== 'Tab') return;
            // Keep Tab inside the dialog while it is open.
            const focusable = backdrop.querySelectorAll('button');
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        };

        backdrop.addEventListener('click', (event) => {
            // The leads page has a document-level handler that closes any .lm-modal-backdrop it
            // sees clicked, via closeDialog() — which also aborts in-flight dialog fetches and
            // moves focus. This dialog manages its own lifecycle, so keep the click here.
            event.stopPropagation();
            if (event.target === backdrop || event.target.closest('[data-export-cancel]')) close(false);
            else if (event.target.closest('[data-export-confirm]')) close(true);
        });
        document.addEventListener('keydown', onKey, true);

        // Mount inside .lm-leads-page, where the server-rendered dialogs live. The light theme
        // defines --lm-overlay-* on that element, so a dialog appended to <body> would resolve
        // those to nothing and render with no border or muted colour. The element sets no
        // transform or filter, so position:fixed still resolves against the viewport.
        (document.querySelector('.lm-leads-page') || document.body).append(backdrop);
        document.body.classList.add('lm-dialog-open');
        backdrop.querySelector('[data-export-confirm]').focus();
    });
}

/** Rows ticked in the leads table right now. */
export function selectedLeadIds() {
    return [...document.querySelectorAll('[data-lm-select]:checked')].map((box) => box.value);
}

/**
 * Leads matching the current filters, read off the count badge the server rendered.
 *
 * Scoped to .lm-count because the bulk-actions bar carries a data-lm-total of its own, and a
 * bare attribute selector would depend on which happens to come first in the markup.
 */
export function filteredLeadTotal() {
    const badge = document.querySelector('.lm-count[data-lm-total]');
    return badge ? Number(badge.dataset.lmTotal) || 0 : 0;
}
