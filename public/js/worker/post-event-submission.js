// =========================
// i18n strings
// =========================
const STRINGS = {
  en: {
    brand:"Volunteer", dashboard:"Dashboard", discover:"Discover Events",
    myRes:"My Reservations", submissions:"Post-Event Submissions",
    announcements:"Announcements", chat:"Chat", profile:"Profile", settings:"Settings",
    pageTitle:"Post-Event Submissions",
    pageSubtitle:"Submit your post-event reports within 24 hours of event completion. Include photos, videos, and detailed descriptions.",
    search:"Search submissions…",
    submitted:"Submitted", pending:"Pending Review",
    viewReport:"View Report",
    noResults:"No submissions found.",
    noSubmissions:"No submissions yet.",
    chooseEventRole:"Please choose an event first.",
    submitOk:"Report submitted successfully!",
    submitFail:(st)=>`Failed to submit report (status ${st}).`,
    submitError:"Error submitting report.",
    ownerRatingLabel: "Rate event owner (1–5)",
    ownerRatingHint:" This rating will be saved with your report. Only the system administrators can see it — the event owner cannot."

  },
  ar: {
    brand:"متطوّع", dashboard:"لوحة التحكم", discover:"استكشف الفعاليات",
    myRes:"حجوزاتي", submissions:"تقارير ما بعد الحدث",
    announcements:"التعميمات", chat:"المحادثة", profile:"الملف الشخصي", settings:"الإعدادات",
    pageTitle:"تقارير ما بعد الحدث",
    pageSubtitle:"قدّم تقاريرك بعد الفعالية خلال 24 ساعة من انتهائها. أضف الصور والفيديوهات والوصف التفصيلي.",
    search:"ابحث في التقارير…",
    submitted:"تم التقديم", pending:"قيد المراجعة",
    viewReport:"عرض التقرير",
    noResults:"لا توجد تقارير مطابقة.",
    noSubmissions:"لا توجد تقارير حتى الآن.",
    chooseEventRole:"الرجاء اختيار الفعالية أولاً.",
    submitOk:"تم إرسال التقرير بنجاح!",
    submitFail:(st)=>`فشل إرسال التقرير (رمز ${st}).`,
    submitError:"حدث خطأ أثناء إرسال التقرير."
  }
};

// =========================
// Role slug mapping
// =========================
// DB slugs come from role_types.name via Str::slug(name, '_')
// DOM slugs are the values in data-role on each <fieldset>
const ROLE_SLUG_MAP = {
  // DB slug         // DOM slug
  'organizer':      'organizer',
  'civil_defense':  'civil',
  'media_staff':    'media',
  'tech_support':   'tech',
  'cleaner':        'cleaner',
  'decorator':      'decorator',
  'cooking_team':   'cooking',
  'waiter':         'waiter',
};

// =========================
// DOM helpers / globals
// =========================
let lang = 'en';
const $   = sel => document.querySelector(sel);

const list            = $('#submissionsList');
const eventSelect     = document.getElementById('eventSelect');
const roleLabelInput  = document.getElementById('roleLabel');
const roleForms       = document.getElementById('roleForms');
const submissionIdInp = document.getElementById('submissionId');
const formTitle       = document.getElementById('formTitle');
const submitBtn       = document.getElementById('submitBtn');
const resetBtn        = document.getElementById('resetBtn');

const cdCasesList    = document.getElementById('cd_cases_list');
const cdAddBtn       = document.getElementById('cd_add_case');

// ⭐ add:
const ownerRatingStars = document.getElementById('ownerRatingStars');
const ownerRatingValue = document.getElementById('ownerRatingValue');

let currentRoleSlugDb = null;   // from DB, e.g. "civil_defense"
let currentEditable   = true;   // track if current loaded submission is editable
let ownerRating       = 0; 
// =========================
// i18n on static elements
// =========================
function populateEventsFromJson() {
  if (!eventSelect) return;

  const raw = eventSelect.dataset.reservations;
  if (!raw) return;

  let items = [];
  try {
    items = JSON.parse(raw);
  } catch (e) {
    console.error('Failed to parse reservations JSON', e);
    return;
  }

  // remove all options except the first placeholder
  while (eventSelect.options.length > 1) {
    eventSelect.remove(1);
  }

  items.forEach(item => {
    const opt = document.createElement('option');
    opt.value = String(item.reservation_id);
    opt.dataset.roleSlug = item.role_slug;
    opt.dataset.roleName = item.role_label;

    let label = item.event_name;
    if (item.date) {
      label += ' - ' + item.date;
    }
    label += ' • Role: ' + item.role_label;

    opt.textContent = label;
    eventSelect.appendChild(opt);
  });
}

