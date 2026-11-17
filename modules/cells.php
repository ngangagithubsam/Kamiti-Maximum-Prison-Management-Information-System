<!-- CELLS -->
      <div class="page" id="page-cells">
        <div class="table-card">
          <div class="table-header"><div class="table-title">Cell Blocks</div></div>
          <table><thead><tr><th>Cell</th><th>Block</th><th>Capacity</th><th>Occupied</th><th>Status</th><th>Actions</th></tr></thead><tbody id="cells-tbody"></tbody></table>
        </div>
      </div>
<script>
async function renderCells() {
  const cells = await dbGetAll('cells');
  const tbody = document.getElementById('cells-tbody');
  tbody.innerHTML = cells.map(c=>`<tr><td class="text-mono text-accent">${c.cellNumber}</td><td>${c.block||'—'}</td><td>${c.capacity}</td><td>${c.occupied||0}</td><td>${statusBadge(c.status)}</td><td><button class="btn btn-danger btn-sm" onclick="confirmDelete('cells',${c.id},'Cell ${c.cellNumber}')">Del</button></td></tr>`).join('') || `<tr><td colspan="6" style="text-align:center;color:var(--text3);padding:40px">No cells</td></tr>`;
}


async function saveCell() {
  const cellNumber = document.getElementById('c-cellnumber').value.trim();
  const block = document.getElementById('c-block').value.trim();
  const capacity = parseInt(document.getElementById('c-capacity').value) || 1;
  if (!cellNumber || !block) return showNotif('Cell number and block are required', 'error');
  await dbPut('cells', {
    id: editingId['cell'] || 0,
    cellNumber,
    block,
    capacity,
    status: document.getElementById('c-status').value,
    occupied: 0
  });
  closeModal('cell');
  showNotif(editingId['cell'] ? 'Cell updated' : 'Cell added', 'success');
  renderCells();
}


async function openModal_cell(id = null) {
  editingId['cell'] = id;
  document.getElementById('cell-modal-title').textContent = id ? 'Edit Cell' : 'Add Cell';
  if (id) {
    const cells = await dbGetAll('cells');
    const cell = cells.find(c => c.id == id);
    if (cell) {
      const cn = document.getElementById('c-cellnumber'); if (cn) cn.value = cell.cellNumber || '';
      const cb = document.getElementById('c-block'); if (cb) cb.value = cell.block || '';
      const cap = document.getElementById('c-capacity'); if (cap) cap.value = cell.capacity || 2;
      const cs = document.getElementById('c-status'); if (cs) cs.value = cell.status || 'Available';
    }
  } else {
    const cn = document.getElementById('c-cellnumber'); if (cn) cn.value = '';
    const cb = document.getElementById('c-block'); if (cb) cb.value = '';
    const cap = document.getElementById('c-capacity'); if (cap) cap.value = 2;
    const cs = document.getElementById('c-status'); if (cs) cs.value = 'Available';
  }
}

</script>


<!-- CELL MODAL -->
<div class="modal-overlay" id="modal-cell">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('cell')">&#x2715;</button>
    <div class="modal-title" id="cell-modal-title">Add Cell</div>
    <div class="form-group"><label class="form-label">Cell Number *</label><input type="text" class="form-input" id="c-cellnumber" placeholder="e.g., A-01"></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Block *</label><input type="text" class="form-input" id="c-block" placeholder="e.g., Block A"></div>
      <div class="form-group"><label class="form-label">Capacity *</label><input type="number" class="form-input" id="c-capacity" min="1" value="2"></div>
    </div>
    <div class="form-group"><label class="form-label">Status</label><select class="form-select" id="c-status"><option>Available</option><option>Occupied</option><option>Under Maintenance</option><option>Closed</option></select></div>
    <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('cell')">Cancel</button><button class="btn btn-primary" onclick="saveCell()">Save Cell</button></div>
  </div>
</div>

