// ============================================================
// VIEW FUNCTIONS FOR ALL MODULES
// Comprehensive read-only modal handlers
// ============================================================

// MOVEMENTS VIEW
async function openMovementView(id) {
  const modal = document.getElementById('modal-movement-view');
  const movements = await dbGetAll('movements');
  const m = movements.find(m => m.id == id);
  if (!m) return showNotif('Movement log not found', 'error');

  document.getElementById('movement-view-subtitle').textContent = `Log ID: ${m.logId || '—'}`;
  document.getElementById('movement-view-content').innerHTML = `
    <div class="prisoner-profile-grid">
      ${prisonerProfileField('Log ID', m.logId || '—')}
      ${prisonerProfileField('Prisoner', m.prisonerName || '—')}
      ${prisonerProfileField('Type', statusBadge(m.type || '—'))}
    </div>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-section-title">Movement Details</div>
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Location', m.location || '—')}
        ${prisonerProfileField('Reason', m.reason || '—')}
        ${prisonerProfileField('Authorized Officer', m.officer || '—')}
      </div>
    </section>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-section-title">Timestamp</div>
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Date & Time', fmtDateTime(m.moveDate || m.created_at) || '—')}
        ${prisonerProfileField('Recorded', fmtDateTime(m.created_at) || '—')}
      </div>
    </section>
    ${m.notes ? `<section class="prisoner-profile-section"><div class="prisoner-profile-section-title">Notes</div><div class="prisoner-profile-related"><div class="prisoner-profile-related-item">${m.notes}</div></div></section>` : ''}
  `;
  modal.classList.add('open');
  modal.setAttribute('aria-hidden', 'false');
}
function closeMovementView() {
  const modal = document.getElementById('modal-movement-view');
  modal.classList.remove('open');
  modal.setAttribute('aria-hidden', 'true');
}

// TRANSFER VIEW
async function openTransferView(id) {
  const modal = document.getElementById('modal-transfer-view');
  try {
    const res = await fetch('./api/transfer_action.php?action=list');
    const result = await res.json();
    const transfers = result.success ? (result.data || []) : [];
    const t = transfers.find(t => t.id == id);
    if (!t) return showNotif('Transfer request not found', 'error');

    document.getElementById('transfer-view-subtitle').textContent = `Request ID: ${t.id || '—'}`;
    document.getElementById('transfer-view-content').innerHTML = `
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Prisoner', t.prisonerName || '—')}
        ${prisonerProfileField('Status', statusBadge(t.status || 'Pending'))}
      </div>
      <section class="prisoner-profile-section">
        <div class="prisoner-profile-section-title">Transfer Details</div>
        <div class="prisoner-profile-grid">
          ${prisonerProfileField('From Facility', t.from_facility || '—')}
          ${prisonerProfileField('To Facility', t.to_facility || '—')}
          ${prisonerProfileField('Reason', t.reason || '—')}
        </div>
      </section>
      ${t.decline_reason ? `<section class="prisoner-profile-section"><div class="prisoner-profile-section-title">Decline Reason</div><div class="prisoner-profile-related"><div class="prisoner-profile-related-item">${t.decline_reason}</div></div></section>` : ''}
    `;
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
  } catch (error) {
    showNotif('Unable to load transfer details', 'error');
  }
}
function closeTransferView() {
  const modal = document.getElementById('modal-transfer-view');
  modal.classList.remove('open');
  modal.setAttribute('aria-hidden', 'true');
}

// CELL VIEW
async function openCellView(id) {
  const modal = document.getElementById('modal-cell-view');
  const cells = await dbGetAll('cells');
  const c = cells.find(c => c.id == id);
  if (!c) return showNotif('Cell not found', 'error');

  document.getElementById('cell-view-subtitle').textContent = `Cell: ${c.cellNumber || '—'}`;
  document.getElementById('cell-view-content').innerHTML = `
    <div class="prisoner-profile-grid">
      ${prisonerProfileField('Cell Number', c.cellNumber || '—')}
      ${prisonerProfileField('Block', c.block || '—')}
      ${prisonerProfileField('Status', statusBadge(c.status || 'Available'))}
    </div>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-section-title">Cell Capacity</div>
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Capacity', c.capacity || '—')}
        ${prisonerProfileField('Currently Occupied', c.occupied || '0')}
        ${prisonerProfileField('Available Spaces', Math.max(0, (c.capacity || 0) - (c.occupied || 0)))}
      </div>
    </section>
    ${c.notes ? `<section class="prisoner-profile-section"><div class="prisoner-profile-section-title">Notes</div><div class="prisoner-profile-related"><div class="prisoner-profile-related-item">${c.notes}</div></div></section>` : ''}
  `;
  modal.classList.add('open');
  modal.setAttribute('aria-hidden', 'false');
}
function closeCellView() {
  const modal = document.getElementById('modal-cell-view');
  modal.classList.remove('open');
  modal.setAttribute('aria-hidden', 'true');
}