function renderSubmissionsFromJson() {
  if (!list) return;

  const raw = list.dataset.submissions;
  let items = [];
  try {
    items = raw ? JSON.parse(raw) : [];
  } catch (e) {
    console.error('Failed to parse submissions JSON', e);
  }

  list.innerHTML = '';

  if (!items.length) {
    const msg = document.createElement('div');
    msg.style.textAlign = 'center';
    msg.style.padding   = '40px';
    msg.style.color     = 'var(--muted)';
    msg.textContent     = STRINGS[lang].noSubmissions;
    list.appendChild(msg);
    return;
  }

items.forEach(sub => {
  const ratingText = sub.owner_rating ? `⭐ ${sub.owner_rating}/5` : '⭐ —';

  const card = document.createElement('article');
  card.className = 'card';
  card.dataset.subId    = sub.id;
  card.dataset.resId    = sub.worker_reservation_id;
  card.dataset.roleSlug = sub.role_slug;
  card.dataset.canEdit  = sub.can_edit ? '1' : '0';
  card.dataset.data     = JSON.stringify(sub.data || {});
  card.dataset.civil    = JSON.stringify(sub.civil_cases || []);
  card.dataset.rating   = sub.owner_rating ?? '';

  card.innerHTML = `
    <div class="card-header">
      <div class="card-title">${sub.event_name}</div>
      <span class="chip-status ${sub.chip_class}">
        ${sub.status_label}
      </span>
    </div>

    <div class="meta">
      <span>📅 Submitted: ${sub.submitted_at}</span>
      <span>${ratingText}</span>
      ${sub.can_edit ? '<span>🕒 Editable for 24h</span>' : ''}
    </div>

    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <button class="btn small ghost"
              type="button"
              data-act="view"
              data-id="${sub.id}">
        ${STRINGS[lang].viewReport}
      </button>
      ${
        sub.can_edit
          ? '<span class="hint-editable">You can still edit this report.</span>'
          : '<span class="hint-locked">View only (locked).</span>'
      }
    </div>
  `;
  list.appendChild(card);
});
}

function i18nApply(){
  const s = STRINGS[lang];
  document.documentElement.dir = (lang==='ar') ? 'rtl' : 'ltr';

  $('#brandName')        && ($('#brandName').textContent        = s.brand);
  $('#navDashboard')     && ($('#navDashboard').textContent     = s.dashboard);
  $('#navDiscover')      && ($('#navDiscover').textContent      = s.discover);
  $('#navMyRes')         && ($('#navMyRes').textContent         = s.myRes);
  $('#navSubmissions')   && ($('#navSubmissions').textContent   = s.submissions);
  $('#navAnnouncements') && ($('#navAnnouncements').textContent = s.announcements);
  $('#navChat')          && ($('#navChat').textContent          = s.chat);
  $('#navProfile')       && ($('#navProfile').textContent       = s.profile);
  $('#navSettings')      && ($('#navSettings').textContent      = s.settings);
  $('#pageTitle')        && ($('#pageTitle').textContent        = s.pageTitle);
  $('#pageSubtitle')     && ($('#pageSubtitle').textContent     = s.pageSubtitle);
  $('#globalSearch')     && ($('#globalSearch').placeholder     = s.search);
    $('#ownerRatingLabel') && ($('#ownerRatingLabel').textContent = s.ownerRatingLabel);
  $('#ownerRatingHint')  && ($('#ownerRatingHint').textContent  = s.ownerRatingHint);
}

