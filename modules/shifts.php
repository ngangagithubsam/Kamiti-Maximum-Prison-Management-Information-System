<!-- SHIFT MANAGEMENT -->
      <div class="page" id="page-shifts">
        <div class="tabs">
          <div class="tab active" onclick="switchTab('shifts','roster')">Shift Roster</div>
          <div class="tab" onclick="switchTab('shifts','swaps')">Swap Requests</div>
        </div>
        <div class="tab-content active" id="shifts-tab-roster">
          <div class="stats-grid-3" style="margin-bottom:20px">
            <div class="stat-card"><div class="stat-label">Today's Shifts</div><div class="stat-value text-accent" id="shift-today-count">0</div></div>
            <div class="stat-card"><div class="stat-label">On Duty Now</div><div class="stat-value" style="color:var(--green)" id="shift-active-count">0</div></div>
            <div class="stat-card"><div class="stat-label">Pending Swaps</div><div class="stat-value" style="color:var(--amber)" id="shift-swap-count">0</div></div>
          </div>
          <div class="table-card">
            <div class="table-header"><div class="table-title">Shift Schedule</div>
              <div class="search-box"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg><input type="text" placeholder="Search shifts..." id="shift-search" oninput="renderShifts()"></div>
              <select class="form-select" id="shift-type-filter" onchange="renderShifts()" style="width:130px"><option value="">All Types</option><option>Morning</option><option>Afternoon</option><option>Night</option><option>Emergency</option></select>
            </div>
            <div class="table-scroll"><table class="shift-table"><colgroup><col><col><col><col><col><col><col><col><col><col><col></colgroup><thead><tr><th>Shift ID</th><th>Staff</th><th>Type</th><th>Date</th><th>Start</th><th>End</th><th>Location</th><th>Supervisor</th><th>Status</th><th>Actions</th></tr></thead><tbody id="shifts-tbody"></tbody></table></div>
          </div>
        </div>
        <div class="tab-content" id="shifts-tab-swaps">
          <div class="table-card">
            <div class="table-header"><div class="table-title">Shift Swap Requests</div>
              <button class="btn btn-primary btn-sm" onclick="openModal('swap')">&#x002B; Request Swap</button>
            </div>
            <div class="table-scroll"><table class="shift-table"><colgroup><col><col><col><col><col><col><col><col></colgroup><thead><tr><th>Swap ID</th><th>Requested By</th><th>Swap With</th><th>Reason</th><th>Status</th><th>Approved By</th><th>Date</th><th>Actions</th></tr></thead><tbody id="swaps-tbody"></tbody></table></div>
          </div>
        </div>
      </div>
<script>
async function renderShifts() {
  const shifts = await dbGetAll('shifts');
  const q = (document.getElementById('shift-search')?.value || '').toLowerCase();
  const tf = document.getElementById('shift-type-filter')?.value || '';
  const today = new Date().toDateString();
  const todayShifts = shifts.filter(s => new Date(s.shiftDate).toDateString() === today);
  if(document.getElementById('shift-today-count')) document.getElementById('shift-today-count').textContent = todayShifts.length;
  if(document.getElementById('shift-active-count')) document.getElementById('shift-active-count').textContent = shifts.filter(s => s.status === 'Active').length;
  const filtered = shifts.filter(s => (!tf || s.shiftType === tf) && (!q || `${s.staffName} ${s.shiftId} ${s.location||''} ${s.shiftType}`.toLowerCase().includes(q)));
  document.getElementById('shifts-tbody').innerHTML = filtered.map(s => `<tr><td class="text-mono text-accent">${s.shiftId}</td><td style="color:var(--text)">${s.staffName}</td><td><span class="badge ${shiftTypeBadge(s.shiftType)}">${s.shiftType}</span></td><td>${fmtDate(s.shiftDate)}</td><td class="text-mono">${s.startTime||'—'}</td><td class="text-mono">${s.endTime||'—'}</td><td>${s.location||'—'}</td><td>${s.supervisor||'—'}</td><td>${statusBadge(s.status)}</td><td style="white-space:nowrap"><button class="btn btn-secondary btn-sm" onclick="openModal('shift',${s.id})">Edit</button> <button class="btn btn-danger btn-sm" onclick="confirmDelete('shifts',${s.id},'${s.shiftId}')">Del</button></td></tr>`).join('') || `<tr><td colspan="10" style="text-align:center;color:var(--text3);padding:40px">No shifts found</td></tr>`;
}


async function renderSwaps() {
  const swaps = await dbGetAll('shift_swaps');
  if (document.getElementById('shift-swap-count')) document.getElementById('shift-swap-count').textContent = swaps.filter(s => s.status === 'Pending').length;
  document.getElementById('swaps-tbody').innerHTML = swaps.map(s => `<tr><td class="text-mono text-accent">${s.swapId}</td><td style="color:var(--text)">${s.requestedBy}</td><td>${s.swapWith}</td><td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${s.reason||'—'}</td><td>${statusBadge(s.status)}</td><td>${s.approvedBy||'—'}</td><td>${fmtDateTime(s.requestDate||s.created_at)}</td><td><button class="btn btn-danger btn-sm" onclick="confirmDelete('shift_swaps',${s.id},'${s.swapId}')">Del</button></td></tr>`).join('') || `<tr><td colspan="8" style="text-align:center;color:var(--text3);padding:40px">No swap requests</td></tr>`;
}

// -- RENDER: Inventory -----------------------------------------

