<!-- MEDICAL -->
      <div class="page" id="page-medical">
        <div class="table-card">
          <div class="table-header"><div class="table-title">Medical Records</div>
            <div class="search-box"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg><input type="text" placeholder="Search records..." id="medical-search" oninput="renderMedical()"></div>
          </div>
          <table><thead><tr><th>Record ID</th><th>Prisoner</th><th>Condition</th><th>Doctor</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead><tbody id="medical-tbody"></tbody></table>
        </div>
      </div>
<script>
async function renderMedical() {
  const recs = await dbGetAll('medical');
  const q = (document.getElementById('medical-search')?.value||'').toLowerCase();
  const tbody = document.getElementById('medical-tbody');
  const filtered = recs.filter(r => !q || `${r.prisonerName} ${r.condition} ${r.recordId}`.toLowerCase().includes(q));
  tbody.innerHTML = filtered.map(r=>`<tr><td class="text-mono text-accent">${r.recordId}</td><td style="color:var(--text)">${r.prisonerName}</td><td>${r.condition}</td><td>${r.doctor||'—'}</td><td>${fmtDate(r.medDate)}</td><td>${statusBadge(r.status)}</td><td><button class="btn btn-secondary btn-sm" onclick="openModal('medical',${r.id})">Edit</button> <button class="btn btn-danger btn-sm" onclick="confirmDelete('medical',${r.id},'${r.condition}')">Del</button></td></tr>`).join('') || `<tr><td colspan="7" style="text-align:center;color:var(--text3);padding:40px">No records</td></tr>`;
}


async function saveMedical() {
  const prisoner = document.getElementById('med-prisoner').value;
  const condition = document.getElementById('med-condition').value.trim();
  if (!prisoner || !condition) return showNotif('Prisoner and condition required', 'error');
  const prisoners = await dbGetAll('prisoners');
  const isEdit = !!editingId['medical'];
  await dbPut('medical', {
    id: isEdit ? editingId['medical'] : 0,
    prisoner, prisonerName: prisonerFullName(prisoner, prisoners),
    condition, treatment: document.getElementById('med-treatment').value.trim(),
    doctor: document.getElementById('med-doctor').value.trim(),
    date: document.getElementById('med-date').value,
    status: document.getElementById('med-status').value,
    notes: document.getElementById('med-notes').value.trim(),
  });
  closeModal('medical'); showNotif('Medical record saved', 'success'); renderMedical();
}



async function openModal_medical(id = null) {
  editingId['medical'] = id;
  const prisoners = await dbGetAll('prisoners');
  const sel = document.getElementById('med-prisoner');
  if (sel) sel.innerHTML = prisoners.map(p => `<option value="${p.prisonerId}">${p.prisonerId} - ${p.firstname} ${p.lastname}</option>`).join('');
  if (!id) {
    const d = document.getElementById('med-date'); if (d) d.value = new Date().toISOString().split('T')[0];
    ['condition','treatment','doctor','notes'].forEach(f => { const el = document.getElementById('med-'+f); if (el) el.value = ''; });
  }
  if (id) {
    const recs = await dbGetAll('medical');
    const r = recs.find(r => r.id == id);
    if (r) {
      const psel = document.getElementById('med-prisoner'); if (psel) psel.value = r.prisoner;
      ['condition','treatment','doctor','status','notes'].forEach(f => { const el = document.getElementById('med-'+f); if (el) el.value = r[f] || ''; });
      const d = document.getElementById('med-date'); if (d) d.value = r.medDate || '';
    }
  }
}

</script>


<!-- MEDICAL MODAL -->
<div class="modal-overlay" id="modal-medical">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('medical')">✕</button>
    <div class="modal-title" id="medical-modal-title">Add Medical Record</div>
    <div class="form-group"><label class="form-label">Prisoner *</label><select class="form-select" id="med-prisoner"></select></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Condition / Diagnosis *</label><input type="text" class="form-input" id="med-condition"></div>
      <div class="form-group"><label class="form-label">Treatment</label><input type="text" class="form-input" id="med-treatment"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Doctor</label><input type="text" class="form-input" id="med-doctor"></div>
      <div class="form-group"><label class="form-label">Date</label><input type="date" class="form-input" id="med-date"></div>
    </div>
    <div class="form-group"><label class="form-label">Status</label><select class="form-select" id="med-status"><option>Ongoing</option><option>Resolved</option><option>Referred</option><option>Critical</option></select></div>
    <div class="form-group"><label class="form-label">Notes</label><textarea class="form-textarea" id="med-notes"></textarea></div>
    <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('medical')">Cancel</button><button class="btn btn-primary" onclick="saveMedical()">Save Record</button></div>
  </div>
</div>