// COURT VIEW
async function openCourtView(id) {
  const modal = document.getElementById('modal-court-view');
  const cases = await dbGetAll('court');
  const c = cases.find(c => c.id == id);
  if (!c) return showNotif('Court case not found', 'error');

  const titleEl = document.getElementById('court-view-title');
  const subtitleEl = document.getElementById('court-view-subtitle');
  const statusEl = document.getElementById('court-view-status');

  if (titleEl) titleEl.textContent = 'View Court Case';
  if (subtitleEl) subtitleEl.textContent = `Case: ${c.casenumber || '—'}`;
  if (statusEl) statusEl.innerHTML = statusBadge(c.status || 'Ongoing');

  document.getElementById('court-view-content').innerHTML = `
    <div class="prisoner-summary-grid">
      ${prisonerProfileField('Case Number', c.casenumber || '—').replace('prisoner-profile-field', 'prisoner-summary-card').replace('prisoner-profile-label', 'prisoner-summary-label').replace('prisoner-profile-value', 'prisoner-summary-value')}
      ${prisonerProfileField('Prisoner', c.prisonerName || '—').replace('prisoner-profile-field', 'prisoner-summary-card').replace('prisoner-profile-label', 'prisoner-summary-label').replace('prisoner-profile-value', 'prisoner-summary-value')}
      ${prisonerProfileField('Court', c.court || '—').replace('prisoner-profile-field', 'prisoner-summary-card').replace('prisoner-profile-label', 'prisoner-summary-label').replace('prisoner-profile-value', 'prisoner-summary-value')}
    </div>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-section-title">Case Information</div>
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Charges', c.charges || '—')}
        ${prisonerProfileField('Status', statusBadge(c.status || 'Ongoing'))}
        ${prisonerProfileField('Verdict', c.verdict || 'Pending')}
      </div>
    </section>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-section-title">Court Officials</div>
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Judge', c.judge || '—')}
        ${prisonerProfileField('Prosecutor', c.prosecutor || '—')}
        ${prisonerProfileField('Defense Attorney', c.defense || '—')}
      </div>
    </section>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-section-title">Dates & Sentence</div>
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Filing Date', fmtDate(c.filingdate) || '—')}
        ${prisonerProfileField('Next Hearing', fmtDate(c.hearingdate) || '—')}
        ${prisonerProfileField('Sentence Imposed', c.sentence || '—')}
      </div>
    </section>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Fine / Bail (KSH)', c.fine ? formatCurrency(c.fine) : '—')}
      </div>
    </section>
    ${c.notes ? `<section class="prisoner-profile-section"><div class="prisoner-profile-section-title">Notes</div><div class="prisoner-profile-related"><div class="prisoner-profile-related-item">${c.notes}</div></div></section>` : ''}
  `;
  modal.classList.add('open');
  modal.setAttribute('aria-hidden', 'false');
}
function closeCourtView() {
  const modal = document.getElementById('modal-court-view');
  modal.classList.remove('open');
  modal.setAttribute('aria-hidden', 'true');
}

