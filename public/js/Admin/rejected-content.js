/* public/js/Admin/rejected-content.js */
(function () {
  const data = Array.isArray(window.initialRejected) ? window.initialRejected : [];
  const csrfMeta = document.querySelector('meta[name="csrf-token"]');
  const csrf = csrfMeta ? csrfMeta.getAttribute('content') : '';

  const tableBody = document.getElementById('rejectedTable');
  const filterType = document.getElementById('filterType');
  const filterStatus = document.getElementById('filterStatus');
  const filterUser = document.getElementById('filterUser');

  const modal = document.getElementById('previewModal');
  const pmMedia = document.getElementById('pm-media');
  const pmText = document.getElementById('pm-text');
  const pmSub = document.getElementById('pm-sub');

  // If any element is missing, stop to avoid silent failures
  if (!tableBody || !filterType || !filterStatus || !filterUser || !modal || !pmMedia || !pmText || !pmSub) {
    console.error('Rejected Content: Missing required DOM elements.');
    return;
  }

  function esc(s) {
    return (s ?? '').toString()
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function badge(status) {
    const cls =
      status === 'approved' ? 'badge-approved'
      : status === 'rejected' ? 'badge-rejected'
      : 'badge-pending';
    return `<span class="badge ${cls}">${esc(status)}</span>`;
  }

  function getTextRow(x) {
    if (x.content_type === 'post') return x.content || '';
    if (x.content_type === 'reel') return x.caption || '';
    return ''; // story usually no text
  }

  function mediaCell(x) {
    if (!x.media_url) return `<span class="small">No media</span>`;
    return `<button class="btn btn-secondary" data-preview="${x.id}">Preview</button>`;
  }

  function actionsCell(x) {
    const disabled = (x.review_status === 'approved') ? 'disabled' : '';
    return `
      <div class="action-buttons">
        <button class="btn btn-success" data-approve="${x.id}" ${disabled}>Approve</button>
        <button class="btn btn-danger" data-reject="${x.id}">Reject</button>
      </div>
    `;
  }

  function render() {
    const typeVal = filterType.value.trim();
    const statusVal = filterStatus.value.trim();
    const userVal = filterUser.value.trim();

    const rows = data.filter(x => {
      if (typeVal && x.content_type !== typeVal) return false;
      if (statusVal && x.review_status !== statusVal) return false;
      if (userVal && String(x.employee_user_id) !== userVal) return false;
      return true;
    });

    tableBody.innerHTML = rows.map(x => {
      const txt = getTextRow(x);
      return `
        <tr>
          <td><strong>${esc(x.content_type)}</strong></td>
          <td>#${esc(x.employee_user_id)}</td>
          <td>${mediaCell(x)}</td>
          <td>${esc(txt).slice(0, 120)}${txt.length > 120 ? '…' : ''}</td>
          <td>${badge(x.review_status)}</td>
          <td class="small">
            related: ${x.ai_related ? 'yes' : 'no'}<br>
            cat: ${x.ai_category_id ?? '-'}<br>
            reason: ${esc(x.ai_reason ?? '-')}
          </td>
          <td class="small">${esc(x.created_at)}</td>
          <td>${actionsCell(x)}</td>
        </tr>
      `;
    }).join('') || `
      <tr><td colspan="8" class="small" style="padding:18px;">No results.</td></tr>
    `;
  }

  async function postJSON(url) {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json'
      }
    });

    const json = await res.json().catch(() => ({}));
    if (!res.ok || json.ok === false) {
      throw new Error(json.message || 'Request failed');
    }
    return json;
  }

  // ✅ One source of truth for closing (fixes your X button issue)
  function closeModal() {
    modal.classList.add('hidden');

    // full reset so nothing "sticks"
    pmMedia.innerHTML = '';
    pmText.textContent = '';
    pmSub.textContent = '';

    // hide both sections to remove any extra space
    pmMedia.style.display = 'none';
    pmText.style.display = 'none';
  }

  function openPreview(x) {
    pmSub.textContent = `Type: ${x.content_type} • User #${x.employee_user_id} • Status: ${x.review_status}`;

    // reset
    pmMedia.innerHTML = '';
    pmText.textContent = '';
    pmMedia.style.display = 'none';
    pmText.style.display = 'none';

    // TEXT
    const text = getTextRow(x);
    if (text && text.trim() !== '') {
      pmText.textContent = text;
      pmText.style.display = 'block';
    }

    // MEDIA
    const media = (x.media_url ?? '').toString().trim();
    if (media !== '') {
      const lower = media.toLowerCase();
      const isVideo = lower.endsWith('.mp4') || lower.endsWith('.mov') || lower.endsWith('.webm');

      pmMedia.innerHTML = isVideo
        ? `<video controls src="${esc(media)}"></video>`
        : `<img src="${esc(media)}" alt="preview">`;

      pmMedia.style.display = 'flex';
    }

    modal.classList.remove('hidden');
  }

  async function approve(id) {
    const x = data.find(r => r.id === id);
    if (!x) return;

    if (!confirm('Approve and publish this content?')) return;

    const json = await postJSON(`/admin/rejected-content/${id}/approve`);
    x.review_status = 'approved';
    render();
    alert(json.message || 'Approved.');
  }

  async function reject(id) {
    const x = data.find(r => r.id === id);
    if (!x) return;

    if (!confirm('Keep this content rejected?')) return;

    const json = await postJSON(`/admin/rejected-content/${id}/reject`);
    x.review_status = 'rejected';
    render();
    alert(json.message || 'Rejected.');
  }

  // Filters
  filterType.addEventListener('change', render);
  filterStatus.addEventListener('change', render);
  filterUser.addEventListener('input', render);

  // One click handler for all buttons
  document.addEventListener('click', (e) => {
    // Close modal (X or backdrop)
    if (e.target.matches('[data-close="1"]') || e.target.closest('[data-close="1"]')) {
      closeModal();
      return;
    }

    const previewBtn = e.target.closest('[data-preview]');
    const approveBtn = e.target.closest('[data-approve]');
    const rejectBtn = e.target.closest('[data-reject]');

    if (previewBtn) {
      const id = parseInt(previewBtn.getAttribute('data-preview'), 10);
      const x = data.find(r => r.id === id);
      if (x) openPreview(x);
      return;
    }

    if (approveBtn) {
      const id = parseInt(approveBtn.getAttribute('data-approve'), 10);
      approve(id).catch(err => alert(err.message));
      return;
    }

    if (rejectBtn) {
      const id = parseInt(rejectBtn.getAttribute('data-reject'), 10);
      reject(id).catch(err => alert(err.message));
      return;
    }
  });

  // ESC closes modal
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
      closeModal();
    }
  });

  render();
})();