// =========================
// Search over existing cards
// =========================
function bindSearch(){
  const input = $('#globalSearch');
  if (!input || !list) return;

  input.addEventListener('input', () => {
    const q = input.value.toLowerCase().trim();
    const cards = list.querySelectorAll('.card');
    let visible = 0;

    cards.forEach(card => {
      const txt  = card.textContent.toLowerCase();
      const show = !q || txt.includes(q);
      card.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    const msgId = 'submissionsEmptyMsg';
    let msg = document.getElementById(msgId);
    if (visible === 0) {
      if (!msg) {
        msg = document.createElement('div');
        msg.id = msgId;
        msg.style.textAlign = 'center';
        msg.style.padding   = '40px';
        msg.style.color     = 'var(--muted)';
        list.appendChild(msg);
      }
      msg.textContent = STRINGS[lang].noResults;
    } else if (msg) {
      msg.remove();
    }
  });
}
// =========================
// Event owner rating (frontend only)
// =========================
function updateOwnerRatingUI(){
  if (!ownerRatingStars) return;
  const stars = ownerRatingStars.querySelectorAll('.star');
  stars.forEach(star => {
    const v = parseInt(star.dataset.value, 10);
    if (v <= ownerRating) {
      star.classList.add('active');
    } else {
      star.classList.remove('active');
    }
  });
  if (ownerRatingValue) {
    ownerRatingValue.value = ownerRating;
  }
}

function resetOwnerRating(){
  ownerRating = 0;
  updateOwnerRatingUI();
}

function bindOwnerRating(){
  if (!ownerRatingStars) return;
  const stars = ownerRatingStars.querySelectorAll('.star');
  stars.forEach(star => {
    star.addEventListener('click', () => {
      const v = parseInt(star.dataset.value, 10) || 0;
      ownerRating = (ownerRating === v ? 0 : v); // click again to clear
      updateOwnerRatingUI();
    });
  });
  // initial render
  updateOwnerRatingUI();
}

// =========================
// Role form logic (auto from reservation)
// =========================
function showRoleForm(roleSlugDb){
  if (!roleForms) return;

  const domRole = ROLE_SLUG_MAP[roleSlugDb] || roleSlugDb || null;

  roleForms.querySelectorAll('.role-set').forEach(fs => {
    const fsRole = fs.getAttribute('data-role');
    fs.style.display = (domRole && fsRole === domRole) ? 'block' : 'none';
  });
}

function applyRoleFromEvent(){
  if (!eventSelect) return;

  const opt = eventSelect.options[eventSelect.selectedIndex];

  if (!opt || !opt.dataset.roleSlug) {
    currentRoleSlugDb = null;
    if (roleLabelInput) roleLabelInput.value = '';
    showRoleForm(null);
    return;
  }

  currentRoleSlugDb = opt.dataset.roleSlug;   // e.g. "decorator"
  const label = opt.dataset.roleName || currentRoleSlugDb;

  if (roleLabelInput) roleLabelInput.value = label;
  showRoleForm(currentRoleSlugDb);
}

populateEventsFromJson();   // <-- build options first

if (eventSelect) {
  eventSelect.addEventListener('change', () => {
    // New event picked => treat as new submission
    if (submissionIdInp) submissionIdInp.value = '';
    currentEditable = true;
    setFormEditable(true);
    if (formTitle) formTitle.textContent = 'Submit New Report';
    if (submitBtn) submitBtn.textContent = 'Submit Report';
    resetOwnerRating();          // ⭐ add this line
    applyRoleFromEvent();
  });

  applyRoleFromEvent();
}


// =========================
// Civil Defense dynamic cases
// =========================
function addCivilCaseRow(initial){
  if (!cdCasesList) return;

  const wrap = document.createElement('div');
  wrap.className = 'two-col';
  wrap.style.alignItems   = 'end';
  wrap.style.border       = '1px solid var(--border-color)';
  wrap.style.borderRadius = '10px';
  wrap.style.padding      = '10px';
  wrap.style.marginBottom = '8px';

  wrap.innerHTML = `
    <div class="form-group">
      <label>Type of case</label>
      <select data-cd="type">
        <option value="" disabled>Select type…</option>
        <option value="injury">Injury</option>
        <option value="fainting">Fainting</option>
        <option value="panic">Panic attack</option>
        <option value="other">Other</option>
      </select>
    </div>
    <div class="form-group">
      <label>Age </label>
      <input type="number" min="0" data-cd="age" placeholder="e.g., 27">
    </div>
    <div class="form-group">
      <label>Gender</label>
      <select data-cd="gender">
        <option value="" disabled>Gender…</option>
        <option>Male</option>
        <option>Female</option>
      </select>
    </div>
    <div class="form-group">
      <label>Action taken</label>
      <select data-cd="action">
        <option value="" disabled>Action taken…</option>
        <option value="on-site-care">On-site care</option>
        <option value="hospital-referral">Hospital referral</option>
        <option value="other">Other</option>
      </select>
    </div>
    <div class="form-group" style="grid-column:1/-1">
      <label>Notes (optional)</label>
      <input type="text" data-cd="notes" placeholder="Short description or context…">
    </div>
    <div style="grid-column:1/-1;display:flex;justify-content:flex-end">
      <button type="button" class="btn small ghost" data-remove>Remove</button>
    </div>
  `;

  wrap.querySelector('[data-remove]').addEventListener('click', () => wrap.remove());
  cdCasesList.appendChild(wrap);

  if (initial) {
    const { type, age, gender, action, notes } = initial;
    if (type)   wrap.querySelector('[data-cd="type"]').value   = type;
    if (age)    wrap.querySelector('[data-cd="age"]').value    = age;
    if (gender) wrap.querySelector('[data-cd="gender"]').value = gender;
    if (action) wrap.querySelector('[data-cd="action"]').value = action;
    if (notes)  wrap.querySelector('[data-cd="notes"]').value  = notes;
  }
}

if (cdAddBtn) cdAddBtn.addEventListener('click', () => addCivilCaseRow(null));

function getCivilCases(){
  if (!cdCasesList) return [];
  const rows = [...cdCasesList.querySelectorAll('.two-col')];
  return rows.map(r => ({
    type:   r.querySelector('[data-cd="type"]')?.value   || '',
    age:    r.querySelector('[data-cd="age"]')?.value    || '',
    gender: r.querySelector('[data-cd="gender"]')?.value || '',
    action: r.querySelector('[data-cd="action"]')?.value || '',
    notes:  r.querySelector('[data-cd="notes"]')?.value  || ''
  }));
}

// =========================
// Enable/disable form (read-only vs editable)
// =========================
function setFormEditable(editable){
  currentEditable = editable;
  const form = document.getElementById('reportForm');
  if (!form) return;

  const controls = form.querySelectorAll('input, textarea, select, button[type="submit"]');
  controls.forEach(el => {
    if (el === submissionIdInp) return; // keep hidden
    if (el === resetBtn) {
      el.disabled = !editable;
      return;
    }
    // role label is always read-only
    if (el === roleLabelInput) {
      el.readOnly = true;
      el.disabled = false;
      return;
    }
    el.disabled = !editable;
  });

  if (submitBtn) submitBtn.textContent = editable ? 'Submit Report' : 'View Only';
  if (formTitle) formTitle.textContent = editable ? 'Submit / Edit Report' : 'View Report';
}

// =========================
// Submit logic (AJAX + files + confirm popup)
// =========================
const confirmModal  = document.getElementById('confirmModal');
const confirmButton = document.getElementById('confirmSubmit');
const cancelButton  = document.getElementById('cancelSubmit');

function openConfirmModal(){
  if (confirmModal) confirmModal.style.display = 'flex';
}
function closeConfirmModal(){
  if (confirmModal) confirmModal.style.display = 'none';
}

function bindForm(){
  const form = document.getElementById('reportForm');
  if (!form) return;

  const storeUrl = form.dataset.storeUrl || form.action;
  const meta     = document.querySelector('meta[name="csrf-token"]');
  const csrf     = meta ? meta.getAttribute('content')
                        : (form.querySelector('input[name="_token"]')?.value || '');

  let isSubmitting = false;

  const doSubmit = async () => {
    if (isSubmitting) return;
    if (!currentEditable) {
      toast('This report is view-only and can no longer be edited.');
      return;
    }
    isSubmitting = true;

    const s = STRINGS[lang];
    const eventSel   = document.getElementById('eventSelect');
    const roleSlugDb = currentRoleSlugDb;
    const domRole    = ROLE_SLUG_MAP[roleSlugDb] || roleSlugDb;

    if (!eventSel?.value || !roleSlugDb) {
      toast(s.chooseEventRole);
      isSubmitting = false;
      return;
    }

    const payload = {
      worker_reservation_id: eventSel.value,
      role_slug: roleSlugDb,
      general_notes: '', // reserved if you add a generic notes textarea later
      data: {},
      civil_cases: [],
    };

    switch (domRole) {
      case 'organizer':
        payload.data = {
          attendance:  document.getElementById('org_attendance').value,
          issues:      document.getElementById('org_issues').value.trim(),
          improvements:document.getElementById('org_improve').value.trim()
        };
        break;

      case 'civil':
        payload.data = {
          attendanceState: document.getElementById('cd_check').value,
          totalCases:      +document.getElementById('cd_total_cases').value || 0,
          concerns:        document.getElementById('cd_concerns').value.trim(),
        };
        payload.civil_cases = getCivilCases();
        break;

      case 'media':
        payload.data = {
          labels:      document.getElementById('media_labels').value.trim(),
          photosCount: +document.getElementById('media_report_photos').value || 0,
          videosCount: +document.getElementById('media_report_videos').value || 0,
          problems:    document.getElementById('media_problems').value.trim(),
          captions:    document.getElementById('media_captions').value.trim()
        };
        break;

      case 'tech':
        payload.data = {
          allOk:        document.getElementById('tech_ok').value,
          returned:     document.getElementById('tech_returned').value,
          issues:       document.getElementById('tech_issues').value.trim(),
          improvements: document.getElementById('tech_suggest').value.trim()
        };
        break;

      case 'cleaner':
        payload.data = {
          zones:       +document.getElementById('clean_zones').value || 0,
          extraHelp:   document.getElementById('clean_extra').value,
          notes:       document.getElementById('clean_notes').value.trim(),
          suggestions: document.getElementById('clean_suggest').value.trim()
        };
        break;

      case 'decorator':
        payload.data = {
          used:     document.getElementById('dec_used').value.trim(),
          damaged:  document.getElementById('dec_damaged').value.trim(),
          replace:  document.getElementById('dec_replace').value.trim(),
          feedback: document.getElementById('dec_feedback').value.trim()
        };
        break;

      case 'cooking':
        payload.data = {
          meals:       document.getElementById('cook_meals').value.trim(),
          ingredients: document.getElementById('cook_ingredients').value.trim(),
          leftovers:   document.getElementById('cook_leftovers').value.trim(),
          hygiene:     document.getElementById('cook_hygiene').value.trim()
        };
        break;

      case 'waiter':
        payload.data = {
          attendance:    document.getElementById('wait_attendance').value,
          itemsServed:   document.getElementById('wait_items').value.trim(),
          serviceIssues: document.getElementById('wait_issues').value.trim(),
          leftovers:     document.getElementById('wait_leftovers').value.trim()
        };
        break;
    }

    const fd = new FormData();
    fd.append('_token', csrf);
    fd.append('worker_reservation_id', payload.worker_reservation_id);
    fd.append('role_slug', payload.role_slug);
    fd.append('general_notes', payload.general_notes);
    fd.append('data', JSON.stringify(payload.data));
    fd.append('civil_cases', JSON.stringify(payload.civil_cases));
if (ownerRating > 0) {
  fd.append('owner_rating', ownerRating);
}

    if (submissionIdInp && submissionIdInp.value) {
      fd.append('submission_id', submissionIdInp.value);
    }

    if (domRole === 'civil') {
      const cdForms = document.getElementById('cd_forms');
      if (cdForms) [...cdForms.files].forEach(f => fd.append('cd_forms[]', f));
    }
    if (domRole === 'media') {
      const media = document.getElementById('media_files');
      if (media) [...media.files].forEach(f => fd.append('media_files[]', f));
    }
    if (domRole === 'tech') {
      const rec = document.getElementById('tech_recording');
      if (rec?.files[0]) fd.append('tech_recording', rec.files[0]);
    }
    if (domRole === 'decorator') {
      const dec = document.getElementById('dec_photos');
      if (dec) [...dec.files].forEach(f => fd.append('dec_photos[]', f));
    }
    if (domRole === 'cooking') {
      const cook = document.getElementById('cook_photos');
      if (cook) [...cook.files].forEach(f => fd.append('cook_photos[]', f));
    }

    try {
      const res = await fetch(storeUrl, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
        },
        body: fd,
      });

      if (!res.ok) {
        const text = await res.text();
        console.error('Backend error (status ' + res.status + '):', text);
        toast(s.submitFail(res.status));
        isSubmitting = false;
        return;
      }

      const data = await res.json();
      console.log('Saved submission:', data);

      toast(s.submitOk);
      setTimeout(() => window.location.reload(), 600);

    } catch (err) {
      console.error('Fetch error:', err);
      toast(STRINGS[lang].submitError);
      isSubmitting = false;
    }
  };

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    if (confirmModal) {
      openConfirmModal();
    } else {
      doSubmit();
    }
  });

  confirmButton?.addEventListener('click', async () => {
    closeConfirmModal();
    await doSubmit();
  });

  cancelButton?.addEventListener('click', () => {
    closeConfirmModal();
  });

  form.addEventListener('reset', () => {
    if (submissionIdInp) submissionIdInp.value = '';
    currentEditable = true;
    setFormEditable(true);
    if (formTitle) formTitle.textContent = 'Submit New Report';
    if (submitBtn) submitBtn.textContent = 'Submit Report';
        resetOwnerRating();
  });
}