// REPORT VIEW
async function openReportView(id) {
  const modal = document.getElementById('modal-report-view');
  const reports = await dbGetAll('reports');
  const r = reports.find(r => r.id == id);
  if (!r) return showNotif('Report not found', 'error');

  document.getElementById('report-view-subtitle').textContent = `Report ID: ${r.reportId || '—'}`;
  document.getElementById('report-view-content').innerHTML = `
    <div class="prisoner-profile-grid">
      ${prisonerProfileField('Report ID', r.reportId || '—')}
      ${prisonerProfileField('Title', r.title || '—')}
      ${prisonerProfileField('Type', r.type || '—')}
    </div>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-section-title">Report Details</div>
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Author', r.author || '—')}
        ${prisonerProfileField('Department', r.department || '—')}
        ${prisonerProfileField('Status', statusBadge(r.status || 'Pending'))}
      </div>
    </section>
    ${r.prisoner ? `<section class="prisoner-profile-section"><div class="prisoner-profile-grid">${prisonerProfileField('Related Prisoner', r.prisoner || '—')}</div></section>` : ''}
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-section-title">Report Body</div>
      <div class="prisoner-profile-related"><div class="prisoner-profile-related-item">${r.body || 'No content'}</div></div>
    </section>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Prepared On', fmtDateTime(r.reportDate || r.created_at) || '—')}
      </div>
    </section>
  `;
  modal.classList.add('open');
  modal.setAttribute('aria-hidden', 'false');
}
function closeReportView() {
  const modal = document.getElementById('modal-report-view');
  modal.classList.remove('open');
  modal.setAttribute('aria-hidden', 'true');
}

// MEDICAL VIEW
async function openMedicalView(id) {
  const modal = document.getElementById('modal-medical-view');
  if (typeof canPerformSync === 'function' && !canPerformSync('medical', 'view')) return showNotif('You do not have permission to view medical records', 'error');

  const records = await dbGetAll('medical');
  const m = records.find(m => m.id == id);
  if (!m) return showNotif('Medical record not found', 'error');

  document.getElementById('medical-view-subtitle').textContent = `Record ID: ${m.recordId || '—'}`;
  document.getElementById('medical-view-content').innerHTML = `
    <div class="prisoner-profile-grid">
      ${prisonerProfileField('Record ID', m.recordId || '—')}
      ${prisonerProfileField('Prisoner', m.prisonerName || '—')}
      ${prisonerProfileField('Condition', m.condition || '—')}
    </div>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-section-title">Medical Details</div>
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Treatment', m.treatment || '—')}
        ${prisonerProfileField('Doctor / Medical Officer', m.doctor || '—')}
        <div class="prisoner-profile-field"><span class="prisoner-profile-label">Status</span><div class="prisoner-profile-value">${statusBadge(m.status || 'Active')}</div></div>
      </div>
    </section>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Examination Date', fmtDate(m.medDate) || '—')}
        ${prisonerProfileField('Recorded', fmtDateTime(m.created_at) || '—')}
      </div>
    </section>
    ${m.notes ? `<section class="prisoner-profile-section"><div class="prisoner-profile-section-title">Clinical Notes</div><div class="prisoner-profile-related"><div class="prisoner-profile-related-item">${m.notes}</div></div></section>` : ''}
  `;
  modal.classList.add('open');
  modal.setAttribute('aria-hidden', 'false');
}
function closeMedicalView() {
  const modal = document.getElementById('modal-medical-view');
  modal.classList.remove('open');
  modal.setAttribute('aria-hidden', 'true');
}

// SHIFT VIEW
async function openShiftView(id) {
  const modal = document.getElementById('modal-shift-view');
  const shifts = await dbGetAll('shifts');
  const s = shifts.find(s => s.id == id);
  if (!s) return showNotif('Shift not found', 'error');

  document.getElementById('shift-view-subtitle').textContent = `Shift ID: ${s.shiftId || '—'}`;
  document.getElementById('shift-view-content').innerHTML = `
    <div class="prisoner-profile-grid">
      ${prisonerProfileField('Shift ID', s.shiftId || '—')}
      ${prisonerProfileField('Staff Member', s.staffName || '—')}
      <div class="prisoner-profile-field"><span class="prisoner-profile-label">Shift Type</span><div class="prisoner-profile-value"><span class="badge ${viewShiftTypeBadge(s.shiftType)}">${s.shiftType || '—'}</span></div></div>
    </div>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-section-title">Shift Schedule</div>
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Date', fmtDate(s.shiftDate) || '—')}
        ${prisonerProfileField('Start Time', s.startTime || '—')}
        ${prisonerProfileField('End Time', s.endTime || '—')}
      </div>
    </section>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Location / Post', s.location || '—')}
        ${prisonerProfileField('Supervisor', s.supervisor || '—')}
        <div class="prisoner-profile-field"><span class="prisoner-profile-label">Status</span><div class="prisoner-profile-value">${statusBadge(s.status || 'Scheduled')}</div></div>
      </div>
    </section>
    ${s.notes ? `<section class="prisoner-profile-section"><div class="prisoner-profile-section-title">Notes</div><div class="prisoner-profile-related"><div class="prisoner-profile-related-item">${s.notes}</div></div></section>` : ''}
  `;
  modal.classList.add('open');
  modal.setAttribute('aria-hidden', 'false');
}
function closeShiftView() {
  const modal = document.getElementById('modal-shift-view');
  modal.classList.remove('open');
  modal.setAttribute('aria-hidden', 'true');
}

