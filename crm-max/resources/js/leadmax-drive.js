import { confirmExport, selectedLeadIds, filteredLeadTotal } from './leadmax-confirm';
// Google Drive picker for LeadMax.
//
// Everything here is bound by delegation from `document`, never by querying elements at load:
// Splade recompiles the page body on navigation, so handlers attached to specific nodes — and
// any inline <script> in a Blade view — do not survive. The markup only carries data-lm-drive-*
// attributes; this file is the single place that knows what they mean.
//
// The access token comes from the SERVER (picker-config), minted from the tenant's stored
// refresh token. It is deliberately not a separate browser-side Google sign-in: an admin signed
// into a different Google account would then pick files the server has no grant for, and every
// download would 404 in a way that looks like a bug rather than a mismatch.

const CONFIG_URL = '/panel/leadmax/settings/google-drive/picker-config';
const GAPI_SRC = 'https://apis.google.com/js/api.js';

let configPromise = null;
let pickerApiPromise = null;

/** Per-tenant picker credentials. Never cached across page loads — the token is short lived. */
function pickerConfig() {
    if (!configPromise) {
        configPromise = fetch(CONFIG_URL, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
            cache: 'no-store',
        }).then((res) => {
            if (res.status === 409) throw new Error('Google Drive is not connected. Connect it in Settings → Integrations.');
            if (!res.ok) throw new Error('Could not start Google Drive. Please try again.');
            return res.json();
        }).catch((err) => {
            configPromise = null; // let the next attempt retry rather than caching the failure
            throw err;
        });
    }

    return configPromise;
}

/** Load Google's JS and the picker module once, then reuse. */
function loadPickerApi() {
    if (pickerApiPromise) return pickerApiPromise;

    pickerApiPromise = new Promise((resolve, reject) => {
        const ready = () => window.gapi.load('picker', { callback: resolve, onerror: () => reject(new Error('Google picker failed to load.')) });

        if (window.gapi) return ready();

        const script = document.createElement('script');
        script.src = GAPI_SRC;
        script.async = true;
        script.defer = true;
        script.onload = ready;
        script.onerror = () => { pickerApiPromise = null; reject(new Error('Could not reach Google. Check the connection and try again.')); };
        document.head.appendChild(script);
    }).catch((err) => {
        pickerApiPromise = null;
        throw err;
    });

    return pickerApiPromise;
}

/**
 * Open the picker.
 *
 * `folders: true` switches to picking a destination folder. setIncludeFolders and
 * setSelectFolderEnabled are two separate switches — the first makes folders visible, the
 * second makes them selectable; without both you get a browser you cannot choose from.
 *
 * LIST mode rather than GRID because drive.file grants no thumbnail access, so a grid renders
 * as a wall of blank tiles.
 */
async function openPicker({ folders = false, mimeTypes = null } = {}) {
    const [cfg] = await Promise.all([pickerConfig(), loadPickerApi()]);
    const picker = window.google.picker;

    const view = folders
        ? new picker.DocsView(picker.ViewId.FOLDERS)
            .setIncludeFolders(true)
            .setSelectFolderEnabled(true)
            .setMimeTypes('application/vnd.google-apps.folder')
        : new picker.DocsView(picker.ViewId.DOCS).setMode(picker.DocsViewMode.LIST);

    if (!folders && mimeTypes) view.setMimeTypes(mimeTypes);

    return new Promise((resolve) => {
        const builder = new picker.PickerBuilder()
            .setOAuthToken(cfg.accessToken)
            .setDeveloperKey(cfg.apiKey)
            .addView(view)
            .setCallback((data) => {
                // The callback also fires for CANCEL and for the initial LOADED event, so
                // nothing may be read off `docs` until the action is actually PICKED.
                if (data.action === picker.Action.PICKED) {
                    const doc = data.docs && data.docs[0];
                    resolve(doc ? { id: doc.id, name: doc.name, mimeType: doc.mimeType, sizeBytes: doc.sizeBytes } : null);
                } else if (data.action === picker.Action.CANCEL) {
                    resolve(null);
                }
            });

        // The app id ties the picker to the same Cloud project as the OAuth client; without it
        // the per-file grant does not attach and the server gets a 404 on the chosen file.
        if (cfg.appId) builder.setAppId(cfg.appId);

        builder.build().setVisible(true);
    });
}

/** Surface a failure without a bare alert() where the page has somewhere better to put it. */
function reportError(trigger, message) {
    const target = trigger.closest('[data-lm-drive-scope]')?.querySelector('[data-lm-drive-error]');

    if (target) {
        target.textContent = message;
        target.hidden = false;
        return;
    }

    window.alert(message);
}

function clearError(trigger) {
    const target = trigger.closest('[data-lm-drive-scope]')?.querySelector('[data-lm-drive-error]');
    if (target) target.hidden = true;
}