// =========================
// Fill form from existing submission when "View Report"
// =========================
function clearRoleForms(){
  if (!roleForms) return;
  roleForms.querySelectorAll('input, textarea, select').forEach(el => {
    if (el.type === 'file') return;
    if (el.tagName === 'SELECT') {
      if (el.options.length) el.selectedIndex = 0;
    } else {
      el.value = '';
    }
  });
  if (cdCasesList) cdCasesList.innerHTML = '';
}

function fillFormFromSubmission(domRole, data, civilCases){
  clearRoleForms();

  switch(domRole){
    case 'organizer':
      document.getElementById('org_attendance').value = data.attendance || '';
      document.getElementById('org_issues').value     = data.issues || '';
      document.getElementById('org_improve').value    = data.improvements || '';
      break;

    case 'civil':
      document.getElementById('cd_check').value        = data.attendanceState || '';
      document.getElementById('cd_total_cases').value  = data.totalCases ?? 0;
      document.getElementById('cd_concerns').value     = data.concerns || '';
      if (Array.isArray(civilCases)) {
        civilCases.forEach(c => addCivilCaseRow(c));
      }
      break;

    case 'media':
      document.getElementById('media_labels').value           = data.labels || '';
      document.getElementById('media_report_photos').value    = data.photosCount ?? 0;
      document.getElementById('media_report_videos').value    = data.videosCount ?? 0;
      document.getElementById('media_problems').value         = data.problems || '';
      document.getElementById('media_captions').value         = data.captions || '';
      break;

    case 'tech':
      document.getElementById('tech_ok').value        = data.allOk || '';
      document.getElementById('tech_returned').value  = data.returned || '';
      document.getElementById('tech_issues').value    = data.issues || '';
      document.getElementById('tech_suggest').value   = data.improvements || '';
      break;

    case 'cleaner':
      document.getElementById('clean_zones').value    = data.zones ?? '';
      document.getElementById('clean_extra').value    = data.extraHelp || '';
      document.getElementById('clean_notes').value    = data.notes || '';
      document.getElementById('clean_suggest').value  = data.suggestions || '';
      break;

    case 'decorator':
      document.getElementById('dec_used').value       = data.used || '';
      document.getElementById('dec_damaged').value    = data.damaged || '';
      document.getElementById('dec_replace').value    = data.replace || '';
      document.getElementById('dec_feedback').value   = data.feedback || '';
      break;

    case 'cooking':
      document.getElementById('cook_meals').value       = data.meals || '';
      document.getElementById('cook_ingredients').value = data.ingredients || '';
      document.getElementById('cook_leftovers').value   = data.leftovers || '';
      document.getElementById('cook_hygiene').value     = data.hygiene || '';
      break;

    case 'waiter':
      document.getElementById('wait_attendance').value = data.attendance || '';
      document.getElementById('wait_items').value      = data.itemsServed || '';
      document.getElementById('wait_issues').value     = data.serviceIssues || '';
      document.getElementById('wait_leftovers').value  = data.leftovers || '';
      break;
  }
}

