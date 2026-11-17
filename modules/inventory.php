<!-- INVENTORY -->
      <div class="page" id="page-inventory">
        <div id="stock-alert-banner" class="stock-alert" style="display:none;">
          &#9888; <span id="stock-alert-text">Some items need restocking.</span>
          <button class="btn btn-warning btn-sm" style="margin-left:auto" onclick="switchTab('inventory','lowstock');navigate('inventory')">View Low Stock</button>
        </div>
        <div class="tabs">
          <div class="tab active" onclick="switchTab('inventory','items')">Items Register</div>
          <div class="tab" onclick="switchTab('inventory','transactions')">Transactions</div>
          <div class="tab" onclick="switchTab('inventory','lowstock')">Low Stock</div>
        </div>
        <div class="tab-content active" id="inventory-tab-items">
          <div class="stats-grid" style="margin-bottom:18px">
            <div class="stat-card"><div class="stat-label">Total Items</div><div class="stat-value text-accent" id="inv-total">0</div></div>
            <div class="stat-card"><div class="stat-label">Available</div><div class="stat-value" style="color:var(--green)" id="inv-available">0</div></div>
            <div class="stat-card"><div class="stat-label">Low Stock</div><div class="stat-value" style="color:var(--amber)" id="inv-low">0</div></div>
            <div class="stat-card"><div class="stat-label">Out of Stock</div><div class="stat-value" style="color:var(--red)" id="inv-out">0</div></div>
          </div>
          <div class="table-card">
            <div class="table-header"><div class="table-title">Inventory Items</div>
              <div class="search-box"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg><input type="text" placeholder="Search items..." id="inv-search" oninput="renderInventoryItems()"></div>
              <select class="form-select" id="inv-cat-filter" onchange="renderInventoryItems()" style="width:130px"><option value="">All Categories</option><option>Security</option><option>Medical</option><option>Food</option><option>Uniform</option><option>Cleaning</option><option>Office</option><option>Equipment</option><option>Other</option></select>
            </div>
            <table class="inventory-items-table"><thead><tr><th>Item Code</th><th>Name</th><th>Category</th><th>Qty</th><th>Unit</th><th>Min Qty</th><th>Unit Cost</th><th>Location</th><th>Status</th><th>Actions</th></tr></thead><tbody id="inventory-tbody"></tbody></table>
          </div>
        </div>
        <div class="tab-content" id="inventory-tab-transactions">
          <div class="table-card">
            <div class="table-header"><div class="table-title">Inventory Transactions</div>
              <button class="btn btn-primary btn-sm" onclick="openModal('inv_txn')">&#x002B; Record Transaction</button>
              <div class="search-box"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg><input type="text" placeholder="Search transactions..." id="txn-search" oninput="renderTransactions()"></div>
            </div>
            <table><thead><tr><th>Txn ID</th><th>Item</th><th>Type</th><th>Qty</th><th>Balance After</th><th>Issued To</th><th>Authorized By</th><th>Date</th><th>Actions</th></tr></thead><tbody id="transactions-tbody"></tbody></table>
          </div>
        </div>
        <div class="tab-content" id="inventory-tab-lowstock">
          <div class="table-card">
            <div class="table-header"><div class="table-title">Low Stock & Out of Stock Items</div></div>
            <table><thead><tr><th>Item Code</th><th>Name</th><th>Category</th><th>Current Qty</th><th>Min Qty</th><th>Supplier</th><th>Status</th><th>Actions</th></tr></thead><tbody id="lowstock-tbody"></tbody></table>
          </div>
        </div>
      </div>
<script>
async function renderInventory() { renderInventoryItems(); renderInventoryStats(); }


