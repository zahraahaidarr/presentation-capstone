/* ========= Bootstrapped data from backend ========= */

let events = Array.isArray(window.initialEvents) ? window.initialEvents : [];
let currentFilter = 'all';

const categoriesFromBackend = Array.isArray(window.initialCategories)
  ? window.initialCategories
  : [];

const rolesFromBackend = Array.isArray(window.initialRoleTypes)
  ? window.initialRoleTypes
  : [];

const venuesFromBackend = Array.isArray(window.initialVenues)
  ? window.initialVenues
  : [];

// Use real DB values; fall back only if tables are empty
let categoryList = categoriesFromBackend.length
  ? categoriesFromBackend.map(c => c.name)
  : ['wedding', 'graduation'];

let workerTypeList = rolesFromBackend.length
  ? rolesFromBackend.map(r => r.name)
  : ['Organizer', 'Civil Defense', 'Media Staff', 'Tech Support', 'Cleaner', 'Decorator', 'Cooking Team', 'Waiter'];

/* ========= Helpers ========= */

const $  = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

function slugifyRole(name) {
  return (name || '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/gi, '-')
    .replace(/(^-|-$)/g, '');
}

/* ========= Table render ========= */

function renderEvents() {
  const tbody = $('#eventsTableBody');

  const filtered = currentFilter === 'all'
    ? events
    : events.filter(e => {
        const k = (e.status || '').toLowerCase();
        return k === currentFilter;
      });

  tbody.innerHTML = filtered.map(event => {
    const statusRaw   = (event.status || 'DRAFT').toUpperCase(); // DRAFT / PUBLISHED / ...
    const statusKey   = statusRaw.toLowerCase();
    const statusLabel = statusKey.charAt(0).toUpperCase() + statusKey.slice(1);
    const statusClass = `status-${statusKey}`;

     const isCompleted = statusRaw === 'COMPLETED';

    const actionsHtml = isCompleted
      ? `<span class="muted">—</span>`
      : `
        <div class="action-buttons">
          <button class="btn btn-secondary btn-sm" data-edit="${event.id}">Edit</button>
          <button class="btn btn-success btn-sm" data-status="PUBLISHED" data-id="${event.id}">Publish</button>
          <button class="btn btn-danger btn-sm" data-status="CANCELLED" data-id="${event.id}">Cancel</button>
          <button class="btn btn-light btn-sm" data-status="DRAFT" data-id="${event.id}">Set as Draft</button>
        </div>
      `;

    return `
      <tr>
        <td class="event-title-cell">${event.title}</td>
        <td><span class="event-category">${event.category || '-'}</span></td>
        <td>${event.date || ''}</td>
        <td>${event.location || ''}</td>
        <td>${event.applicants || 0} / ${event.totalSpots || 0}</td>
        <td><span class="status-badge ${statusClass}">${statusLabel}</span></td>
        <td>${actionsHtml}</td>
      </tr>`;
  }).join('');
}

/* ========= Tabs ========= */

function wireTabs() {
  $$('.tab').forEach(tab => {
    tab.addEventListener('click', ev => {
      $$('.tab').forEach(t => t.classList.remove('active'));
      ev.currentTarget.classList.add('active');

      // expects data-filter = all | draft | published | active | completed | cancelled
      currentFilter = ev.currentTarget.dataset.filter || 'all';
      renderEvents();
    });
  });
}

/* ========= Roles UI ========= */

function getAvailableRoles() {
  const used = new Set(
    $$('#rolesContainer select.role-select')
      .map(s => s.value)
      .filter(Boolean)
  );
  return workerTypeList.filter(r => !used.has(r));
}

function refreshRoleSelectOptions() {
  $$('#rolesContainer select.role-select').forEach(select => {
    const current = select.value;
    const options = new Set([current, ...getAvailableRoles()]);
    select.innerHTML = '';
    options.forEach(role => {
      if (!role) return;
      const opt = document.createElement('option');
      opt.value = role;
      opt.textContent = role;
      select.appendChild(opt);
    });
  });
}

function renderRoleRow(roleName = '', spots = 0) {
  const wrap = $('#rolesContainer');
  const row  = document.createElement('div');
  row.className = 'role-item';

  const sel = document.createElement('select');
  sel.className = 'role-select';

  workerTypeList.forEach(r => {
    const o = document.createElement('option');
    o.value = r;
    o.textContent = r;
    sel.appendChild(o);
  });

  if (roleName && workerTypeList.includes(roleName)) {
    sel.value = roleName;
  }

  const inp = document.createElement('input');
  inp.type = 'number';
  inp.min = '0';
  inp.placeholder = 'Spots';
  inp.className = 'role-spots';
  inp.value = String(spots || 0);

  const btn = document.createElement('button');
  btn.type = 'button';
  btn.className = 'btn-remove';
  btn.textContent = 'Remove';
  btn.onclick = () => { row.remove(); refreshRoleSelectOptions(); };

  sel.addEventListener('change', refreshRoleSelectOptions);

  row.appendChild(sel);
  row.appendChild(inp);
  row.appendChild(btn);
  wrap.appendChild(row);

  refreshRoleSelectOptions();
}

function addRoleRow() {
  const avail = getAvailableRoles();
  if (!avail.length) {
    alert('All worker types are already added.');
    return;
  }
  renderRoleRow(avail[0], 0);
}

function collectRolesFromModal() {
  return $$('#rolesContainer .role-item')
    .map(r => {
      const name  = r.querySelector('select.role-select').value;
      const spots = Number(r.querySelector('input.role-spots').value || 0);
      return { name, slug: slugifyRole(name), spots };
    })
    .filter(x => x.spots > 0);
}

/* ========= Wizard navigation ========= */

let WZ_STEP = 1;

function setWizardStep(n) {
  WZ_STEP = n;
  ['step1','step2','step3'].forEach((id, i) => {
    $('#'+id).classList.toggle('active', i + 1 === n);
  });
  [1,2,3].forEach(i => {
    $('#wz'+i).classList.toggle('active', i <= n);
  });

  $('#btn_back').style.display       = n > 1 ? '' : 'none';
  $('#btn_next').style.display       = n < 3 ? '' : 'none';
  $('#btn_save_draft').style.display = n === 3 ? '' : 'none';
  $('#btn_publish').style.display    = n === 3 ? '' : 'none';
}

function wizardBack() {
  setWizardStep(Math.max(1, WZ_STEP - 1));
}

async function wizardNext() {
  if (WZ_STEP === 1) {
    // sync category selects and call AI staffing
   

    const cat1 = $('#wizard_event_category').value;
    const cat3 = $('#eventCategory');
    if (cat3 && cat1) cat3.value = cat1;

    
    await runStaffingAndFillStep2(); // AI prediction fills step 2

    setWizardStep(2);
    return;
  }

  if (WZ_STEP === 2) {
    const rows = $$('#wizard_role_capacity_rows tr')
      .map(r => {
        const name = r.querySelector('td').textContent.trim();
        const cap  = Number(r.querySelector('input.capacity').value || 0);
        return { name, cap };
      })
      .filter(x => x.cap > 0);

    $('#rolesContainer').innerHTML = '';
    rows.forEach(p => renderRoleRow(p.name, p.cap));

    const total = rows.reduce((s, x) => s + x.cap, 0);
    if (total > 0) $('#eventSpots').value = String(total);

    setWizardStep(3);
    return;
  }
}

/* ========= Modal ========= */

let editingEventId = null;    // null => create, not null => edit

function openCreateModal() {
  editingEventId = null;
  $('#modalTitle').textContent = 'Create New Event';
  $('#eventForm').reset();
  $('#rolesContainer').innerHTML = '';
  ensureCategoryOptions();
   ensureVenueOptions();   
  buildStep2CapacityRows();
  setWizardStep(1);
  const area = $('#venue_area_m2');
  if (area) area.value = '';
  $('#eventModal').classList.add('active');
}

function closeModal() {
  editingEventId = null;
  $('#eventModal').classList.remove('active');
}

/* ========= Category options ========= */

function ensureCategoryOptions() {
  const select = $('#eventCategory');
  if (select) {
    // keep the first "Select category..." option
    const existing = new Set(
      $$('#eventCategory option').map(o => o.value)
    );

    categoryList.forEach(c => {
      if (!existing.has(c)) {
        const opt = document.createElement('option');
        opt.value = c;
        opt.textContent = c.charAt(0).toUpperCase() + c.slice(1);
        select.appendChild(opt);
      }
    });

    // when creating a new event keep placeholder selected
    if (!editingEventId) {
      select.value = '';      // matches <option value="">Select category...</option>
    }
  }

  // Wizard select – always rebuild with placeholder as the default
  $('#wizard_event_category').innerHTML =
    '<option value="">Select category...</option>' +
    categoryList
      .map(c => `<option value="${c}">${c[0].toUpperCase() + c.slice(1)}</option>`)
      .join('');
}

/* ========= Step 2 rows ========= */

function buildStep2CapacityRows() {
  const tbody = $('#wizard_role_capacity_rows');
  tbody.innerHTML = workerTypeList.map(w => `
    <tr data-worker-type="${slugifyRole(w)}">
      <td>${w}</td>
      <td><input class="capacity" type="number" min="0" value="0" style="max-width:180px"></td>
    </tr>
  `).join('');
}

/* ========= AI staffing ========= */

async function runStaffingAndFillStep2() {
  if (!window.ENDPOINT_AI_STAFFING) return false;

  const area   = Number($('#venue_area_m2').value || 0);
  const people = Number($('#expected_attendees').value || 0);
  const cat    = $('#wizard_event_category').value || (categoryList[0] || 'general');

  const roles = [...workerTypeList];

  try {
    const res = await fetch(window.ENDPOINT_AI_STAFFING, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': window.csrfToken
      },
      body: JSON.stringify({
        venue_area_m2:      area,
        expected_attendees: people,
        category:           cat,
        available_roles:    roles
      })
    });

    if (!res.ok) {
      console.error('AI staffing HTTP error', res.status, res.statusText);
      return false;
    }

    const ai = await res.json();
    console.log('AI staffing response:', ai); // 👈 log full payload

    // Support both { role_capacities: {...} } and { data: { role_capacities: {...} } }
    const caps =
      ai.role_capacities ||
      (ai.data && ai.data.role_capacities) ||
      {};

    console.log('Parsed role capacities:', caps);

    // build rows once here and fill them
    buildStep2CapacityRows();

    $$('#wizard_role_capacity_rows tr').forEach(tr => {
      const label = tr.querySelector('td').textContent.trim();
      const inp   = tr.querySelector('input.capacity');

      const val = caps[label];

      if (typeof val === 'number' && !Number.isNaN(val)) {
        inp.value = val;
      }
    });

    return true;
  } catch (e) {
    console.error('AI staffing error', e);
    return false;
  }
}