async function saveShift() {
  const staffSel = document.getElementById('sh-staff');
  const staffId = staffSel.value;
  const staffText = staffSel.options[staffSel.selectedIndex]?.text || '';
  const staffName = staffText.includes(' - ') ? staffText.split(' - ')[1].split(' (')[0] : staffText;
  const shiftDate = document.getElementById('sh-date').value;
  if (!staffId || !shiftDate) return showNotif('Staff and date required', 'error');
  const isEdit = !!editingId['shift'];
  await dbPut('shifts', { id: isEdit ? editingId['shift'] : 0, staffId, staffName, shiftType: document.getElementById('sh-type').value, date: shiftDate, startTime: document.getElementById('sh-start').value, endTime: document.getElementById('sh-end').value, location: document.getElementById('sh-location').value.trim(), supervisor: document.getElementById('sh-supervisor').value.trim(), status: document.getElementById('sh-status').value, notes: document.getElementById('sh-notes').value.trim() });
  closeModal('shift'); showNotif(`Shift ${isEdit?'updated':'scheduled'}`, 'success'); renderShifts();
}

// -- SAVE: Swap ------------------------------------------------

async function saveSwap() {
  const by = document.getElementById('sw-by').value.trim();
  const withWho = document.getElementById('sw-with').value.trim();
  if (!by || !withWho) return showNotif('Both staff names required', 'error');
  await dbPut('shift_swaps', { id: 0, originalShift: parseInt(document.getElementById('sw-shift').value) || 0, requestedBy: by, swapWith: withWho, reason: document.getElementById('sw-reason').value.trim(), status: document.getElementById('sw-status').value, approvedBy: document.getElementById('sw-approved').value.trim() });
  closeModal('swap'); showNotif('Swap request submitted', 'success'); renderSwaps();
}

// -- SAVE: Inventory Item --------------------------------------

const shiftTypeBadge = t => ({ Morning:'badge-amber', Afternoon:'badge-blue', Night:'badge-purple', Emergency:'badge-red' }[t] || 'badge-gray');


async function openModal_shift(id = null) {
  editingId['shift'] = id;
  document.getElementById('shift-modal-title').textContent = id ? 'Edit Shift' : 'Schedule Shift';
  const staff = await dbGetAll('staff');
  const sel = document.getElementById('sh-staff');
  if (sel) sel.innerHTML = staff.filter(s => s.status === 'Active').map(s => `<option value="${s.staffId}">${s.staffId} - ${s.firstname} ${s.lastname} (${s.role})</option>`).join('');
  if (!id) {
    const d = document.getElementById('sh-date'); if (d) d.value = new Date().toISOString().split('T')[0];
    const st = document.getElementById('sh-start'); if (st) st.value = '06:00';
    const en = document.getElementById('sh-end'); if (en) en.value = '14:00';
    ['sh-location','sh-supervisor','sh-notes'].forEach(f=>{const el=document.getElementById(f);if(el)el.value='';});
  }
  if (id) {
    const shifts = await dbGetAll('shifts'); const s = shifts.find(s => s.id == id);
    if (s) {
      const sel2 = document.getElementById('sh-staff'); if (sel2) sel2.value = s.staffId;
      const t = document.getElementById('sh-type'); if (t) t.value = s.shiftType || 'Morning';
      const d = document.getElementById('sh-date'); if (d) d.value = s.shiftDate || '';
      const st = document.getElementById('sh-start'); if (st) st.value = s.startTime || '';
      const en = document.getElementById('sh-end'); if (en) en.value = s.endTime || '';
      const loc = document.getElementById('sh-location'); if (loc) loc.value = s.location || '';
      const sup = document.getElementById('sh-supervisor'); if (sup) sup.value = s.supervisor || '';
      const sts = document.getElementById('sh-status'); if (sts) sts.value = s.status || 'Scheduled';
      const notes = document.getElementById('sh-notes'); if (notes) notes.value = s.notes || '';
    }
  }
}

async function openModal_swap(id = null) {
  ['sw-shift','sw-by','sw-with','sw-reason','sw-approved'].forEach(f=>{const el=document.getElementById(f);if(el)el.value='';});
  editingId['swap'] = id;
}
</script>

<!-- SHIFT MODAL -->
<div class="modal-overlay" id="modal-shift">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('shift')">&#x2715;</button>
    <div class="modal-title" id="shift-modal-title">Schedule Shift</div>
    <div class="modal-sub">Assign a staff member to a duty shift</div>
    <div class="form-group"><label class="form-label">Staff Member *</label><select class="form-select" id="sh-staff"></select></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Shift Type *</label><select class="form-select" id="sh-type"><option>Morning</option><option>Afternoon</option><option>Night</option><option>Emergency</option></select></div>
      <div class="form-group"><label class="form-label">Shift Date *</label><input type="date" class="form-input" id="sh-date"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Start Time *</label><input type="time" class="form-input" id="sh-start"></div>
      <div class="form-group"><label class="form-label">End Time *</label><input type="time" class="form-input" id="sh-end"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Post / Location</label><input type="text" class="form-input" id="sh-location" placeholder="e.g. Block A, Main Gate"></div>
      <div class="form-group"><label class="form-label">Supervisor</label><input type="text" class="form-input" id="sh-supervisor"></div>
    </div>
    <div class="form-group"><label class="form-label">Status</label><select class="form-select" id="sh-status"><option>Scheduled</option><option>Active</option><option>Completed</option><option>Absent</option><option>Swapped</option></select></div>
    <div class="form-group"><label class="form-label">Notes</label><textarea class="form-textarea" id="sh-notes"></textarea></div>
    <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('shift')">Cancel</button><button class="btn btn-primary" onclick="saveShift()">Save Shift</button></div>
  </div>
</div>

