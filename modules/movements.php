<!-- MOVEMENTS -->
      <div class="page" id="page-movements">
        <div class="stats-grid-3" style="margin-bottom:18px">
          <div class="stat-card"><div class="stat-label">Check-Ins Today</div><div class="stat-value text-accent" id="stat-checkin">0</div></div>
          <div class="stat-card"><div class="stat-label">Check-Outs Today</div><div class="stat-value" style="color:var(--green)" id="stat-checkout">0</div></div>
          <div class="stat-card"><div class="stat-label">Total Movements</div><div class="stat-value" style="color:var(--purple)" id="stat-movements">0</div></div>
        </div>
        <div class="table-card">
          <div class="table-header"><div class="table-title">Movement Log</div>
            <div class="search-box"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg><input type="text" placeholder="Search movements..." id="movement-search" oninput="renderMovements()"></div>
          </div>
          <table><thead><tr><th>Log ID</th><th>Prisoner</th><th>Type</th><th>Location</th><th>Reason</th><th>Officer</th><th>Date</th><th>Actions</th></tr></thead><tbody id="movement-tbody"></tbody></table>
        </div>
      </div>
<script>
async function renderMovements() {
  const movements = await dbGetAll('movements');
  const q = (document.getElementById('movement-search')?.value||'').toLowerCase();
  const today = new Date().toDateString();
  document.getElementById('stat-checkin').textContent = movements.filter(m=>m.type==='Check-In'&&new Date(m.moveDate||m.created_at).toDateString()===today).length;
  document.getElementById('stat-checkout').textContent = movements.filter(m=>m.type==='Check-Out'&&new Date(m.moveDate||m.created_at).toDateString()===today).length;
  document.getElementById('stat-movements').textContent = movements.length;
  const tbody = document.getElementById('movement-tbody');
  const filtered = movements.filter(m => !q || `${m.prisonerName} ${m.logId} ${m.type}`.toLowerCase().includes(q));
  tbody.innerHTML = filtered.map(m=>{
    const actions = [renderEntryAction('movements','movement',m.id, 'View')];
    if (canPerformSync('movements', 'save')) actions.push(renderEditAction('movements','movement',m.id, 'Edit'));
    if (canPerformSync('movements', 'delete')) actions.push(renderDeleteAction('movements',m.id,'Movement'));
    return `<tr><td class="text-mono text-accent">${m.logId}</td><td style="color:var(--text)">${m.prisonerName}</td><td>${statusBadge(m.type)}</td><td>${m.location||'—'}</td><td>${m.reason||'—'}</td><td>${m.officer||'—'}</td><td>${fmtDateTime(m.moveDate)}</td><td>${actions.join(' ')}</td></tr>`;
  }).join('') || `<tr><td colspan="8" style="text-align:center;color:var(--text3);padding:40px">No movements</td></tr>`;
}


async function saveMovement() {
  const prisoner = document.getElementById('m-prisoner').value;
  if (!prisoner) return showNotif('Prisoner required', 'error');
  const prisoners = await dbGetAll('prisoners');
  await dbPut('movements', {
    id: 0,
    prisoner, prisonerName: prisonerFullName(prisoner, prisoners),
    type: document.getElementById('m-type').value,
    location: document.getElementById('m-location').value.trim(),
    reason: document.getElementById('m-reason').value.trim(),
    officer: document.getElementById('m-officer').value.trim(),
    date: document.getElementById('m-date').value,
    notes: document.getElementById('m-notes').value.trim(),
  });
  closeModal('movement'); showNotif('Movement logged', 'success'); renderMovements();
}


async function openModal_movement(id = null) {
  editingId['movement'] = id;
  document.getElementById('movement-modal-title').textContent = 'Log Movement';
  const prisoners = await dbGetAll('prisoners');
  const sel = document.getElementById('m-prisoner');
  if (sel) sel.innerHTML = prisoners.map(p => `<option value="${p.prisonerId}">${p.prisonerId} - ${p.firstname} ${p.lastname}</option>`).join('');
  const d = document.getElementById('m-date'); if (d) d.value = new Date().toISOString().slice(0,16);
  const off = document.getElementById('m-officer'); if (off) off.value = currentUser?.name || '';
  ['location','reason','notes'].forEach(f => { const el = document.getElementById('m-'+f); if(el) el.value=''; });
}

</script>


<!-- MOVEMENT MODAL -->
<div class="modal-overlay" id="modal-movement">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('movement')">✕</button>
    <div class="modal-title" id="movement-modal-title">Log Movement</div>
    <div class="form-group"><label class="form-label">Prisoner *</label><select class="form-select" id="m-prisoner"></select></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Type</label><select class="form-select" id="m-type"><option>Check-In</option><option>Check-Out</option><option>Court Appearance</option><option>Medical</option><option>Transfer</option><option>Other</option></select></div>
      <div class="form-group"><label class="form-label">Location</label><input type="text" class="form-input" id="m-location"></div>
    </div>
    <div class="form-group"><label class="form-label">Reason</label><input type="text" class="form-input" id="m-reason"></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Authorized Officer</label><input type="text" class="form-input" id="m-officer"></div>
      <div class="form-group"><label class="form-label">Date & Time</label><input type="datetime-local" class="form-input" id="m-date"></div>
    </div>
    <div class="form-group"><label class="form-label">Notes</label><textarea class="form-textarea" id="m-notes"></textarea></div>
    <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('movement')">Cancel</button><button class="btn btn-primary" onclick="saveMovement()">Log Movement</button></div>
  </div>
</div>

