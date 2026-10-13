/* ---------------------------------------------------------------------------
   "Who is this event with?" — a typeahead over the company's leads.

   Replaces a <select> that held at most 500 leads in first-name order: finding
   anyone meant scrolling and already knowing how their name was spelled. This
   searches name, email, phone and account number through the same filterSearch
   the leads list uses, so it finds exactly who that list would.

   Document-level delegation and no framework, like the rest of leadmax: the
   calendar body is swapped in as a fragment, so anything bound to an element at
   load time is bound to an element that no longer exists by the time it is used.
   --------------------------------------------------------------------------- */

const MIN_QUERY = 2;
const DEBOUNCE_MS = 220;

const state = new WeakMap();   // field -> { items, active, timer, controller, lastQuery }

const fieldOf = (node) => node?.closest?.('[data-lm-people]') ?? null;
const inputOf = (field) => field.querySelector('[data-lm-people-input]');
const valueOf = (field) => field.querySelector('[data-lm-people-value]');
const listOf = (field) => field.querySelector('.lm-people-list');

const read = (field) => {
    if (!state.has(field)) state.set(field, { items: [], active: -1, timer: null, controller: null, lastQuery: null });
    return state.get(field);
};

const close = (field) => {
    const list = listOf(field);
    const input = inputOf(field);
    if (list) {
        list.hidden = true;
        list.innerHTML = '';
    }
    input?.setAttribute('aria-expanded', 'false');
    read(field).active = -1;
};

const render = (field) => {
    const list = listOf(field);
    const input = inputOf(field);
    const { items, active } = read(field);

    if (!list) return;

    if (!items.length) {
        // An explicit "nothing found" rather than a silently empty box, so it is
        // clear the search ran.
        list.innerHTML = '<li class="lm-people-empty" role="presentation">No matching leads</li>';
        list.hidden = false;
        input?.setAttribute('aria-expanded', 'true');
        return;
    }

    list.innerHTML = items.map((item, index) =>
        '<li role="option" id="lm-people-opt-' + index + '"'
        + ' aria-selected="' + (index === active ? 'true' : 'false') + '"'
        + ' class="lm-people-option' + (index === active ? ' is-active' : '') + '"'
        + ' data-lm-people-option="' + index + '">'
        + '<strong></strong><small></small></li>'
    ).join('');

    // Text set through the DOM, never interpolated into the markup above: these
    // are lead names and email addresses typed by whoever created the lead.
    [...list.children].forEach((node, index) => {
        node.querySelector('strong').textContent = items[index].name;
        node.querySelector('small').textContent = items[index].detail || '';
    });

    list.hidden = false;
    input?.setAttribute('aria-expanded', 'true');
};

const choose = (field, index) => {
    const { items } = read(field);
    const item = items[index];
    if (!item) return;

    inputOf(field).value = item.detail ? item.name + ' — ' + item.detail : item.name;
    valueOf(field).value = item.id;
    close(field);
};

const search = async (field, term) => {
    const data = read(field);

    // One request in flight per field: typing fast otherwise lets an earlier,
    // slower response overwrite the list for what is now a longer query.
    data.controller?.abort();
    data.controller = new AbortController();

    try {
        const response = await fetch('/panel/leadmax/calendar/people?q=' + encodeURIComponent(term), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal: data.controller.signal,
        });
        if (!response.ok) throw new Error('search failed');

        const body = await response.json();
        data.items = Array.isArray(body?.data) ? body.data : [];
        data.active = -1;
        render(field);
    } catch (error) {
        if (error.name === 'AbortError') return;
        close(field);
    }
};

document.addEventListener('input', (event) => {
    const field = fieldOf(event.target);
    if (!field || !event.target.matches('[data-lm-people-input]')) return;

    const data = read(field);
    const term = event.target.value.trim();

    // Typing after a pick means they are choosing someone else; the old id must
    // not travel with the form.
    valueOf(field).value = '';

    clearTimeout(data.timer);
    if (term.length < MIN_QUERY) {
        data.controller?.abort();
        close(field);
        return;
    }
    if (term === data.lastQuery) return;
    data.lastQuery = term;
    data.timer = setTimeout(() => search(field, term), DEBOUNCE_MS);
});

document.addEventListener('keydown', (event) => {
    const field = fieldOf(event.target);
    if (!field || !event.target.matches('[data-lm-people-input]')) return;

    const data = read(field);
    const list = listOf(field);
    if (!list || list.hidden || !data.items.length) {
        if (event.key === 'Escape') close(field);
        return;
    }

    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        const step = event.key === 'ArrowDown' ? 1 : -1;
        data.active = (data.active + step + data.items.length) % data.items.length;
        render(field);
        list.children[data.active]?.scrollIntoView({ block: 'nearest' });
        return;
    }
    if (event.key === 'Enter' && data.active >= 0) {
        // Only swallow Enter when something is highlighted, so the form can still
        // be submitted from this field.
        event.preventDefault();
        choose(field, data.active);
        return;
    }
    if (event.key === 'Escape') {
        event.preventDefault();
        close(field);
    }
});

// mousedown, not click: the input's blur would close the list first.
document.addEventListener('mousedown', (event) => {
    const option = event.target.closest?.('[data-lm-people-option]');
    if (option) {
        event.preventDefault();
        const field = fieldOf(option);
        if (field) choose(field, Number(option.dataset.lmPeopleOption));
        return;
    }
    // A click anywhere else dismisses an open list.
    for (const field of document.querySelectorAll('[data-lm-people]')) {
        if (!field.contains(event.target)) close(field);
    }
});

// A form that was submitted and re-rendered, or reset, must not keep showing a
// name whose hidden id has gone.
document.addEventListener('reset', (event) => {
    for (const field of event.target.querySelectorAll?.('[data-lm-people]') ?? []) {
        inputOf(field).value = '';
        valueOf(field).value = '';
        close(field);
    }
});
