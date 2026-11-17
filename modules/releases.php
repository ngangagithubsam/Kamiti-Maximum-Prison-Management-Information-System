<!-- RELEASE MANAGEMENT -->
<div class="page" id="page-releases">
  <style>
    .table-header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 14px; margin-bottom: 12px; }
    .table-header .table-title { font-size: 20px; font-weight: 700; margin: 0; }
    .table-header .actions-wrapper { display: flex; gap: 10px; align-items: center; }
    .filter-bar { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 16px; }
    .filter-bar .filter-group { display: flex; align-items: center; gap: 8px; }
    .filter-bar .search-box { display: flex; align-items: center; gap: 8px; flex: 1 1 260px; min-width: 220px; }
    .filter-bar .search-box input { flex: 1; }
    .modal-overlay#modal-release-request .modal { max-width: 720px; }
  </style>
  <div class="table-card">
    <div class="table-header">
      <div class="table-title">Release Requests</div>
      <div class="actions-wrapper"><button class="btn btn-primary btn-sm" onclick="openReleaseRequestModal()">&#x002B; New Release Request</button></div>
    </div>
    <div class="filter-bar">
      <div class="filter-group"><label>Status:</label>
        <select class="form-select" id="release-status-filter" onchange="renderReleases()"><option value="">All</option><option>Pending</option><option>Approved</option><option>Declined</option></select>
      </div>
      <div class="filter-group"><label>Type:</label>
        <select class="form-select" id="release-type-filter" onchange="renderReleases()"><option value="">All</option><option>Sentence Complete</option><option>Parole</option><option>Bail</option><option>Court Order</option><option>Death</option><option>Transfer Out</option><option>Other</option></select>
      </div>
      <div class="filter-group"><label>From:</label><input type="date" id="release-from" onchange="renderReleases()"><label>To:</label><input type="date" id="release-to" onchange="renderReleases()"></div>
      <div class="search-box"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
        <input type="text" placeholder="Search release requests..." id="release-search" oninput="renderReleases()"></div>
      <div class="filter-group"><button class="btn btn-secondary btn-sm" onclick="clearReleaseFilters()">Clear Filters</button></div>
      <div class="filter-group">
        <button class="btn btn-export" onclick="exportReleases('csv')">CSV</button>
        <button class="btn btn-export" onclick="exportReleases('excel')">Excel</button>
        <button class="btn btn-export" onclick="exportReleases('pdf')">PDF</button>
        <button class="btn btn-export" onclick="exportReleases('word')">Word</button>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>Request ID</th>
          <th>Prisoner</th>
          <th>Request Type</th>
          <th>Requested Release</th>
          <th>Status</th>
          <th>Requested On</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="release-tbody"></tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="modal-release-request">
  <div class="modal modal-lg">
    <button class="modal-close" onclick="closeReleaseRequestModal()">✕</button>
    <div class="modal-title">Schedule Release Request</div>
    <div class="form-group"><label class="form-label">Prisoner *</label><select class="form-select" id="rr-prisoner"></select></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Request Type *</label><select class="form-select" id="rr-request-type"><option>Sentence Complete</option><option>Parole</option><option>Bail</option><option>Court Order</option><option>Death</option><option>Transfer Out</option><option>Other</option></select></div>
      <div class="form-group"><label class="form-label">Requested Release Date</label><input type="date" class="form-input" id="rr-requested-release-date"></div>
    </div>
    <div class="form-group"><label class="form-label">Reason *</label><textarea class="form-textarea" id="rr-reason" style="min-height:100px"></textarea></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Contact Phone</label><input type="text" class="form-input" id="rr-contact-phone"></div>
      <div class="form-group"><label class="form-label">Bond Amount</label><input type="number" class="form-input" id="rr-bond-amount" step="0.01"></div>
    </div>
    <div class="form-group"><label class="form-label">Notes</label><textarea class="form-textarea" id="rr-notes" style="min-height:80px"></textarea></div>
    <div class="modal-footer"><button class="btn btn-secondary" onclick="closeReleaseRequestModal()">Cancel</button><button class="btn btn-primary" onclick="saveReleaseRequest()">Submit Request</button></div>
  </div>
</div>

<script>
const RELEASE_API = 'api/releases.php';

function clearReleaseFilters() {
  document.getElementById('release-status-filter').value = '';
  document.getElementById('release-type-filter').value = '';
  document.getElementById('release-from').value = '';
  document.getElementById('release-to').value = '';
  document.getElementById('release-search').value = '';
  renderReleases();
}