function bindActions(){
  if (!list) return;

  list.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-act="view"]');
    if (!btn) return;

    const card = btn.closest('.card');
    if (!card) return;

    const subId   = card.dataset.subId;
    const resId   = card.dataset.resId;
    const roleSlugDb = card.dataset.roleSlug;
    const canEdit = card.dataset.canEdit === '1';
    let data = {};
    let civil = [];

    try {
      data = card.dataset.data ? JSON.parse(card.dataset.data) : {};
    } catch (err) {
      console.warn('Failed to parse data JSON', err);
    }
    try {
      civil = card.dataset.civil ? JSON.parse(card.dataset.civil) : [];
    } catch (err) {
      console.warn('Failed to parse civil JSON', err);
    }

    // Set hidden submission id
    if (submissionIdInp) submissionIdInp.value = subId || '';

    // Select event & role
  if (resId) {
  const eventLabelText = card.querySelector('.card-title')?.textContent?.trim() || 'Selected Event';
  setEventSelectValue(resId, roleSlugDb, roleSlugDb, eventLabelText);
  applyRoleFromEvent(); // now it WILL fill role label + show correct form
}


    currentRoleSlugDb = roleSlugDb || null;
    const domRole = ROLE_SLUG_MAP[currentRoleSlugDb] || currentRoleSlugDb;
    const ratingFromCard = parseInt(card.dataset.rating || '0', 10) || 0;