/**
 * One delegated handler for every Drive button on any page.
 *
 * data-lm-drive-pick       what to pick: "folder" | "file"
 * data-lm-drive-mimes      optional comma-separated mime allow-list for files
 * data-lm-drive-form       id of the form to fill and (optionally) submit
 * data-lm-drive-label      selector for an element to show the chosen name in
 * data-lm-drive-submit     "true"    native submit (full page post)
 *                          "request" requestSubmit(), which fires a real submit event — use
 *                                    this where another handler owns the form, e.g. the leads
 *                                    Import dialog's AJAX submit, which a native submit would
 *                                    bypass and so lose its JSON result handling
 * data-lm-drive-action     retarget the form to this URL before submitting, so a page whose
 *                          form normally posts an upload can post a Drive file id instead and
 *                          carry all of its other fields along unchanged
 */
// Close any open export menu when the click lands outside it. Delegated for the same reason
// as everything else here: the markup is replaced on Splade navigation.
document.addEventListener('click', (event) => {
    document.querySelectorAll('details.lm-export-menu[open]').forEach((menu) => {
        if (!menu.contains(event.target)) menu.removeAttribute('open');
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('details.lm-export-menu[open]').forEach((menu) => menu.removeAttribute('open'));
});

document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-lm-drive-pick]');
    if (!trigger) return;

    event.preventDefault();

    if (trigger.dataset.lmDriveBusy === 'true') return;
    trigger.dataset.lmDriveBusy = 'true';
    trigger.setAttribute('aria-busy', 'true');
    clearError(trigger);

    try {
        // Confirm the row count before the picker opens, so the number being exported is stated
        // up front rather than after the folder has been chosen.
        if (trigger.hasAttribute('data-lm-confirm-export')) {
            const ids = selectedLeadIds();
            const ok = await confirmExport({
                selected: ids.length,
                total: filteredLeadTotal(),
                destination: trigger.dataset.lmConfirmExport || 'drive',
            });
            if (!ok) return;
        }

        const folders = trigger.dataset.lmDrivePick === 'folder';
        const picked = await openPicker({ folders, mimeTypes: trigger.dataset.lmDriveMimes || null });

        if (!picked) return; // cancelled — leave everything untouched

        const form = trigger.dataset.lmDriveForm ? document.getElementById(trigger.dataset.lmDriveForm) : null;

        if (form) {
            const set = (name, value) => {
                const field = form.querySelector(`[name="${name}"]`);
                if (field) field.value = value;
            };
            set(folders ? 'folder_id' : 'file_id', picked.id);
            set(folders ? 'folder_name' : 'file_name', picked.name);
        }

        if (trigger.dataset.lmDriveLabel) {
            const label = document.querySelector(trigger.dataset.lmDriveLabel);
            if (label) label.textContent = picked.name;
        }

        const submitMode = trigger.dataset.lmDriveSubmit;

        if (form && (submitMode === 'true' || submitMode === 'request')) {
            if (trigger.dataset.lmDriveAction) form.action = trigger.dataset.lmDriveAction;

            // A file input left populated would be posted alongside the Drive id and could be
            // picked up by the upload branch instead.
            form.querySelectorAll('input[type="file"]').forEach((input) => { input.value = ''; });

            // Live filtering updates the address bar without re-rendering this form, so the
            // filter inputs Blade wrote can describe an older query than the one on screen.
            // Rebuild them from the URL, which is kept in step with the visible results.
            if (form.hasAttribute('data-lm-drive-sync-filters')) {
                form.querySelectorAll('input[data-lm-drive-filter]').forEach((node) => node.remove());
                new URL(location.href).searchParams.forEach((value, key) => {
                    if (['lead', 'tab', 'dialog', 'page'].includes(key)) return;
                    const field = document.createElement('input');
                    field.type = 'hidden';
                    field.name = key;          // already "country[]" for a multi-select
                    field.value = value;
                    field.setAttribute('data-lm-drive-filter', '');
                    form.appendChild(field);
                });
            }

            // Mirror what the download link does with ticked rows, so "export selected" means
            // the same thing whichever destination is chosen.
            if (form.hasAttribute('data-lm-drive-include-selected')) {
                form.querySelectorAll('input[data-lm-drive-selected]').forEach((node) => node.remove());
                document.querySelectorAll('[data-lm-select]:checked').forEach((box) => {
                    const field = document.createElement('input');
                    field.type = 'hidden';
                    field.name = 'ids[]';
                    field.value = box.value;
                    field.setAttribute('data-lm-drive-selected', '');
                    form.appendChild(field);
                });
            }

            // Collapse the menu the trigger came from, so it is not left hanging open behind
            // the page that is now navigating.
            trigger.closest('details[open]')?.removeAttribute('open');

            form.hidden = false;

            // requestSubmit fires a cancellable submit event, which is what a delegated AJAX
            // handler listens for; submit() dispatches nothing and would post the page.
            if (submitMode === 'request' && typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        }
    } catch (err) {
        reportError(trigger, err?.message || 'Google Drive could not be opened.');
    } finally {
        trigger.dataset.lmDriveBusy = 'false';
        trigger.removeAttribute('aria-busy');
    }
});
