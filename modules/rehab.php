<!-- REHABILITATION -->
      <div class="page" id="page-rehab">
        <div class="tabs">
          <div class="tab active" onclick="switchTab('rehab','programs')">Programs</div>
          <div class="tab" onclick="switchTab('rehab','enrollments')">Enrollments</div>
        </div>
        <div class="tab-content active" id="rehab-tab-programs">
          <div class="stats-grid-3" style="margin-bottom:20px">
            <div class="stat-card"><div class="stat-label">Active Programs</div><div class="stat-value text-accent" id="rehab-prog-count">0</div></div>
            <div class="stat-card"><div class="stat-label">Total Enrolled</div><div class="stat-value" style="color:var(--green)" id="rehab-enrolled-count">0</div></div>
            <div class="stat-card"><div class="stat-label">Completions</div><div class="stat-value" style="color:#fb923c" id="rehab-complete-count">0</div></div>
          </div>
          <div class="table-card">
            <div class="table-header"><div class="table-title">Rehabilitation Programs</div>
              <div class="search-box"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg><input type="text" placeholder="Search programs..." id="rehab-prog-search" oninput="renderRehabPrograms()"></div>
              <select class="form-select" id="rehab-type-filter" onchange="renderRehabPrograms()" style="width:140px"><option value="">All Types</option><option>Education</option><option>Vocational</option><option>Counseling</option><option>Drug Rehab</option><option>Religious</option><option>Sports</option><option>Other</option></select>
            </div>
            <div class="table-scroll"><table class="rehab-program-table"><colgroup><col><col><col><col><col><col><col><col><col></colgroup><thead><tr><th>Program ID</th><th>Name</th><th>Type</th><th>Facilitator</th><th>Schedule</th><th>Capacity</th><th>Enrolled</th><th>Status</th><th>Actions</th></tr></thead><tbody id="rehab-programs-tbody"></tbody></table></div>
          </div>
        </div>
        <div class="tab-content" id="rehab-tab-enrollments">
          <div class="table-card">
            <div class="table-header"><div class="table-title">Prisoner Enrollments</div>
              <button class="btn btn-primary btn-sm" onclick="openModal('enrollment')">&#x002B; Enroll Prisoner</button>
              <div class="search-box"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg><input type="text" placeholder="Search enrollments..." id="enroll-search" oninput="renderEnrollments()"></div>
            </div>
            <div class="table-scroll"><table class="rehab-enrollment-table"><colgroup><col><col><col><col><col><col><col></colgroup><thead><tr><th>Enroll ID</th><th>Prisoner</th><th>Program</th><th>Enroll Date</th><th>Progress</th><th>Status</th><th>Actions</th></tr></thead><tbody id="enrollments-tbody"></tbody></table></div>
          </div>
        </div>
      </div>
<script>
async function renderRehab() { renderRehabPrograms(); renderRehabStats(); }


async function renderRehabStats() {
  const [progs, enrollments] = await Promise.all([dbGetAll('rehab_programs'), dbGetAll('rehab_enrollments')]);
  if(document.getElementById('rehab-prog-count')) document.getElementById('rehab-prog-count').textContent = progs.filter(p=>p.status==='Active').length;
  if(document.getElementById('rehab-enrolled-count')) document.getElementById('rehab-enrolled-count').textContent = enrollments.filter(e=>e.status==='Active').length;
  if(document.getElementById('rehab-complete-count')) document.getElementById('rehab-complete-count').textContent = enrollments.filter(e=>e.status==='Completed').length;
}


async function renderRehabPrograms() {
  const progs = await dbGetAll('rehab_programs');
  const q = (document.getElementById('rehab-prog-search')?.value || '').toLowerCase();
  const tf = document.getElementById('rehab-type-filter')?.value || '';
  const filtered = progs.filter(p => (!tf || p.type === tf) && (!q || `${p.name} ${p.programId} ${p.facilitator||''}`.toLowerCase().includes(q)));
  document.getElementById('rehab-programs-tbody').innerHTML = filtered.map(p => `<tr><td class="text-mono text-accent">${p.programId}</td><td style="color:var(--text)">${p.name}</td><td><span class="badge ${rehabBadge(p.type)}">${p.type}</span></td><td>${p.facilitator||'—'}</td><td style="font-size:12px">${p.schedule||'—'}</td><td>${p.capacity}</td><td><span style="color:${p.enrolled>=p.capacity?'var(--red)':'var(--green)'}">${p.enrolled||0}</span>/${p.capacity}</td><td>${statusBadge(p.status)}</td><td style="white-space:nowrap"><button class="btn btn-secondary btn-sm" onclick="openModal('rehab_prog',${p.id})">Edit</button> <button class="btn btn-danger btn-sm" onclick="confirmDelete('rehab_programs',${p.id},'${p.name.replace(/'/g,'')}')">Del</button></td></tr>`).join('') || `<tr><td colspan="9" style="text-align:center;color:var(--text3);padding:40px">No programs found</td></tr>`;
}