async function fetchReleases(filters = {}) {
  const res = await apiCall(RELEASE_API, { action: 'list', filters });
  if (!res.success) throw new Error(res.msg || 'Failed to load releases');
  return res.data || [];
}

async function renderReleases() {
  const status = document.getElementById('release-status-filter').value;
  const type = document.getElementById('release-type-filter').value;
  const from = document.getElementById('release-from').value;
  const to = document.getElementById('release-to').value;
  const q = (document.getElementById('release-search').value || '').trim();
  const tbody = document.getElementById('release-tbody');
  try {
    const data = await fetchReleases({ status, type, from, to, q });
    const isAdminRole = isAdmin();
    const canDelete = canPerformSync('release_requests','delete');
    tbody.innerHTML = data.map(r => {
      const actions = [];
      actions.push(renderEntryAction('release_requests','release',r.id, 'View'));
      if (isAdminRole && r.status === 'Pending') {
        actions.push(`<button class="btn btn-success btn-sm" onclick="approveRelease(${r.id})">Approve</button>`);
        actions.push(`<button class="btn btn-danger btn-sm" onclick="declineReleasePrompt(${r.id})">Decline</button>`);
      }
      if (canDelete && r.status === 'Pending') {
        actions.push(renderDeleteAction('release_requests', r.id, r.requestId || `Request ${r.id}`));
      }
      return `<tr>
        <td class="text-mono text-accent">${r.requestId || '—'}</td>
        <td style="color:var(--text)">${r.prisonerName || r.prisoner_id || '—'}</td>
        <td>${r.request_type || '—'}</td>
        <td>${fmtDate(r.requested_release_date)}</td>
        <td>${statusBadge(r.status)}</td>
        <td>${fmtDateTime(r.requested_on)}</td>
        <td style="white-space:nowrap">${actions.join(' ')}</td>
      </tr>`;
    }).join('') || `<tr><td colspan="7" style="text-align:center;color:var(--text3);padding:40px">No release requests found</td></tr>`;
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:var(--text3);padding:40px">Error loading releases</td></tr>`;
    showNotif(err.message || 'Failed to load releases', 'error');
  }
}

// Open details modal (lightweight) - reuse existing modal overlay pattern
async function openReleaseDetails(id) {
  const res = await apiCall(RELEASE_API, { action: 'get', id });
  if (!res.success) return showNotif(res.msg || 'Not found', 'error');
  const r = res.data;
  // Build simple details modal HTML
  const html = `<div style="padding:16px;max-width:680px">
    <h3 style="margin-top:0">Release Request ${r.requestId || ''}</h3>
    <p><strong>Prisoner:</strong> ${r.prisonerName || r.prisoner_id}</p>
    <p><strong>Type:</strong> ${r.request_type}</p>
    <p><strong>Requested Release:</strong> ${fmtDate(r.requested_release_date)}</p>
    <p><strong>Reason:</strong> ${escapeHtml(r.reason || '')}</p>
    <p><strong>Notes:</strong> ${escapeHtml(r.notes || '')}</p>
    <p><strong>Status:</strong> ${statusBadge(r.status)}</p>
    <p><strong>Requested On:</strong> ${fmtDateTime(r.requested_on)}</p>
    ${r.status === 'Declined' && r.decline_reason ? `<p><strong>Decline reason:</strong> ${escapeHtml(r.decline_reason)}</p>` : ''}
    <div style="text-align:right;margin-top:12px"><button class="btn btn-secondary" onclick="closeModalInline()">Close</button></div>
  </div>`;
  openInlineModal(html);
}

function openInlineModal(contentHtml) {
  // create overlay
  let ov = document.getElementById('inline-modal-overlay');
  if (!ov) {
    ov = document.createElement('div'); ov.id = 'inline-modal-overlay'; ov.className = 'modal-overlay'; document.body.appendChild(ov);
  }
  ov.innerHTML = `<div class="modal modal-lg"> <button class="modal-close" onclick="closeModalInline()">✕</button>${contentHtml}</div>`;
  ov.style.display = 'block';
}

function closeModalInline() {
  const ov = document.getElementById('inline-modal-overlay'); if (ov) ov.style.display = 'none';
}

