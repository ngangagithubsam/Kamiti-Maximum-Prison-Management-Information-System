<!-- VISITATIONS -->
      <div class="page" id="page-visitations">
        <div class="table-card">
          <div class="table-header"><div class="table-title">Visitations</div>
            <div class="search-box"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg><input type="text" placeholder="Search visitations..." id="visit-search" oninput="renderVisitations()"></div>
            <button class="btn-export" type="button" onclick="exportData('visitations','csv')" title="Export visitation records as CSV">Export CSV</button>
          </div>
          <table><thead><tr><th>Visit ID</th><th>Prisoner</th><th>Visitor</th><th>Relationship</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead><tbody id="visit-tbody"></tbody></table>
        </div>
      </div>
<script>
async function renderVisitations() {
  const visits = await dbGetAll('visitations');
  const q = (document.getElementById('visit-search')?.value||'').toLowerCase();
  const tbody = document.getElementById('visit-tbody');
  let filtered = getFilteredData(visits, 'visit');
  filtered = filtered.filter(v => !q || `${v.prisonerName} ${v.visitorname} ${v.visitId}`.toLowerCase().includes(q));
  tbody.innerHTML = filtered.map(v=>{
    const actions = [renderEntryAction('visitations','visit',v.id, 'View')];
    if (canPerformSync('visitations', 'save')) actions.push(renderEditAction('visitations','visit',v.id, 'Edit'));
    if (canPerformSync('visitations', 'delete')) actions.push(renderDeleteAction('visitations',v.id,v.visitId));
    return `<tr><td class="text-mono text-accent">${v.visitId}</td><td style="color:var(--text)">${v.prisonerName}</td><td>${v.visitorname}</td><td>${v.relationship||'—'}</td><td>${fmtDateTime(v.visitDate)}</td><td>${statusBadge(v.status)}</td><td>${actions.join(' ')}</td></tr>`;
  }).join('') || `<tr><td colspan="7" style="text-align:center;color:var(--text3);padding:40px">No visits found</td></tr>`;
}


async function saveVisitation() {
  const prisoner = document.getElementById('v-prisoner').value;
  const visitorname = document.getElementById('v-visitorname').value.trim();
  if (!prisoner || !visitorname) return showNotif('Prisoner and visitor name required', 'error');
  const prisoners = await dbGetAll('prisoners');
  const isEdit = !!editingId['visit'];
  await dbPut('visitations', {
    id: isEdit ? editingId['visit'] : 0,
    prisoner, prisonerName: prisonerFullName(prisoner, prisoners),
    visitorname, relationship: document.getElementById('v-relationship').value,
    visitorid: document.getElementById('v-visitorid').value.trim(),
    phone: document.getElementById('v-phone').value.trim(),
    date: document.getElementById('v-date').value,
    duration: parseInt(document.getElementById('v-duration').value)||30,
    type: document.getElementById('v-type').value,
    status: document.getElementById('v-status').value,
    notes: document.getElementById('v-notes').value.trim(),
  });
  closeModal('visit'); showNotif('Visitation saved', 'success'); renderVisitations();
}


async function openModal_visit(id = null) {
  editingId['visit'] = id;
  document.getElementById('visit-modal-title').textContent = id ? 'Edit Visit Record' : 'Schedule Visitation';
  const prisoners = await dbGetAll('prisoners');
  const sel = document.getElementById('v-prisoner');
  if (sel) sel.innerHTML = prisoners.filter(p => p.status === 'Active').map(p => `<option value="${p.prisonerId}">${p.prisonerId} - ${p.firstname} ${p.lastname}</option>`).join('');
  if (!id) {
    const d = document.getElementById('v-date'); if (d) d.value = new Date().toISOString().slice(0,16);
  }
  if (id) {
    const vs = await dbGetAll('visitations');
    const v = vs.find(v => v.id == id);
    if (v) {
      const psel = document.getElementById('v-prisoner'); if (psel) psel.value = v.prisoner;
      ['visitorname','relationship','visitorid','phone','duration','type','status','notes'].forEach(f => { const el = document.getElementById('v-'+f); if (el) el.value = v[f]||''; });
      const d = document.getElementById('v-date'); if (d) d.value = v.visitDate ? v.visitDate.slice(0,16) : '';
    }
  }
}

</script>


<!-- VISIT MODAL -->
<div class="modal-overlay" id="modal-visit">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('visit')">✕</button>
    <div class="modal-title" id="visit-modal-title">Schedule Visitation</div>
    <div class="form-group"><label class="form-label">Prisoner *</label><select class="form-select" id="v-prisoner"></select></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Visitor Name *</label><input type="text" class="form-input" id="v-visitorname"></div>
      <div class="form-group"><label class="form-label">Relationship</label><select class="form-select" id="v-relationship"><option>Family</option><option>Lawyer</option><option>Friend</option><option>Other</option></select></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Visitor ID</label><input type="text" class="form-input" id="v-visitorid"></div>
      <div class="form-group"><label class="form-label">Phone</label><input type="text" class="form-input" id="v-phone"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Date & Time *</label><input type="datetime-local" class="form-input" id="v-date"></div>
      <div class="form-group"><label class="form-label">Duration (min)</label><input type="number" class="form-input" id="v-duration" value="30" min="15"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Type</label><select class="form-select" id="v-type"><option>In-Person</option><option>Virtual</option><option>Legal</option></select></div>
      <div class="form-group"><label class="form-label">Status</label><select class="form-select" id="v-status"><option>Scheduled</option><option>Completed</option><option>Denied</option><option>Cancelled</option></select></div>
    </div>
    <div class="form-group"><label class="form-label">Notes</label><textarea class="form-textarea" id="v-notes"></textarea></div>
    <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('visit')">Cancel</button><button class="btn btn-primary" onclick="saveVisitation()">Save Visit</button></div>
  </div>
</div>