async function renderInventoryStats() {
  const items = await dbGetAll('inventory');
  if(document.getElementById('inv-total')) document.getElementById('inv-total').textContent = items.length;
  if(document.getElementById('inv-available')) document.getElementById('inv-available').textContent = items.filter(i => i.status === 'Available').length;
  if(document.getElementById('inv-low')) document.getElementById('inv-low').textContent = items.filter(i => i.status === 'Low Stock').length;
  if(document.getElementById('inv-out')) document.getElementById('inv-out').textContent = items.filter(i => i.status === 'Out of Stock').length;
  const critical = items.filter(i => i.status === 'Low Stock' || i.status === 'Out of Stock');
  const banner = document.getElementById('stock-alert-banner');
  if (banner) { if (critical.length > 0) { banner.style.display = 'flex'; document.getElementById('stock-alert-text').textContent = `${critical.length} item(s) need restocking.`; } else banner.style.display = 'none'; }
}


async function renderInventoryItems() {
  const items = await dbGetAll('inventory');
  const q = (document.getElementById('inv-search')?.value || '').toLowerCase();
  const cf = document.getElementById('inv-cat-filter')?.value || '';
  const filtered = items.filter(i => (!cf || i.category === cf) && (!q || `${i.name} ${i.itemCode} ${i.supplier||''}`.toLowerCase().includes(q)));
  document.getElementById('inventory-tbody').innerHTML = filtered.map(i => `<tr><td class="text-mono text-accent">${i.itemCode}</td><td style="color:var(--text)">${i.name}</td><td><span class="badge ${catBadge(i.category)}">${i.category}</span></td><td style="color:${i.quantity<=i.minQuantity?'var(--red)':'var(--text)'};font-weight:${i.quantity<=i.minQuantity?600:400}">${i.quantity}</td><td>${i.unit}</td><td>${i.minQuantity}</td><td>KSh ${parseFloat(i.unitCost||0).toLocaleString()}</td><td>${i.location||'—'}</td><td>${statusBadge(i.status)}</td><td style="white-space:nowrap"><button class="btn btn-secondary btn-sm" onclick="openModal('inv_item',${i.id})">Edit</button> <button class="btn btn-danger btn-sm" onclick="confirmDelete('inventory_items',${i.id},'${i.name.replace(/'/g,'')}')">Del</button></td></tr>`).join('') || `<tr><td colspan="10" style="text-align:center;color:var(--text3);padding:40px">No items found</td></tr>`;
}


async function renderInventoryLowStock() {
  const items = await dbGetAll('inventory');
  const critical = items.filter(i => i.status === 'Low Stock' || i.status === 'Out of Stock');
  document.getElementById('lowstock-tbody').innerHTML = critical.map(i => `<tr><td class="text-mono text-accent">${i.itemCode}</td><td style="color:var(--text)">${i.name}</td><td><span class="badge ${catBadge(i.category)}">${i.category}</span></td><td style="color:var(--red);font-weight:600">${i.quantity}</td><td>${i.minQuantity}</td><td>${i.supplier||'—'}</td><td>${statusBadge(i.status)}</td><td><button class="btn btn-warning btn-sm" onclick="openModal('inv_txn')">Restock</button></td></tr>`).join('') || `<tr><td colspan="8" style="text-align:center;color:var(--green);padding:40px">&#10003; All items sufficiently stocked</td></tr>`;
}

// -- RENDER: Rehabilitation ------------------------------------

async function saveInventoryItem() {
  const name = document.getElementById('ii-name').value.trim();
  if (!name) return showNotif('Item name required', 'error');
  const isEdit = !!editingId['inv_item'];
  await dbPut('inventory_items', { id: isEdit ? editingId['inv_item'] : 0, name, category: document.getElementById('ii-category').value, description: document.getElementById('ii-description').value.trim(), unit: document.getElementById('ii-unit').value, quantity: parseInt(document.getElementById('ii-quantity').value) || 0, minQuantity: parseInt(document.getElementById('ii-minqty').value) || 5, maxQuantity: parseInt(document.getElementById('ii-maxqty').value) || 100, unitCost: parseFloat(document.getElementById('ii-unitcost').value) || 0, supplier: document.getElementById('ii-supplier').value.trim(), location: document.getElementById('ii-location').value.trim(), lastRestocked: document.getElementById('ii-restocked').value });
  closeModal('inv_item'); showNotif(`Item ${isEdit?'updated':'added'}`, 'success'); renderInventory();
}

