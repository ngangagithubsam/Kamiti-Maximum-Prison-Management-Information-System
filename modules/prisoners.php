<!-- PRISONERS -->
      <div class="page" id="page-prisoners">
        <div class="table-card">
          <div class="table-header"><div class="table-title">Prisoner Registry</div>
            <div class="filter-group"><label>From:</label><input type="date" id="prisoner-from-date" onchange="renderPrisoners()"><label>To:</label><input type="date" id="prisoner-to-date" onchange="renderPrisoners()"><button class="btn-export" onclick="exportData('prisoners','csv')">CSV</button><button class="btn-export" onclick="exportData('prisoners','excel')">Excel</button><button class="btn-export" onclick="exportData('prisoners','pdf')">PDF</button><button class="btn-export" onclick="exportData('prisoners','word')">Word</button></div>
            <select class="form-select" id="prisoner-filter" onchange="renderPrisoners()" style="width:140px"><option value="">All Status</option><option>Active</option><option>Released</option><option>Transferred</option><option>Deceased</option></select>
            <div class="search-box"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg><input type="text" placeholder="Search prisoners..." id="prisoner-search" oninput="renderPrisoners()"></div>
          </div>
          <div class="table-scroll"><table class="prisoner-table"><colgroup><col><col><col><col><col><col><col><col></colgroup><thead><tr><th>Prisoner ID</th><th>Full Name</th><th>DOB</th><th>Type</th><th>Cell</th><th>Sentence</th><th>Status</th><th>Actions</th></tr></thead><tbody id="prisoners-tbody"></tbody></table></div>
        </div>
      </div>
<script>
async function renderPrisoners() {
  const prisoners = await dbGetAll('prisoners');
  const q = (document.getElementById('prisoner-search')?.value||'').toLowerCase();
  const sf = document.getElementById('prisoner-filter')?.value||'';
  const tbody = document.getElementById('prisoners-tbody');
  let filtered = getFilteredData(prisoners, 'prisoner');
  filtered = filtered.filter(p => (!sf||p.status===sf) && (!q||`${p.firstname} ${p.lastname} ${p.prisonerId} ${p.prisonerType||''} ${p.caseNumber||''}`.toLowerCase().includes(q)));
  tbody.innerHTML = filtered.map(p=>`<tr><td class="text-mono text-accent">${p.prisonerId}</td><td style="color:var(--text)">${p.firstname} ${p.lastname}</td><td>${fmtDate(p.dob)}</td><td>${p.prisonerType||'—'}</td><td>${p.cell||'—'}</td><td>${p.sentence||0} mo.</td><td>${statusBadge(p.status)}</td><td><button class="btn btn-secondary btn-sm" onclick="openPrisonerView(${Number(p.id)})">View</button> <button class="btn btn-primary btn-sm" onclick="openPrisonerEdit(${Number(p.id)})">Edit</button> <button class="btn btn-export btn-sm" onclick="openPrisonerReport(${Number(p.id)})">Export Report</button> <button class="btn btn-danger btn-sm" onclick="confirmDelete('prisoners',${p.id},'${p.firstname} ${p.lastname}')">Del</button></td></tr>`).join('') || `<tr><td colspan="8" style="text-align:center;color:var(--text3);padding:40px">No prisoners found</td></tr>`;
}


// savePrisoner is implemented centrally in index.php to avoid duplicate definitions


function previewPrisonerPhoto(input) {
  const preview = document.getElementById('p-photo-preview');
  if (input.files && input.files[0]) {
    const file = input.files[0];
    if (!file.type.startsWith('image/')) {
      showNotif('Please choose an image file', 'error');
      input.value = '';
      if (preview) { preview.src = ''; preview.style.display = 'none'; }
      return;
    }
    const reader = new FileReader();
    reader.onload = () => {
      if (preview) { preview.src = reader.result; preview.style.display = 'block'; }
    };
    reader.readAsDataURL(file);
  } else if (preview) {
    preview.src = '';
    preview.style.display = 'none';
  }
}


function readFileAsDataURL(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = () => reject(new Error('Unable to read image file'));
    reader.readAsDataURL(file);
  });
}


async function openModal_prisoner(id = null) {
  editingId['prisoner'] = id;
  document.getElementById('prisoner-modal-title').textContent = id ? 'Edit Prisoner Record' : 'Register New Prisoner';
  const cells = await dbGetAll('cells');
  const prisoners = await dbGetAll('prisoners');
  const pcell = document.getElementById('p-cell');
  if (pcell) pcell.innerHTML = '<option value="">-- Unassigned --</option>' + cells.map(c => `<option value="${c.cellNumber}">${c.cellNumber} (${c.block}) - ${c.status}</option>`).join('');
  if (id) {
    const p = prisoners.find(p => p.id == id);
    if (p) {
      ['firstname','lastname','dob','gender','natid','nationality','address','emname','emphone','admdate','sentence','release','offense','security','status','notes'].forEach(f => { const el = document.getElementById('p-'+f); if (el) el.value = p[f] || ''; });
      const cas = document.getElementById('p-casenumber'); if (cas) cas.value = p.caseNumber || '';
      const type = document.getElementById('p-prisonerType'); if (type) type.value = p.prisonerType || 'Remand / Pre-trial prisoners';
      const mid = document.getElementById('p-middlename'); if (mid) mid.value = p.middlename || '';
      if (pcell) pcell.value = p.cell || '';
      const preview = document.getElementById('p-photo-preview');
      const photoInput = document.getElementById('p-photo');
      if (preview) {
        if (p.photo) { preview.src = p.photo; preview.style.display = 'block'; } else { preview.src = ''; preview.style.display = 'none'; }
      }
      if (photoInput) photoInput.value = '';
    }
  } else {
    ['firstname','middlename','lastname','dob','natid','nationality','address','emname','emphone','sentence','release','offense','notes','casenumber'].forEach(f => { const el = document.getElementById('p-'+f); if (el) el.value = f==='nationality' ? 'Kenyan' : ''; });
    const type = document.getElementById('p-prisonerType'); if (type) type.value = 'Remand / Pre-trial prisoners';
    const adm = document.getElementById('p-admdate'); if (adm) adm.value = new Date().toISOString().split('T')[0];
    const preview = document.getElementById('p-photo-preview'); if (preview) { preview.src = ''; preview.style.display = 'none'; }
    const photoInput = document.getElementById('p-photo'); if (photoInput) photoInput.value = '';
  }
}


</script>