function escapeHtml(s) { return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

async function approveRelease(id) {
  if (!confirm('Approve this release request and mark prisoner as Released?')) return;
  const res = await apiCall(RELEASE_API, { action: 'approve', id });
  if (!res.success) return showNotif(res.msg || 'Failed', 'error');
  showNotif('Request approved', 'success');
  renderReleases();
  if (typeof renderDashboard === 'function') renderDashboard();
}

function declineReleasePrompt(id) {
  const reason = prompt('Enter decline reason:');
  if (reason === null) return; // cancelled
  declineRelease(id, reason.trim());
}

async function declineRelease(id, reason) {
  if (!reason) return showNotif('Decline reason required', 'error');
  const res = await apiCall(RELEASE_API, { action: 'decline', id, reason });
  if (!res.success) return showNotif(res.msg || 'Failed', 'error');
  showNotif('Request declined', 'success');
  renderReleases();
  if (typeof renderDashboard === 'function') renderDashboard();
}

// Delete handled via generic delete helper renderDeleteAction

function openReleaseRequestModal() {
  const modal = document.getElementById('modal-release-request');
  const prisonerSelect = document.getElementById('rr-prisoner');
  if (modal) modal.classList.add('open');
  if (prisonerSelect) {
    dbGetAll('prisoners').then(prisoners => {
      prisonerSelect.innerHTML = prisoners.map(p => `<option value="${p.id}">${p.prisonerId || p.id} - ${p.firstname || ''} ${p.lastname || ''}</option>`).join('');
      if (!prisoners.length) prisonerSelect.innerHTML = '<option value="">No prisoners available</option>';
    }).catch(() => {
      prisonerSelect.innerHTML = '<option value="">Unable to load prisoners</option>';
    });
  }
  ['rr-request-type','rr-requested-release-date','rr-reason','rr-contact-phone','rr-bond-amount','rr-notes'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
}

function closeReleaseRequestModal() {
  const modal = document.getElementById('modal-release-request');
  if (modal) modal.classList.remove('open');
}

async function saveReleaseRequest() {
  const prisonerId = document.getElementById('rr-prisoner').value;
  const requestType = document.getElementById('rr-request-type').value;
  const requestedReleaseDate = document.getElementById('rr-requested-release-date').value;
  const reason = document.getElementById('rr-reason').value.trim();
  const contactPhone = document.getElementById('rr-contact-phone').value.trim();
  const bondAmount = document.getElementById('rr-bond-amount').value;
  const notes = document.getElementById('rr-notes').value.trim();

  if (!prisonerId || !reason) {
    return showNotif('Prisoner and reason are required', 'error');
  }

  try {
    await submitNewRelease({
      prisoner_id: prisonerId,
      request_type: requestType,
      requested_release_date: requestedReleaseDate || null,
      reason,
      contact_phone: contactPhone || null,
      bond_amount: bondAmount ? parseFloat(bondAmount) : null,
      notes: notes || null
    });
    closeReleaseRequestModal();
    showNotif('Release request submitted', 'success');
    renderReleases();
    if (typeof renderDashboard === 'function') renderDashboard();
  } catch (err) {
    showNotif(err.message || 'Unable to submit release request', 'error');
  }
}

async function submitNewRelease(record) {
  const res = await apiCall(RELEASE_API, { action: 'create', record });
  if (!res.success) throw new Error(res.msg || 'Create failed');
  return res.data;
}

// Export that respects filters
async function exportReleases(format) {
  const status = document.getElementById('release-status-filter').value;
  const type = document.getElementById('release-type-filter').value;
  const from = document.getElementById('release-from').value;
  const to = document.getElementById('release-to').value;
  const q = (document.getElementById('release-search').value || '').trim();
  const res = await apiCall(RELEASE_API, { action: 'export', filters: { status, type, from, to, q } });
  if (!res.success) return showNotif(res.msg || 'Export failed', 'error');
  const data = res.data || [];
  const datePart = new Date().toISOString().split('T')[0];
  if (format === 'csv') return exportToCSV(data, `release_requests_${datePart}.csv`);
  if (format === 'excel') return exportToExcel(data, `release_requests_${datePart}.xls`);
  if (format === 'pdf') return exportToPDF(data, `release_requests_${datePart}.pdf`);
  if (format === 'word') return exportToWord(data, `release_requests_${datePart}.doc`);
}

// Initialize when page loads
document.addEventListener('appReady', () => {
  if (document.getElementById('page-releases')) {
    renderReleases();
  }
});
</script>