// -- SAVE: Transaction -----------------------------------------

async function saveTransaction() {
  const itemId = parseInt(document.getElementById('txn-item').value);
  const qty = parseInt(document.getElementById('txn-qty').value) || 0;
  if (!itemId || qty < 1) return showNotif('Item and quantity required', 'error');
  const items = await dbGetAll('inventory');
  const item = items.find(i => i.id == itemId);
  await dbPut('inventory_transactions', { id: 0, itemId, itemCode: item?.itemCode || '', itemName: item?.name || '', type: document.getElementById('txn-type').value, quantity: qty, issuedTo: document.getElementById('txn-issuedto').value.trim(), purpose: document.getElementById('txn-purpose').value.trim(), authorizedBy: document.getElementById('txn-auth').value.trim(), notes: document.getElementById('txn-notes').value.trim() });
  closeModal('inv_txn'); showNotif('Transaction recorded', 'success'); renderInventory();
}

// -- SAVE: Rehab Program ---------------------------------------

const catBadge = c => ({ Security:'badge-red', Medical:'badge-blue', Food:'badge-green', Uniform:'badge-purple', Cleaning:'badge-teal', Equipment:'badge-amber', Office:'badge-gray', Other:'badge-gray' }[c] || 'badge-gray');

const txnBadge = t => ({ Issue:'badge-amber', Return:'badge-green', Restock:'badge-teal', Damaged:'badge-red', Disposed:'badge-gray', Transfer:'badge-blue' }[t] || 'badge-gray');


async function openModal_inv_item(id = null) {
  editingId['inv_item'] = id;
  document.getElementById('inv-item-modal-title').textContent = id ? 'Edit Item' : 'Add Inventory Item';
  if (!id) { ['ii-name','ii-description','ii-supplier','ii-location'].forEach(f=>{const el=document.getElementById(f);if(el)el.value='';}); const q = document.getElementById('ii-quantity'); if (q) q.value = 0; const mq = document.getElementById('ii-minqty'); if (mq) mq.value = 5; const Mq = document.getElementById('ii-maxqty'); if (Mq) Mq.value = 100; const uc = document.getElementById('ii-unitcost'); if (uc) uc.value = 0; }
  if (id) { const items = await dbGetAll('inventory'); const it = items.find(i => i.id == id); if (it) {
    const n = document.getElementById('ii-name'); if (n) n.value = it.name||'';
    const cat = document.getElementById('ii-category'); if (cat) cat.value = it.category||'';
    const desc = document.getElementById('ii-description'); if (desc) desc.value = it.description||'';
    const unit = document.getElementById('ii-unit'); if (unit) unit.value = it.unit||'pcs';
    const qty = document.getElementById('ii-quantity'); if (qty) qty.value = it.quantity||0;
    const minq = document.getElementById('ii-minqty'); if (minq) minq.value = it.minQuantity||5;
    const maxq = document.getElementById('ii-maxqty'); if (maxq) maxq.value = it.maxQuantity||100;
    const uc = document.getElementById('ii-unitcost'); if (uc) uc.value = it.unitCost||0;
    const sup = document.getElementById('ii-supplier'); if (sup) sup.value = it.supplier||'';
    const loc = document.getElementById('ii-location'); if (loc) loc.value = it.location||'';
    const rest = document.getElementById('ii-restocked'); if (rest) rest.value = it.lastRestocked||'';
  } }
}

async function openModal_inv_txn(id = null) {
  editingId['inv_txn'] = id;
  const items = await dbGetAll('inventory');
  const sel = document.getElementById('txn-item'); if (sel) sel.innerHTML = items.map(i=>`<option value="${i.id}">${i.itemCode} - ${i.name} (${i.quantity} ${i.unit})</option>`).join('');
  const auth = document.getElementById('txn-auth'); if (auth) auth.value = currentUser?.name || '';
  ['txn-issuedto','txn-purpose','txn-notes'].forEach(f=>{const el=document.getElementById(f);if(el)el.value='';});
  const qty = document.getElementById('txn-qty'); if (qty) qty.value = 1;
}
</script>

