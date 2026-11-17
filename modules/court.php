<!-- COURT -->
      <div class="page" id="page-court">
        <div class="table-card">
          <div class="table-header"><div class="table-title">Court Cases</div>
            <div class="search-box"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg><input type="text" placeholder="Search cases..." id="court-search" oninput="renderCourt()"></div>
          </div>
          <table><thead><tr><th>Case #</th><th>Prisoner</th><th>Charges</th><th>Court</th><th>Verdict</th><th>Status</th><th>Actions</th></tr></thead><tbody id="court-tbody"></tbody></table>
        </div>
      </div>
<script>
async function renderCourt() {
  const cases = await dbGetAll('court');
  const q = (document.getElementById('court-search')?.value||'').toLowerCase();
  const tbody = document.getElementById('court-tbody');
  const filtered = cases.filter(c => !q || `${c.prisonerName} ${c.caseId} ${c.casenumber}`.toLowerCase().includes(q));
  tbody.innerHTML = filtered.map(c=>`<tr><td class="text-mono text-accent">${c.casenumber}</td><td style="color:var(--text)">${c.prisonerName}</td><td>${c.charges||'—'}</td><td>${c.court||'—'}</td><td>${c.verdict||'Pending'}</td><td>${statusBadge(c.status)}</td><td><button class="btn btn-secondary btn-sm" onclick="openModal('court',${c.id})">Edit</button> <button class="btn btn-danger btn-sm" onclick="confirmDelete('court',${c.id},'${c.casenumber}')">Del</button></td></tr>`).join('') || `<tr><td colspan="7" style="text-align:center;color:var(--text3);padding:40px">No cases</td></tr>`;
}


async function saveCourt() {
  const prisoner = document.getElementById('c-prisoner').value;
  const casenumber = document.getElementById('c-casenumber').value.trim();
  if (!prisoner || !casenumber) return showNotif('Prisoner and case number required', 'error');
  const prisoners = await dbGetAll('prisoners');
  const isEdit = !!editingId['court'];
  await dbPut('court', {
    id: isEdit ? editingId['court'] : 0,
    prisoner, prisonerName: prisonerFullName(prisoner, prisoners),
    casenumber, court: document.getElementById('c-court').value.trim(),
    charges: document.getElementById('c-charges').value.trim(),
    judge: document.getElementById('c-judge').value.trim(),
    prosecutor: document.getElementById('c-prosecutor').value.trim(),
    defense: document.getElementById('c-defense').value.trim(),
    filingdate: document.getElementById('c-filingdate').value,
    hearingdate: document.getElementById('c-hearingdate').value,
    verdict: document.getElementById('c-verdict').value,
    sentence: document.getElementById('c-sentence').value.trim(),
    status: document.getElementById('c-status').value,
    fine: parseInt(document.getElementById('c-fine').value)||0,
    notes: document.getElementById('c-notes').value.trim(),
  });
  closeModal('court'); showNotif('Court case saved', 'success'); renderCourt();
}


async function openModal_court(id = null) {
  editingId['court'] = id;
  document.getElementById('court-modal-title').textContent = id ? 'Edit Court Case' : 'Add Court Case';
  const prisoners = await dbGetAll('prisoners');
  const sel = document.getElementById('c-prisoner');
  if (sel) sel.innerHTML = prisoners.map(p => `<option value="${p.prisonerId}">${p.prisonerId} - ${p.firstname} ${p.lastname}</option>`).join('');
  if (!id) {
    const fd = document.getElementById('c-filingdate'); if (fd) fd.value = new Date().toISOString().split('T')[0];
    ['casenumber','court','charges','judge','prosecutor','defense','hearingdate','sentence','notes'].forEach(f => { const el = document.getElementById('c-'+f); if(el) el.value=''; });
    const fine = document.getElementById('c-fine'); if (fine) fine.value = '0';
  }
  if (id) {
    const cases = await dbGetAll('court');
    const c = cases.find(c => c.id == id);
    if (c) {
      const psel = document.getElementById('c-prisoner'); if (psel) psel.value = c.prisoner;
      ['casenumber','court','charges','judge','prosecutor','defense','filingdate','hearingdate','verdict','sentence','status','fine','notes'].forEach(f => { const el = document.getElementById('c-'+f); if (el) el.value = c[f] || ''; });
    }
  }
}

</script>


<!-- COURT MODAL -->
<div class="modal-overlay" id="modal-court">
  <div class="modal modal-lg">
    <button class="modal-close" onclick="closeModal('court')">✕</button>
    <div class="modal-title" id="court-modal-title">Add Court Case</div>
    <div class="form-group"><label class="form-label">Prisoner *</label><select class="form-select" id="c-prisoner"></select></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Case Number *</label><input type="text" class="form-input" id="c-casenumber"></div>
      <div class="form-group"><label class="form-label">Court Name</label><input type="text" class="form-input" id="c-court"></div>
    </div>
    <div class="form-group"><label class="form-label">Charges</label><input type="text" class="form-input" id="c-charges"></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Judge</label><input type="text" class="form-input" id="c-judge"></div>
      <div class="form-group"><label class="form-label">Prosecutor</label><input type="text" class="form-input" id="c-prosecutor"></div>
    </div>
    <div class="form-group"><label class="form-label">Defense Attorney</label><input type="text" class="form-input" id="c-defense"></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Filing Date</label><input type="date" class="form-input" id="c-filingdate"></div>
      <div class="form-group"><label class="form-label">Next Hearing Date</label><input type="date" class="form-input" id="c-hearingdate"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Verdict</label><select class="form-select" id="c-verdict"><option value="">Pending</option><option>Guilty</option><option>Not Guilty</option><option>Dismissed</option><option>Plea Deal</option></select></div>
      <div class="form-group"><label class="form-label">Sentence Imposed</label><input type="text" class="form-input" id="c-sentence"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Status</label><select class="form-select" id="c-status"><option>Active</option><option>Awaiting Hearing</option><option>Appealing</option><option>Closed</option></select></div>
      <div class="form-group"><label class="form-label">Fine / Bail (KSH)</label><input type="number" class="form-input" id="c-fine"></div>
    </div>
    <div class="form-group"><label class="form-label">Notes</label><textarea class="form-textarea" id="c-notes"></textarea></div>
    <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('court')">Cancel</button><button class="btn btn-primary" onclick="saveCourt()">Save Case</button></div>
  </div>
</div>