async function renderEnrollments() {
  const enrollments = await dbGetAll('rehab_enrollments');
  const q = (document.getElementById('enroll-search')?.value || '').toLowerCase();
  const filtered = enrollments.filter(e => !q || `${e.prisonerName} ${e.programName} ${e.enrollId}`.toLowerCase().includes(q));
  document.getElementById('enrollments-tbody').innerHTML = filtered.map(e => `<tr><td class="text-mono text-accent">${e.enrollId}</td><td style="color:var(--text)">${e.prisonerName}</td><td>${e.programName}</td><td>${fmtDate(e.enrollDate)}</td><td style="min-width:120px"><div class="progress-bar"><div class="progress-fill" style="width:${e.progress||0}%"></div></div><span style="font-size:11px;color:var(--text3)">${e.progress||0}%</span></td><td>${statusBadge(e.status)}</td><td style="white-space:nowrap"><button class="btn btn-secondary btn-sm" onclick="openModal('enrollment',${e.id})">Edit</button> <button class="btn btn-danger btn-sm" onclick="confirmDelete('rehab_enrollments',${e.id},'${e.enrollId}')">Del</button></td></tr>`).join('') || `<tr><td colspan="7" style="text-align:center;color:var(--text3);padding:40px">No enrollments found</td></tr>`;
}

 
// ============================================================
// NOTIFICATIONS
// ============================================================
let notifTimeout;

async function saveRehabProgram() {
  const name = document.getElementById('rp-name').value.trim();
  if (!name) return showNotif('Program name required', 'error');
  const isEdit = !!editingId['rehab_prog'];
  await dbPut('rehab_programs', { id: isEdit ? editingId['rehab_prog'] : 0, name, type: document.getElementById('rp-type').value, description: document.getElementById('rp-description').value.trim(), facilitator: document.getElementById('rp-facilitator').value.trim(), schedule: document.getElementById('rp-schedule').value.trim(), venue: document.getElementById('rp-venue').value.trim(), capacity: parseInt(document.getElementById('rp-capacity').value) || 20, startDate: document.getElementById('rp-start').value, endDate: document.getElementById('rp-end').value, status: document.getElementById('rp-status').value });
  closeModal('rehab_prog'); showNotif(`Program ${isEdit?'updated':'added'}`, 'success'); await renderRehab(); await renderDashboard();
}

// -- SAVE: Enrollment ------------------------------------------

async function saveEnrollment() {
  const programId = document.getElementById('en-program').value;
  const prisoner = document.getElementById('en-prisoner').value;
  if (!programId || !prisoner) return showNotif('Program and prisoner required', 'error');
  const [prisoners, progs] = await Promise.all([dbGetAll('prisoners'), dbGetAll('rehab_programs')]);
  const prog = progs.find(p => p.id == programId);
  const isEdit = !!editingId['enrollment'];
  await dbPut('rehab_enrollments', { id: isEdit ? editingId['enrollment'] : 0, programId: parseInt(programId), programName: prog?.name || '', prisoner, prisonerName: prisonerFullName(prisoner, prisoners), enrollDate: document.getElementById('en-date').value, completionDate: document.getElementById('en-completion').value, progress: parseInt(document.getElementById('en-progress').value) || 0, grade: document.getElementById('en-grade').value.trim(), status: document.getElementById('en-status').value, notes: document.getElementById('en-notes').value.trim() });
  closeModal('enrollment'); showNotif('Enrollment saved', 'success'); await renderEnrollments(); await renderRehabStats(); await updateNavCounts(); await renderDashboard();
}

// -- RENDER: Shifts --------------------------------------------

const rehabBadge = t => ({ Education:'badge-blue', Vocational:'badge-teal', Counseling:'badge-purple', 'Drug Rehab':'badge-red', Religious:'badge-amber', Sports:'badge-green', Other:'badge-gray' }[t] || 'badge-gray');


