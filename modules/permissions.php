<!-- PERMISSIONS (ADMIN) -->
      <div class="page" id="page-permissions">
        <div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap;">
          <div class="table-card" style="flex:1;min-width:100%;">
            <div class="table-header">
              <div class="table-title">Manage User Roles & Permissions</div>
              <button class="btn btn-sm btn-secondary" onclick="toggleACLEditor()" style="margin-left:auto;">Advanced</button>
            </div>
            <div id="permissions-roles-list" style="padding:16px;"></div>
          </div>
        </div>
        <div class="table-card" id="acl-editor-section" style="display:none;">
          <div class="table-header">
            <div class="table-title">ACL JSON (Advanced)</div>
          </div>
          <div style="padding:16px;">
            <textarea id="acl-editor" style="width:100%;min-height:240px;background:var(--bg3);border:1px solid var(--border2);padding:12px;border-radius:6px;font-family:var(--mono);font-size:13px;color:var(--text)"></textarea>
            <div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end;">
              <button class="btn btn-secondary" onclick="renderPermissions()">Reload</button>
              <button class="btn btn-primary" onclick="savePermissions()">Save Permissions</button>
            </div>
          </div>
        </div>
      </div>
<script>
async function renderPermissions() {
  if (!isAdmin()) return showNotif('Only administrators can manage permissions','error');
  try {
    const res = await fetch('./api/acl.php');
    const json = await res.json();
    if (!json.success) return showNotif(json.msg || 'Failed to load ACL','error');
    const acl = json.data || {};
    document.getElementById('acl-editor').value = JSON.stringify(acl, null, 2);
    
    // Render role permission cards
    const rolesDiv = document.getElementById('permissions-roles-list');
    const roles = Object.keys(acl).sort();
    rolesDiv.innerHTML = roles.map(role => `
      <div style="background:var(--bg3);border:1px solid var(--border2);border-radius:var(--r2);padding:16px;margin-bottom:16px;">
        <div style="font-weight:600;margin-bottom:12px;color:var(--accent);">${role}</div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
          ${permissionStores.map(store => `
            <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--r);padding:10px;">
              <div style="font-size:12px;font-weight:600;margin-bottom:8px;color:var(--text2);">${store}</div>
              <div style="display:flex;flex-direction:column;gap:6px;">
                ${permissionActions.map(action => `
                  <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;">
                    <input type="checkbox" class="perm-toggle" data-role="${role}" data-store="${store}" data-action="${action}" onchange="updatePermissionUI('${role}')" ${(acl[role]?.[store]?.[action] === true) ? 'checked' : ''}>
                    ${action.charAt(0).toUpperCase() + action.slice(1)}
                  </label>
                `).join('')}
              </div>
            </div>
          `).join('')}
        </div>
        <div style="margin-top:12px;display:flex;gap:8px;">
          <button class="btn btn-sm btn-success" onclick="saveRolePermissions('${role}')">Save ${role}</button>
        </div>
      </div>
    `).join('');
  } catch (e) {
    showNotif('Failed to load ACL: ' + e.message, 'error');
  }
}


async function saveRolePermissions(role) {
  if (!isAdmin()) return showNotif('Only administrators can save permissions','error');
  try {
    // Get current ACL
    const res = await fetch('./api/acl.php');
    const json = await res.json();
    const acl = json.data || {};
    
    // Update this role's permissions from checkboxes
    const checkboxes = document.querySelectorAll(`.perm-toggle[data-role="${role}"]`);
    if (!acl[role]) acl[role] = {};
    checkboxes.forEach(cb => {
      const store = cb.getAttribute('data-store');
      const action = cb.getAttribute('data-action');
      if (!acl[role][store]) acl[role][store] = {};
      acl[role][store][action] = cb.checked;
    });
    
    // Save updated ACL
    const saveRes = await fetch('./api/acl.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(acl) });
    const saveJson = await saveRes.json();
    if (!saveJson.success) return showNotif(saveJson.msg || 'Failed to save','error');
    showNotif(`${role} permissions saved`, 'success');
    applyRoleMenuFiltering(); renderViewToggle();
  } catch (e) {
    showNotif('Failed to save permissions: ' + e.message, 'error');
  }
}


async function savePermissions() {
  if (!isAdmin()) return showNotif('Only administrators can save permissions','error');
  try {
    const payload = JSON.parse(document.getElementById('acl-editor').value || '{}');
    const res = await fetch('./api/acl.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
    const json = await res.json();
    if (!json.success) return showNotif(json.msg || 'Failed to save ACL','error');
    showNotif('Permissions saved', 'success');
    renderPermissions();
    applyRoleMenuFiltering(); renderViewToggle();
  } catch (e) {
    showNotif('Failed to save ACL: ' + e.message, 'error');
  }
}

// ============================================================
// AUTH
// ============================================================

const permissionActions = ['get', 'save', 'delete'];

const permissionStores = ['prisoners', 'cells', 'visitations', 'incidents', 'movements', 'court', 'reports', 'medical', 'shifts', 'shift_swaps', 'inventory_items', 'inventory_transactions', 'rehab_programs', 'rehab_enrollments', 'transfer_requests'];


</script>