ownerRating = ratingFromCard;
updateOwnerRatingUI();


    if (roleLabelInput && domRole) {
      // role label is already from reservation; keep as is
    }

    showRoleForm(currentRoleSlugDb);
    fillFormFromSubmission(domRole, data || {}, civil || []);
    setFormEditable(canEdit);

    if (formTitle) formTitle.textContent = canEdit ? 'Edit Report' : 'View Report';
    if (submitBtn) submitBtn.textContent = canEdit ? 'Save Changes' : 'View Only';

    const formCard = document.getElementById('submissionForm');
    if (formCard) formCard.scrollIntoView({ behavior:'smooth', block:'start' });

    if (!canEdit) {
      toast('This report is locked (older than 24 hours or already processed).');
    } else {
      toast('You are editing an existing report.');
    }
  });
}

// =========================
// Small toast helper
// =========================
function toast(msg){
  let box = document.getElementById('toastContainer');
  if (!box){
    box = document.createElement('div');
    box.id = 'toastContainer';
    document.body.appendChild(box);
    Object.assign(box.style,{
      position:'fixed', left:'50%', transform:'translateX(-50%)',
      bottom:'24px', zIndex:9999
    });
  }
  const t = document.createElement('div');
  Object.assign(t.style,{
    background:'rgba(0,0,0,.7)', color:'#fff',
    padding:'10px 14px', borderRadius:'10px', marginTop:'8px'
  });
  t.textContent = msg;
  box.appendChild(t);
  setTimeout(()=> t.remove(), 2200);
}
function setEventSelectValue(resId, roleSlugDb, roleName, eventLabelText) {
  if (!eventSelect) return false;

  const target = String(resId ?? '').trim();
  if (!target) return false;

  // 1) Try to find the option by value (string-safe)
  const opts = [...eventSelect.options];
  const found = opts.find(o => String(o.value) === target);

  if (found) {
    eventSelect.value = target;
    return true;
  }

  // 2) If not found, create it BUT include datasets so applyRoleFromEvent() works
  const opt = document.createElement('option');
  opt.value = target;

  opt.dataset.roleSlug = roleSlugDb || '';
  opt.dataset.roleName = roleName || roleSlugDb || '';

  opt.textContent = eventLabelText || 'Selected Event';

  eventSelect.appendChild(opt);
  eventSelect.value = target;

  return true;
}


// =========================
// Init
// =========================
i18nApply();
renderSubmissionsFromJson();
populateEventsFromJson();
bindOwnerRating();   // ⭐ rating widget
bindForm();
bindActions();
bindSearch();