// INVENTORY ITEM VIEW
async function openInvItemView(id) {
  const modal = document.getElementById('modal-inv-item-view');
  const items = await dbGetAll('inventory');
  const i = items.find(i => i.id == id);
  if (!i) return showNotif('Inventory item not found', 'error');

  document.getElementById('inv-item-view-subtitle').textContent = `Item Code: ${i.itemCode || '—'}`;
  document.getElementById('inv-item-view-content').innerHTML = `
    <div class="prisoner-profile-grid">
      ${prisonerProfileField('Item Code', i.itemCode || '—')}
      ${prisonerProfileField('Item Name', i.name || '—')}
      ${prisonerProfileField('Category', i.category || '—')}
    </div>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-section-title">Stock Information</div>
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Current Quantity', i.quantity || '0')}
        ${prisonerProfileField('Unit', i.unit || 'pcs')}
        ${prisonerProfileField('Unit Cost (KSH)', i.unitCost ? formatCurrency(i.unitCost) : '—')}
      </div>
    </section>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-section-title">Stock Levels</div>
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Minimum Quantity', i.minQuantity || '5')}
        ${prisonerProfileField('Maximum Quantity', i.maxQuantity || '100')}
        <div class="prisoner-profile-field"><span class="prisoner-profile-label">Status</span><div class="prisoner-profile-value">${statusBadge(i.status || 'Available')}</div></div>
      </div>
    </section>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Supplier', i.supplier || '—')}
        ${prisonerProfileField('Storage Location', i.location || '—')}
        ${prisonerProfileField('Last Restocked', fmtDate(i.lastRestocked) || '—')}
      </div>
    </section>
    ${i.description ? `<section class="prisoner-profile-section"><div class="prisoner-profile-section-title">Description</div><div class="prisoner-profile-related"><div class="prisoner-profile-related-item">${i.description}</div></div></section>` : ''}
  `;
  modal.classList.add('open');
  modal.setAttribute('aria-hidden', 'false');
}
function closeInvItemView() {
  const modal = document.getElementById('modal-inv-item-view');
  modal.classList.remove('open');
  modal.setAttribute('aria-hidden', 'true');
}

// INVENTORY TRANSACTION VIEW
async function openInvTxnView(id) {
  const modal = document.getElementById('modal-inv-txn-view');
  const txns = await dbGetAll('inventory_transactions');
  const t = txns.find(t => t.id == id);
  if (!t) return showNotif('Transaction not found', 'error');

  document.getElementById('inv-txn-view-subtitle').textContent = `Txn ID: ${t.txnId || '—'}`;
  document.getElementById('inv-txn-view-content').innerHTML = `
    <div class="prisoner-profile-grid">
      ${prisonerProfileField('Transaction ID', t.txnId || '—')}
      ${prisonerProfileField('Item', t.itemName || t.itemCode || '—')}
      <div class="prisoner-profile-field"><span class="prisoner-profile-label">Type</span><div class="prisoner-profile-value"><span class="badge ${viewTxnBadge(t.type)}">${t.type || '—'}</span></div></div>
    </div>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-section-title">Transaction Details</div>
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Quantity', t.quantity || '0')}
        ${prisonerProfileField('Balance After', t.balanceAfter || '—')}
        ${prisonerProfileField('Issued To / From', t.issuedTo || '—')}
      </div>
    </section>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Purpose', t.purpose || '—')}
        ${prisonerProfileField('Authorized By', t.authorizedBy || '—')}
        ${prisonerProfileField('Date & Time', fmtDateTime(t.txnDate || t.created_at) || '—')}
      </div>
    </section>
    ${t.notes ? `<section class="prisoner-profile-section"><div class="prisoner-profile-section-title">Notes</div><div class="prisoner-profile-related"><div class="prisoner-profile-related-item">${t.notes}</div></div></section>` : ''}
  `;
  modal.classList.add('open');
  modal.setAttribute('aria-hidden', 'false');
}
function closeInvTxnView() {
  const modal = document.getElementById('modal-inv-txn-view');
  modal.classList.remove('open');
  modal.setAttribute('aria-hidden', 'true');
}

