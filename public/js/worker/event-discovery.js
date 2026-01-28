/* ========= Bootstrapped data from backend ========= */

let events = Array.isArray(window.initialEvents) ? window.initialEvents : [];
let filteredEvents = [...events];

const eventsListEndpoint = window.eventsListEndpoint || null;

/* ========= Helpers ========= */

const $  = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

function getCurrentView() {
    const gridBtn = $('#gridViewBtn');
    return gridBtn && gridBtn.classList.contains('active') ? 'grid' : 'list';
}

function getOwnerName(ev) {
  // support multiple possible shapes coming from backend
  return (
    ev.owner_name ||
    ev.ownerName ||
    (ev.owner && ev.owner.name) ||
    (ev.creator && ev.creator.name) ||
    ev.created_by_name ||
    ev.createdByName ||
    ev.created_by || // fallback (id)
    '—'
  );
}

/* ========= Rendering ========= */

function renderEvents(view = 'grid') {
    const grid = $('#eventsGrid');
    if (!grid) return;

    grid.innerHTML = '';

    filteredEvents.forEach(ev => {
        // ---------- status label ----------
        const status = ev.status || 'open';
        const statusClass =
            status === 'open'
                ? 'status-open'
                : status === 'limited'
                    ? 'status-limited'
                    : 'status-full';

        const statusText =
            status === 'open'
                ? 'Open'
                : status === 'limited'
                    ? 'Limited Spots'
                    : 'Full';

        // ---------- spots: USED / TOTAL ----------
        // backend gives us spotsRemaining (available) + spotsTotal
        // we convert to: used = total - remaining
        let spotsTotal = ev.spotsTotal;
        let spotsRemaining = ev.spotsRemaining;

        // make sure they are numbers, not undefined / strings
        spotsTotal = typeof spotsTotal === 'number'
            ? spotsTotal
            : parseInt(spotsTotal || '0', 10);

        spotsRemaining = typeof spotsRemaining === 'number'
            ? spotsRemaining
            : parseInt(spotsRemaining || '0', 10);

        if (isNaN(spotsTotal)) spotsTotal = 0;
        if (isNaN(spotsRemaining)) spotsRemaining = 0;

        const spotsUsed = Math.max(0, spotsTotal - spotsRemaining);

        // ---------- roles ----------
        const rolesHtml = Array.isArray(ev.roles)
            ? ev.roles.map(r => `<span class="role-badge">${r}</span>`).join('')
            : '';

        // ---------- card ----------
        const card = document.createElement('div');
        card.className = 'event-card';

        if (view === 'list') {
            card.style.display = 'grid';
            card.style.gridTemplateColumns = '180px 1fr';
            card.style.alignItems = 'stretch';
        }

        card.onclick = () => openEventModal(ev);
const ownerName = getOwnerName(ev);

        card.innerHTML = `
            <img src="${ev.image}" alt="${ev.title}" class="event-image">
            <div class="event-content">
                <div class="event-header">
                    <span class="event-category">${ev.category ?? ''}</span>
                    <span class="event-status ${statusClass}">${statusText}</span>
                </div>
                <h3 class="event-title">${ev.title}</h3>
                <p class="event-description">${ev.description ?? ''}</p>
                <div class="event-meta">
                    <div class="meta-item">
                        <span class="meta-icon">📅</span>
                        <span>${ev.date || ''} ${ev.time ? 'at ' + ev.time : ''}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-icon">📍</span>
                        <span>${ev.location || ''}</span>
                    </div>
                    <div class="meta-item">
  <span class="meta-icon">👤</span>
  <span>${ownerName}</span>
</div>

                    <div class="meta-item">
                        <span class="meta-icon">⏱️</span>
                        <span>${ev.duration || '—'}</span>
                    </div>
                </div>
                <div class="event-roles">${rolesHtml}</div>
                <div class="event-footer">
        <span class="spots-remaining">
            <strong>${spotsUsed}</strong> / ${spotsTotal} spots
        </span>
        <button class="btn-apply"
                ${status === 'full' ? 'disabled' : ''}
                onclick="event.stopPropagation(); openEventModal(${ev.id});">
            ${status === 'full' ? 'Full' : 'Apply'}
        </button>
    </div>
`;

        grid.appendChild(card);
    });

    const countEl = $('#resultsCount');
    if (countEl) countEl.textContent = filteredEvents.length;
}