/* ========= Create/Update (Publish / Draft) ========= */

async function submitEvent(status = 'PUBLISHED') {
  const form = $('#eventForm');
  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const roles = collectRolesFromModal();
  if (!roles.length) {
    alert('Add at least one role with spots > 0.');
    return;
  }

  const isEdit = !!editingEventId;
  const url = isEdit
    ? `${window.ENDPOINT_UPDATE_EVENT_BASE}/${editingEventId}`
    : window.ENDPOINT_CREATE_EVENT;
  const method = 'POST'; // using POST + _method for PUT

  const fd = new FormData();
  fd.append('_token', window.csrfToken);
  if (isEdit) {
    fd.append('_method', 'PUT');
  }

  fd.append('title', $('#eventTitle').value);
  fd.append('description', $('#eventDescription').value);
  fd.append('category', $('#eventCategory').value);
  fd.append('location', $('#eventLocation').value);
  fd.append('date', $('#eventDate').value);
  fd.append('time', $('#eventTime').value);
  fd.append('duration_hours', $('#eventDuration').value);
  fd.append('total_spots', $('#eventSpots').value);
  fd.append('requirements', '');
  fd.append('venue_area_m2', $('#venue_area_m2').value || 0);
  fd.append('expected_attendees', $('#expected_attendees').value || 0);
  fd.append('status', status);
const venueVal = $('#venue_id').value || '';
fd.append('venue_id', venueVal === 'other' ? '' : venueVal);

  fd.append('roles', JSON.stringify(roles));

  const imgInput = $('#eventImage');
  if (imgInput && imgInput.files && imgInput.files[0]) {
    fd.append('image', imgInput.files[0]);
  }

  try {
    const res = await fetch(url, {
      method,
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': window.csrfToken,
      },
      body: fd,
    });

    const raw = await res.text();
    let data = null;
    try { data = raw ? JSON.parse(raw) : null; } catch (_) {}

    const buildErrorMessage = () => {
      let msg = `Request failed (${res.status} ${res.statusText})`;
      if (data) {
        if (data.message) msg += `\nMessage: ${data.message}`;
        if (data.error)   msg += `\nError: ${data.error}`;
        if (data.errors) {
          msg += `\nValidation errors:`;
          Object.entries(data.errors).forEach(([field, msgs]) => {
            msgs.forEach(m => { msg += `\n - ${field}: ${m}`; });
          });
        }
      } else if (raw) {
        msg += `\nRaw response: ${raw.substring(0,400)}`;
      }
      return msg;
    };

    if (!res.ok || !data || data.ok === false) {
      console.error('Event save failed', { status: res.status, data, raw });
      alert(buildErrorMessage());
      return;
    }

    const ev = data.event;

    if (isEdit) {
      const idx = events.findIndex(e => String(e.id) === String(ev.id));
      if (idx !== -1) {
        events[idx] = {
          id: ev.id,
          title: ev.title,
          category: ev.category,
          date: (ev.starts_at || '').substring(0,10),
          location: ev.location,
          applicants: events[idx].applicants || 0,
          totalSpots: ev.total_spots,
          status: ev.status,
        };
      }
      alert('Event updated successfully.');
    } else {
      events.unshift({
        id: ev.id,
        title: ev.title,
        category: ev.category,
        date: (ev.starts_at || '').substring(0,10),
        location: ev.location,
        applicants: 0,
        totalSpots: ev.total_spots,
        status: ev.status || status,
      });
      alert(ev.status === 'DRAFT'
        ? 'Draft saved successfully.'
        : 'Event created successfully.'
      );
    }

    closeModal();
    renderEvents();

  } catch (err) {
    console.error('Unexpected error while saving event', err);
    alert('Unexpected error while saving event: ' + err.message);
  }
}

