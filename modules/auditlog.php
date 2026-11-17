<!-- AUDIT LOG -->
      <div class="page" id="page-auditlog">
        <div class="table-card">
          <div class="table-header">
            <div class="table-title">Audit Log</div>
            <div class="filter-group">
              <label>From:</label>
              <input type="date" id="audit-from-date" onchange="renderAudit()">
              <label>To:</label>
              <input type="date" id="audit-to-date" onchange="renderAudit()">
              <button class="btn-export" onclick="exportAuditToExcel()" title="Export to Excel">📊 Excel</button>
              <button class="btn-export" onclick="exportAuditToCSV()" title="Export to CSV">📄 CSV</button>
            </div>
          </div>
          <table><thead><tr><th>Timestamp</th><th>User</th><th>Action</th><th>Module</th><th>Details</th><th>IP</th><th>Actions</th></tr></thead><tbody id="audit-tbody"></tbody></table>
        </div>
      </div>