/* ========= Filters ========= */

function applyFilters(page = 1) {
    const termInput       = $('#searchInput');
    const categorySel     = $('#categoryFilter');
    const locationSel     = $('#locationFilter');
    const availabilitySel = $('#availabilityFilter');
    const dateSel         = $('#dateFilter');

    const searchTerm   = (termInput?.value || '').trim();
    const category     = categorySel?.value || '';
    const location     = locationSel?.value || '';
    const availability = availabilitySel?.value || '';
    const dateRange    = dateSel?.value || '';

    // If endpoint exists -> server-side filtering
    if (eventsListEndpoint) {
        const params = new URLSearchParams({
            q: searchTerm,
            category,
            location,
            availability,
            per_page: 12,
            page
        });

        fetch(`${eventsListEndpoint}?${params.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(res => res.json())
            .then(json => {
                events = Array.isArray(json.data) ? json.data : [];
                filteredEvents = [...events];
                buildUniqueLocations();
                applyDateFilterInFront(dateRange);
                renderEvents(getCurrentView());
                updateActiveFilters({ category, location, availability });
                updatePagination(json.current_page, json.last_page);
            })
            .catch(err => console.error(err));

        return;
    }

    // Fallback: pure client-side filtering on bootstrapped events
    filteredEvents = events.filter(ev => {

        const matchesSearch =
            !searchTerm ||
            (ev.title && ev.title.toLowerCase().includes(searchTerm.toLowerCase())) ||
            (ev.description && ev.description.toLowerCase().includes(searchTerm.toLowerCase())) ||
            (ev.location && ev.location.toLowerCase().includes(searchTerm.toLowerCase()));

        const matchesCategory = !category || String(ev.category_id) === String(category);
        const matchesLocation = !location || (ev.location || '').toLowerCase() === location.toLowerCase();
        const matchesAvail    = !availability || ev.status === availability;

        return matchesSearch && matchesCategory && matchesLocation && matchesAvail;
    });

    applyDateFilterInFront(dateRange);
    renderEvents(getCurrentView());
    updateActiveFilters({ category, location, availability });
    updatePagination(1, 1);
}

function applyDateFilterInFront(dateRange) {
    if (!dateRange) return;

    const today = new Date();
    const endOfWeek = new Date();
    endOfWeek.setDate(today.getDate() + 7);
    const endOfMonth = new Date();
    endOfMonth.setMonth(today.getMonth() + 1);

    filteredEvents = filteredEvents.filter(ev => {
        if (!ev.date) return false;
        const d = new Date(ev.date);

        if (dateRange === 'today') {
            return (
                d.getFullYear() === today.getFullYear() &&
                d.getMonth() === today.getMonth() &&
                d.getDate() === today.getDate()
            );
        }
        if (dateRange === 'week') {
            return d >= today && d <= endOfWeek;
        }
        if (dateRange === 'month') {
            return d >= today && d <= endOfMonth;
        }
        return true;
    });
}

function updateActiveFilters({ category, location, availability }) {
    const box = $('#activeFilters');
    if (!box) return;

    const tags = [];

    if (category) tags.push({ type: 'category', label: 'Category' });
    if (location) tags.push({ type: 'location', label: 'Location' });
    if (availability) tags.push({ type: 'availability', label: 'Availability' });

    if (!tags.length) {
        box.style.display = 'none';
        box.innerHTML = '';
        return;
    }

    box.style.display = 'flex';
    box.innerHTML = tags.map(t => `
        <span class="filter-tag">
            ${t.label}
            <span class="filter-tag-close" onclick="removeFilter('${t.type}')">×</span>
        </span>
    `).join('');
}

function removeFilter(type) {
    const el = document.getElementById(type + 'Filter');
    if (el) el.value = '';
    applyFilters();
}

/* ========= Pagination UI (for server mode) ========= */

function updatePagination(current, last) {
    const container = $('#pagination');
    if (!container) return;

    container.innerHTML = '';

    if (!last || last <= 1) return;

    const addBtn = (label, page, active = false, disabled = false) => {
        const btn = document.createElement('button');
        btn.className = 'page-btn' + (active ? ' active' : '');
        btn.textContent = label;
        if (!disabled) {
            btn.onclick = () => applyFilters(page);
        } else {
            btn.disabled = true;
        }
        container.appendChild(btn);
    };

    addBtn('Prev', Math.max(1, current - 1), false, current === 1);

    for (let p = 1; p <= last; p++) {
        addBtn(String(p), p, p === current);
    }

    addBtn('Next', Math.min(last, current + 1), false, current === last);
}

/* ========= Modal ========= */

let selectedEvent = null;

function openEventModal(eventOrId) {
    selectedEvent = typeof eventOrId === 'number'
        ? filteredEvents.find(e => e.id === eventOrId)
        : eventOrId;

    const modal = $('#eventModal');
    const body  = $('#modalBody');
    const ownerName = getOwnerName(selectedEvent);


    if (!modal || !body || !selectedEvent) return;

    const rolesHtml = Array.isArray(selectedEvent.roles)
        ? selectedEvent.roles.map(r => `<span class="role-badge">${r}</span>`).join('')
        : '';

          const workerRoleHtml = window.workerRoleName
        ? `<div class="meta-item"><span class="meta-icon">🧑‍💼</span>
               <span><strong>Your Role:</strong> ${window.workerRoleName}</span>
           </div>`
        : '';


    body.innerHTML = `
        <img src="${selectedEvent.image}" alt="${selectedEvent.title}"
             style="width:100%;border-radius:var(--radius-md);margin-bottom:16px;"class="modal-event-image">
        <h3 style="margin-bottom:8px;">${selectedEvent.title}</h3>
        <p style="color:var(--text-secondary);margin-bottom:16px;">${selectedEvent.description}</p>

        <div class="meta-item">
            <span class="meta-icon">📅</span>
            <span><strong>Date:</strong> ${selectedEvent.date} ${selectedEvent.time ? 'at ' + selectedEvent.time : ''}</span>
        </div>
        <div class="meta-item">
            <span class="meta-icon">📍</span>
            <span><strong>Location:</strong> ${selectedEvent.location}</span>
        </div>
        <div class="meta-item">
            <span class="meta-icon">🏷️</span>
            <span><strong>Category:</strong> ${selectedEvent.category || 'General'}</span>
        </div>
        <div class="meta-item">
      <span class="meta-icon">👤</span>
      <span><strong>Event Owner:</strong> ${ownerName}</span>
  </div>
        <div class="meta-item">
            <span class="meta-icon">⏱️</span>
            <span><strong>Duration:</strong> ${selectedEvent.duration}</span>
        </div>
        <div class="meta-item">
            <span class="meta-icon">👥</span>
            <span><strong>Available Spots (your role):</strong> ${selectedEvent.spotsRemaining}</span>
        </div>
        ${workerRoleHtml}

        <div style="margin-top:16px;">
            <strong>Roles:</strong>
            <div class="event-roles">
                ${rolesHtml || '<span class="role-badge">General Volunteer</span>'}
            </div>
        </div>
    `;


    modal.classList.add('active');
}

function closeModal() {
    const modal = $('#eventModal');
    if (modal) modal.classList.remove('active');
    selectedEvent = null;
}

function applyToEvent() {
    if (!selectedEvent || !window.applyEventBase) return;

    const url = `${window.applyEventBase}/${selectedEvent.id}/apply`;

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': window.csrfToken,
        },
        body: JSON.stringify({}),
    })
        .then(async res => {
            const data = await res.json().catch(() => null);

            if (!res.ok || !data || data.ok === false) {
                alert((data && data.message) || 'Failed to apply for this event.');
                return;
            }

            if (typeof data.spotsRemaining !== 'undefined') {
                selectedEvent.spotsRemaining = data.spotsRemaining;

                const idx = events.findIndex(e => e.id === selectedEvent.id);
                if (idx !== -1) {
                    events[idx].spotsRemaining = data.spotsRemaining;
                }

                renderEvents(getCurrentView());
            }

            alert('Application submitted successfully.');
            closeModal();
        })
        .catch(err => {
            console.error(err);
            alert('Unexpected error while applying.');
        });
}

function buildUniqueLocations() {
  const locationSel = document.getElementById('locationFilter');
  if (!locationSel) return;

  // keep the first option (All Locations)
  const firstOption = locationSel.options[0];
  locationSel.innerHTML = '';
  locationSel.appendChild(firstOption);

  const seen = new Set();

  events.forEach(ev => {
    const loc = (ev.location || '').trim().replace(/\s+/g, ' ');
    if (!loc) return;

    const key = loc.toLowerCase();
    if (seen.has(key)) return; // ✅ already added

    const opt = document.createElement('option');
    opt.value = loc;
    opt.textContent = loc;
    locationSel.appendChild(opt);

    seen.add(key);
  });
}


/* ========= Theme & Language ========= */

function toggleTheme() {
    const html = document.documentElement;
    const current = html.getAttribute('data-theme') || 'dark';
    const next = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);

    const icon = $('#theme-icon');
    if (icon) icon.textContent = next === 'dark' ? '🌙' : '☀️';
}

function toggleLanguage() {
    const html = document.documentElement;
    const current = html.getAttribute('lang') || 'en';
    const next = current === 'en' ? 'ar' : 'en';
    html.setAttribute('lang', next);
    html.setAttribute('dir', next === 'ar' ? 'rtl' : 'ltr');

    const icon = $('#lang-icon');
    if (icon) icon.textContent = next === 'en' ? 'AR' : 'EN';
}

/* ========= View toggle ========= */

function setView(view) {
    const gridBtn = $('#gridViewBtn');
    const listBtn = $('#listViewBtn');
    if (!gridBtn || !listBtn) return;

    if (view === 'grid') {
        gridBtn.classList.add('active');
        listBtn.classList.remove('active');
    } else {
        listBtn.classList.add('active');
        gridBtn.classList.remove('active');
    }

    renderEvents(view);
}

/* ========= Init ========= */

document.addEventListener('DOMContentLoaded', () => {
    buildUniqueLocations();
    renderEvents('grid');
    updatePagination(1, 1);

    const searchInput       = $('#searchInput');
    const categoryFilter    = $('#categoryFilter');
    const locationFilter    = $('#locationFilter');
    const availabilityFilter= $('#availabilityFilter');
    const dateFilter        = $('#dateFilter');
    const gridBtn           = $('#gridViewBtn');
    const listBtn           = $('#listViewBtn');

    if (searchInput)        searchInput.addEventListener('input', () => applyFilters());
    if (categoryFilter)     categoryFilter.addEventListener('change', () => applyFilters());
    if (locationFilter)     locationFilter.addEventListener('change', () => applyFilters());
    if (availabilityFilter) availabilityFilter.addEventListener('change', () => applyFilters());
    if (dateFilter)         dateFilter.addEventListener('change', () => applyFilters());

    if (gridBtn) gridBtn.addEventListener('click', () => setView('grid'));
    if (listBtn) listBtn.addEventListener('click', () => setView('list'));
});

/* expose for inline calls */
window.applyFilters    = applyFilters;
window.removeFilter    = removeFilter;
window.openEventModal  = openEventModal;
window.closeModal      = closeModal;
window.applyToEvent    = applyToEvent;
window.toggleTheme     = toggleTheme;
window.toggleLanguage  = toggleLanguage;
window.applyToEvent = applyToEvent;