function publishEvent() {
  submitEvent('PUBLISHED');
}

function saveDraft() {
  submitEvent('DRAFT');
}

/* ========= Status update from table ========= */

async function updateEventStatus(id, status) {
  if (!window.ENDPOINT_UPDATE_EVENT_STATUS_BASE) {
    console.warn('ENDPOINT_UPDATE_EVENT_STATUS_BASE is not defined.');
    return;
  }

  try {
    const url = `${window.ENDPOINT_UPDATE_EVENT_STATUS_BASE}/${id}/status`;
    const res = await fetch(url, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': window.csrfToken,
      },
      body: JSON.stringify({ status }),
    });

    const data = await res.json().catch(() => null);

    if (!res.ok || !data || data.ok !== true) {
      console.error('Status update error', {res, data});
      alert('Failed to update status. Please try again.');
      return;
    }

    const newStatus = data.event?.status || status;
    const ev = events.find(e => String(e.id) === String(id));
    if (ev) {
      ev.status = newStatus;
      renderEvents();
    }

    alert(`Status updated to ${newStatus}.`);

  } catch (e) {
    console.error('Status update exception', e);
    alert('Unexpected error while updating status.');
  }
}

/* ========= Load event into modal for EDIT ========= */

async function openEditModal(id) {
  editingEventId = id;
  $('#modalTitle').textContent = 'Edit Event';
  $('#eventForm').reset();
  $('#rolesContainer').innerHTML = '';
  ensureCategoryOptions();

  setWizardStep(3);

  try {
    const url = `${window.ENDPOINT_UPDATE_EVENT_BASE}/${id}`;
    const res = await fetch(url, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      }
    });

    const data = await res.json();

    if (!res.ok || !data || !data.ok) {
      console.error('Failed to load event', data);
      alert('Failed to load event data.');
      return;
    }

    const ev = data.event;

    $('#eventTitle').value        = ev.title || '';
    $('#eventDescription').value  = ev.description || '';
    $('#eventCategory').value     = ev.category || '';
    $('#eventLocation').value     = ev.location || '';
    $('#eventDate').value         = ev.date || '';
    $('#eventTime').value         = ev.time || '';
    $('#eventDuration').value     = ev.duration_hours || 1;
    $('#eventSpots').value        = ev.total_spots || 1;
    $('#venue_area_m2').value     = ev.venue_area_m2 ?? '';
    $('#expected_attendees').value = ev.expected_attendees ?? '';

    const preview  = $('#eventImagePreview');
    const imgInput = $('#eventImage');

    if (preview) {
      if (ev.image_url) {
        preview.src = ev.image_url;
        preview.style.display = 'block';
      } else {
        preview.src = '';
        preview.style.display = 'none';
      }
    }
    if (imgInput) {
      imgInput.value = '';
    }

    $('#rolesContainer').innerHTML = '';
    if (Array.isArray(ev.roles) && ev.roles.length) {
      ev.roles.forEach(r => renderRoleRow(r.name, r.spots));
    }

    $('#eventModal').classList.add('active');

  } catch (e) {
    console.error('Error loading event for edit', e);
    alert('Unexpected error while loading event.');
  }
}