async function openModal_rehab_prog(id = null) {
  editingId['rehab_prog'] = id;
  document.getElementById('rehab-prog-modal-title').textContent = id ? 'Edit Program' : 'Add Rehabilitation Program';
  if (!id) { ['rp-name','rp-description','rp-facilitator','rp-schedule','rp-venue','rp-start','rp-end'].forEach(f=>{const el=document.getElementById(f);if(el)el.value='';}); const cap = document.getElementById('rp-capacity'); if (cap) cap.value = 20; }
  if (id) { const progs = await dbGetAll('rehab_programs'); const p = progs.find(p => p.id == id); if (p) {
    const n = document.getElementById('rp-name'); if (n) n.value = p.name||'';
    const t = document.getElementById('rp-type'); if (t) t.value = p.type||'';
    const desc = document.getElementById('rp-description'); if (desc) desc.value = p.description||'';
    const f = document.getElementById('rp-facilitator'); if (f) f.value = p.facilitator||'';
    const sch = document.getElementById('rp-schedule'); if (sch) sch.value = p.schedule||'';
    const ven = document.getElementById('rp-venue'); if (ven) ven.value = p.venue||'';
    const cap = document.getElementById('rp-capacity'); if (cap) cap.value = p.capacity||20;
    const st = document.getElementById('rp-start'); if (st) st.value = p.startDate||'';
    const en = document.getElementById('rp-end'); if (en) en.value = p.endDate||'';
    const s = document.getElementById('rp-status'); if (s) s.value = p.status||'Active';
  } }
}

async function openModal_enrollment(id = null) {
  editingId['enrollment'] = id;
  document.getElementById('enroll-modal-title').textContent = id ? 'Edit Enrollment' : 'Enroll Prisoner';
  const progs = await dbGetAll('rehab_programs');
  const prisoners = await dbGetAll('prisoners');
  const progSel = document.getElementById('en-program'); if (progSel) progSel.innerHTML = progs.filter(p=>p.status==='Active').map(p=>`<option value="${p.id}">${p.programId} - ${p.name}</option>`).join('');
  const prSel = document.getElementById('en-prisoner'); if (prSel) prSel.innerHTML = prisoners.filter(p=>p.status==='Active').map(p=>`<option value="${p.prisonerId}">${p.prisonerId} - ${p.firstname} ${p.lastname}</option>`).join('');
  if (!id) {
    const d = document.getElementById('en-date'); if (d) d.value = new Date().toISOString().split('T')[0];
    const prog = document.getElementById('en-progress'); if (prog) prog.value = 0;
    ['en-completion','en-grade','en-notes'].forEach(f=>{const el=document.getElementById(f);if(el)el.value='';});
  }
  if (id) {
    const enrs = await dbGetAll('rehab_enrollments'); const e = enrs.find(e => e.id == id);
    if (e) {
      const prog = document.getElementById('en-program'); if (prog) prog.value = e.programId;
      const pr = document.getElementById('en-prisoner'); if (pr) pr.value = e.prisoner;
      const d = document.getElementById('en-date'); if (d) d.value = e.enrollDate || '';
      const com = document.getElementById('en-completion'); if (com) com.value = e.completionDate || '';
      const progress = document.getElementById('en-progress'); if (progress) progress.value = e.progress || 0;
      const grade = document.getElementById('en-grade'); if (grade) grade.value = e.grade || '';
      const status = document.getElementById('en-status'); if (status) status.value = e.status || 'Active';
      const notes = document.getElementById('en-notes'); if (notes) notes.value = e.notes || '';
    }
  }
}
</script>

<!-- ENROLLMENT MODAL -->
<div class="modal-overlay" id="modal-enrollment">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('enrollment')">&#x2715;</button>
    <div class="modal-title" id="enroll-modal-title">Enroll Prisoner in Program</div>
    <div class="form-group"><label class="form-label">Program *</label><select class="form-select" id="en-program"></select></div>
    <div class="form-group"><label class="form-label">Prisoner *</label><select class="form-select" id="en-prisoner"></select></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Enroll Date</label><input type="date" class="form-input" id="en-date"></div>
      <div class="form-group"><label class="form-label">Expected Completion</label><input type="date" class="form-input" id="en-completion"></div>
    </div>
    <div class="form-group"><label class="form-label">Progress (0-100%)</label><input type="number" class="form-input" id="en-progress" min="0" max="100" value="0"></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Grade</label><input type="text" class="form-input" id="en-grade" placeholder="A, B, C..."></div>
      <div class="form-group"><label class="form-label">Status</label><select class="form-select" id="en-status"><option>Active</option><option>Completed</option><option>Dropped</option><option>Suspended</option></select></div>
    </div>
    <div class="form-group"><label class="form-label">Notes</label><textarea class="form-textarea" id="en-notes"></textarea></div>
    <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('enrollment')">Cancel</button><button class="btn btn-primary" onclick="saveEnrollment()">Save Enrollment</button></div>
  </div>
</div>

