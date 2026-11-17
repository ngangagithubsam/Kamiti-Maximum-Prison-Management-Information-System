<!-- INCIDENTS -->
      <div class="page" id="page-incidents">
        <div class="table-card">
          <div class="table-header"><div class="table-title">Incident Reports</div>
            <div class="filter-group"><label>Severity:</label><select class="form-select" id="incident-severity-filter" onchange="renderIncidents()"><option value="">All</option><option>Low</option><option>Medium</option><option>High</option><option>Critical</option></select></div>
            <div class="search-box"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg><input type="text" placeholder="Search incidents..." id="incident-search" oninput="renderIncidents()"></div>
          </div>
          <table><thead><tr><th>Incident ID</th><th>Type</th><th>Prisoner</th><th>Location</th><th>Severity</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead><tbody id="incident-tbody"></tbody></table>
        </div>
      </div>
<script>
async function renderIncidents() {
  const incidents = await dbGetAll('incidents');
  const q = (document.getElementById('incident-search')?.value||'').toLowerCase();
  const sf = document.getElementById('incident-severity-filter')?.value||'';
  const tbody = document.getElementById('incident-tbody');
  const filtered = incidents.filter(i => (!sf||i.severity===sf) && (!q||`${i.prisonerName} ${i.type} ${i.incidentId}`.toLowerCase().includes(q)));
  tbody.innerHTML = filtered.map(i=>{
    const actions = [renderEntryAction('incidents','incident',i.id, 'View')];
    if (canPerformSync('incidents', 'save')) actions.push(renderEditAction('incidents','incident',i.id, 'Edit'));
    if (canPerformSync('incidents', 'delete')) actions.push(renderDeleteAction('incidents',i.id,i.incidentId));
    return `<tr><td class="text-mono text-accent">${i.incidentId}</td><td>${i.type}</td><td style="color:var(--text)">${i.prisonerName}</td><td>${i.location||'—'}</td><td>${severityBadge(i.severity)}</td><td>${fmtDate(i.incidentDate)}</td><td>${statusBadge(i.status)}</td><td>${actions.join(' ')}</td></tr>`;
  }).join('') || `<tr><td colspan="8" style="text-align:center;color:var(--text3);padding:40px">No incidents</td></tr>`;
}


async function saveIncident() {
  const prisoner = document.getElementById('i-prisoner').value;
  const desc = document.getElementById('i-description').value.trim();
  if (!prisoner || !desc) return showNotif('Prisoner and description required', 'error');
  const prisoners = await dbGetAll('prisoners');
  const isEdit = !!editingId['incident'];
  await dbPut('incidents', {
    id: isEdit ? editingId['incident'] : 0,
    prisoner, prisonerName: prisonerFullName(prisoner, prisoners),
    type: document.getElementById('i-type').value,
    severity: document.getElementById('i-severity').value,
    others: document.getElementById('i-others').value.trim(),
    date: document.getElementById('i-date').value,
    location: document.getElementById('i-location').value.trim(),
    description: desc,
    action: document.getElementById('i-action').value.trim(),
    officer: document.getElementById('i-officer').value.trim(),
    status: document.getElementById('i-status').value,
  });
  closeModal('incident'); showNotif('Incident saved', 'success'); renderIncidents();
}


async function openModal_incident(id = null) {
  editingId['incident'] = id;
  document.getElementById('incident-modal-title').textContent = id ? 'Edit Incident' : 'File Incident Report';
  const prisoners = await dbGetAll('prisoners');
  const sel = document.getElementById('i-prisoner');
  if (sel) sel.innerHTML = prisoners.map(p => `<option value="${p.prisonerId}">${p.prisonerId} - ${p.firstname} ${p.lastname}</option>`).join('');
  if (!id) {
    const d = document.getElementById('i-date'); if (d) d.value = new Date().toISOString().slice(0,16);
    const off = document.getElementById('i-officer'); if (off) off.value = currentUser?.name || '';
    ['others','location','description','action'].forEach(f => { const el = document.getElementById('i-'+f); if(el) el.value=''; });
  }
  if (id) {
    const incs = await dbGetAll('incidents');
    const inc = incs.find(i => i.id == id);
    if (inc) {
      const psel = document.getElementById('i-prisoner'); if (psel) psel.value = inc.prisoner;
      ['type','severity','others','location','description','action','officer','status'].forEach(f => { const el = document.getElementById('i-'+f); if (el) el.value = inc[f] || ''; });
      const d = document.getElementById('i-date'); if (d) d.value = (inc.incidentDate||'').slice(0,16);
    }
  }
}

</script>



<!-- INCIDENT MODAL -->
<div class="modal-overlay" id="modal-incident">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('incident')">✕</button>
    <div class="modal-title" id="incident-modal-title">File Incident Report</div>
    <div class="form-group"><label class="form-label">Prisoner *</label><select class="form-select" id="i-prisoner"></select></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Type</label><select class="form-select" id="i-type"><option>Assault</option><option>Contraband</option><option>Escape Attempt</option><option>Medical</option><option>Damage to Property</option><option>Other</option></select></div>
      <div class="form-group"><label class="form-label">Severity</label><select class="form-select" id="i-severity"><option>Low</option><option>Medium</option><option>High</option><option>Critical</option></select></div>
    </div>
    <div class="form-group"><label class="form-label">Others Involved</label><input type="text" class="form-input" id="i-others"></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Date & Time</label><input type="datetime-local" class="form-input" id="i-date"></div>
      <div class="form-group"><label class="form-label">Location</label><input type="text" class="form-input" id="i-location"></div>
    </div>
    <div class="form-group"><label class="form-label">Description *</label><textarea class="form-textarea" id="i-description"></textarea></div>
    <div class="form-group"><label class="form-label">Action Taken</label><textarea class="form-textarea" id="i-action"></textarea></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Reporting Officer</label><input type="text" class="form-input" id="i-officer"></div>
      <div class="form-group"><label class="form-label">Status</label><select class="form-select" id="i-status"><option>Open</option><option>Under Review</option><option>Resolved</option><option>Closed</option></select></div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('incident')">Cancel</button><button class="btn btn-primary" onclick="saveIncident()">Save Incident</button></div>
  </div>
</div>