/* ========= Table actions ========= */

function handleTableClicks(e) {
  const editId   = e.target.getAttribute('data-edit');
  const status   = e.target.getAttribute('data-status');
  const statusId = e.target.getAttribute('data-id');

  if (editId) {
    openEditModal(editId);
    return;
  }

  if (status && statusId) {
    updateEventStatus(statusId, status);
  }
}

/* ========= Init ========= */

document.addEventListener('DOMContentLoaded', () => {
  $('#btn_open_create').addEventListener('click', openCreateModal);
  $('#btn_close_modal').addEventListener('click', closeModal);
  $('#btn_back').addEventListener('click', wizardBack);
  $('#btn_next').addEventListener('click', wizardNext);
  $('#btn_publish').addEventListener('click', publishEvent);
  $('#btn_save_draft').addEventListener('click', saveDraft);
  $('#btn_add_role').addEventListener('click', addRoleRow);

  $('.table').addEventListener('click', handleTableClicks);

  ensureCategoryOptions();
  ensureVenueOptions();
wireVenueAutoFill();
  buildStep2CapacityRows();
  wireTabs();
  renderEvents();

  const imgInput = $('#eventImage');
  if (imgInput) {
    imgInput.addEventListener('change', (e) => {
      const file = e.target.files[0];
      const preview = $('#eventImagePreview');
      if (!preview) return;

      if (file) {
        const url = URL.createObjectURL(file);
        preview.src = url;
        preview.style.display = 'block';
      } else {
        preview.src = '';
        preview.style.display = 'none';
      }
    });
  }
});
function ensureVenueOptions() {
  const sel = document.getElementById('venue_id');
  if (!sel) return;

  const venuesHtml = venuesFromBackend
    .map(v => `<option value="${v.id}" data-area="${v.area_m2 ?? ''}">
                ${v.name}${v.city ? ' • ' + v.city : ''}
              </option>`)
    .join('');

  sel.innerHTML =
    '<option value="">Select venue...</option>' +
    venuesHtml +
    '<option value="other">Other...</option>';
}


function wireVenueAutoFill() {
  const sel  = document.getElementById('venue_id');
  const area = document.getElementById('venue_area_m2');
  if (!sel || !area) return;

  const setAreaMode = (mode) => {
    if (mode === 'manual') {
      area.readOnly = false;
      area.placeholder = 'Enter area...';
      area.value = ''; // clear
    } else {
      area.readOnly = true;
      area.placeholder = 'Auto-filled';
    }
  };

  // default state
  setAreaMode('auto');

  sel.addEventListener('change', () => {
    const val = sel.value;

    // ✅ If Other -> manual input
    if (val === 'other') {
      setAreaMode('manual');
      return;
    }

    // ✅ If empty -> reset
    if (!val) {
      area.value = '';
      setAreaMode('auto');
      return;
    }

    // ✅ Normal venue -> auto fill + readonly
    const opt = sel.options[sel.selectedIndex];
    const a = opt ? opt.getAttribute('data-area') : '';
    area.value = a || '';
    setAreaMode('auto');
  });
}