// REHAB PROGRAM VIEW
async function openRehabProgView(id) {
  const modal = document.getElementById('modal-rehab-prog-view');
  const progs = await dbGetAll('rehab_programs');
  const p = progs.find(p => p.id == id);
  if (!p) return showNotif('Program not found', 'error');

  document.getElementById('rehab-prog-view-subtitle').textContent = `Program ID: ${p.programId || '—'}`;
  document.getElementById('rehab-prog-view-content').innerHTML = `
    <div class="prisoner-profile-grid">
      ${prisonerProfileField('Program ID', p.programId || '—')}
      ${prisonerProfileField('Name', p.name || '—')}
      <div class="prisoner-profile-field"><span class="prisoner-profile-label">Type</span><div class="prisoner-profile-value"><span class="badge ${viewRehabBadge(p.type)}">${p.type || '—'}</span></div></div>
    </div>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-section-title">Program Information</div>
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Facilitator', p.facilitator || '—')}
        ${prisonerProfileField('Schedule', p.schedule || '—')}
        ${prisonerProfileField('Venue', p.venue || '—')}
      </div>
    </section>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Capacity', p.capacity || '—')}
        ${prisonerProfileField('Currently Enrolled', p.enrolled || '0')}
        ${prisonerProfileField('Available Spots', Math.max(0, (p.capacity || 0) - (p.enrolled || 0)))}
      </div>
    </section>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Start Date', fmtDate(p.startDate) || '—')}
        ${prisonerProfileField('End Date', fmtDate(p.endDate) || '—')}
        <div class="prisoner-profile-field"><span class="prisoner-profile-label">Status</span><div class="prisoner-profile-value">${statusBadge(p.status || 'Active')}</div></div>
      </div>
    </section>
    ${p.description ? `<section class="prisoner-profile-section"><div class="prisoner-profile-section-title">Description</div><div class="prisoner-profile-related"><div class="prisoner-profile-related-item">${p.description}</div></div></section>` : ''}
  `;
  modal.classList.add('open');
  modal.setAttribute('aria-hidden', 'false');
}
function closeRehabProgView() {
  const modal = document.getElementById('modal-rehab-prog-view');
  modal.classList.remove('open');
  modal.setAttribute('aria-hidden', 'true');
}

// REHAB ENROLLMENT VIEW
async function openRehabEnrollView(id) {
  const modal = document.getElementById('modal-rehab-enroll-view');
  const enrollments = await dbGetAll('rehab_enrollments');
  const e = enrollments.find(e => e.id == id);
  if (!e) return showNotif('Enrollment not found', 'error');

  document.getElementById('rehab-enroll-view-subtitle').textContent = `Enrollment ID: ${e.enrollId || '—'}`;
  document.getElementById('rehab-enroll-view-content').innerHTML = `
    <div class="prisoner-profile-grid">
      ${prisonerProfileField('Enrollment ID', e.enrollId || '—')}
      ${prisonerProfileField('Prisoner', e.prisonerName || '—')}
      ${prisonerProfileField('Program', e.programName || '—')}
    </div>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-section-title">Enrollment Details</div>
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Enrollment Date', fmtDate(e.enrollDate) || '—')}
        ${prisonerProfileField('Expected Completion', fmtDate(e.completionDate) || '—')}
        <div class="prisoner-profile-field"><span class="prisoner-profile-label">Status</span><div class="prisoner-profile-value">${statusBadge(e.status || 'Active')}</div></div>
      </div>
    </section>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-section-title">Progress</div>
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Progress', `${e.progress || 0}%`)}
        ${prisonerProfileField('Grade', e.grade || '—')}
      </div>
    </section>
    ${e.notes ? `<section class="prisoner-profile-section"><div class="prisoner-profile-section-title">Remarks</div><div class="prisoner-profile-related"><div class="prisoner-profile-related-item">${e.notes}</div></div></section>` : ''}
  `;
  modal.classList.add('open');
  modal.setAttribute('aria-hidden', 'false');
}
function closeRehabEnrollView() {
  const modal = document.getElementById('modal-rehab-enroll-view');
  modal.classList.remove('open');
  modal.setAttribute('aria-hidden', 'true');
}

// STAFF VIEW
async function openStaffView(id) {
  const modal = document.getElementById('modal-staff-view');
  const staff = await dbGetAll('staff');
  const s = staff.find(s => s.id == id);
  if (!s) return showNotif('Staff member not found', 'error');

  document.getElementById('staff-view-subtitle').textContent = `Staff ID: ${s.staffId || '—'}`;
  document.getElementById('staff-view-content').innerHTML = `
    <div class="prisoner-profile-grid">
      ${prisonerProfileField('Staff ID', s.staffId || '—')}
      ${prisonerProfileField('Name', `${s.firstname || ''} ${s.lastname || ''}`.trim() || '—')}
      ${prisonerProfileField('Role', s.role || '—')}
    </div>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-section-title">Employment Information</div>
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Department', s.dept || '—')}
        ${prisonerProfileField('Badge Number', s.badge || '—')}
        ${prisonerProfileField('Username', s.username || '—')}
      </div>
    </section>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-section-title">Account Status</div>
      <div class="prisoner-profile-grid">
        <div class="prisoner-profile-field"><span class="prisoner-profile-label">Status</span><div class="prisoner-profile-value">${statusBadge(s.status || 'Active')}</div></div>
        ${prisonerProfileField('Last Login', fmtDateTime(s.lastlogin) || 'Never')}
        ${prisonerProfileField('Created', fmtDateTime(s.created_at) || '—')}
      </div>
    </section>
  `;
  modal.classList.add('open');
  modal.setAttribute('aria-hidden', 'false');
}
function closeStaffView() {
  const modal = document.getElementById('modal-staff-view');
  modal.classList.remove('open');
  modal.setAttribute('aria-hidden', 'true');
}

// AUDIT LOG VIEW
async function openAuditView(id) {
  const modal = document.getElementById('modal-audit-view');
  const logs = await dbGetAll('audit_log');
  const log = logs.find(l => l.id == id);
  if (!log) return showNotif('Audit log entry not found', 'error');

  document.getElementById('audit-view-subtitle').textContent = `Audit ID: ${log.id || '—'}`;
  document.getElementById('audit-view-content').innerHTML = `
    <div class="prisoner-profile-grid">
      ${prisonerProfileField('User', log.user || '—')}
      ${prisonerProfileField('Action', log.action || '—')}
      ${prisonerProfileField('Module', log.module || '—')}
    </div>
    <section class="prisoner-profile-section">
      <div class="prisoner-profile-section-title">Audit Details</div>
      <div class="prisoner-profile-grid">
        ${prisonerProfileField('Timestamp', fmtDateTime(log.ts) || '—')}
        ${prisonerProfileField('IP Address', log.ip || '—')}
      </div>
    </section>
    ${log.details ? `<section class="prisoner-profile-section"><div class="prisoner-profile-section-title">Details</div><div class="prisoner-profile-related"><div class="prisoner-profile-related-item">${log.details}</div></div></section>` : ''}
  `;
  modal.classList.add('open');
  modal.setAttribute('aria-hidden', 'false');
}
function closeAuditView() {
  const modal = document.getElementById('modal-audit-view');
  modal.classList.remove('open');
  modal.setAttribute('aria-hidden', 'true');
}

// HELPER FUNCTIONS
function formatCurrency(amount) {
  return new Intl.NumberFormat('en-KE', { style: 'currency', currency: 'KES', minimumFractionDigits: 2 }).format(amount || 0);
}

const viewShiftTypeBadge = t => ({ Morning: 'badge-amber', Afternoon: 'badge-blue', Night: 'badge-purple', Emergency: 'badge-red' }[t] || 'badge-gray');
const viewRehabBadge = t => ({ Education: 'badge-blue', Vocational: 'badge-amber', Counseling: 'badge-purple', 'Drug Rehab': 'badge-red', Religious: 'badge-green', Sports: 'badge-teal', Other: 'badge-gray' }[t] || 'badge-gray');
const viewTxnBadge = t => ({ Issue: 'badge-amber', Return: 'badge-green', Restock: 'badge-teal', Damaged: 'badge-red', Disposed: 'badge-gray', Transfer: 'badge-blue' }[t] || 'badge-gray');
