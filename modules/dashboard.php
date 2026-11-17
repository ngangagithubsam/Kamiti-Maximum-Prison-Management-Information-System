<!-- DASHBOARD -->
      <div class="page active" id="page-dashboard">
        <div class="stats-grid">
          <div class="stat-card"><div class="stat-label">Total Prisoners</div><div class="stat-value text-accent" id="stat-total">0</div><div class="stat-sub">All registered inmates</div></div>
          <div class="stat-card"><div class="stat-label">Currently Housed</div><div class="stat-value" style="color:var(--green)" id="stat-housed">0</div><div class="stat-sub">Active in facility</div></div>
          <div class="stat-card"><div class="stat-label">Pending Releases</div><div class="stat-value" style="color:var(--blue)" id="stat-pending-releases">0</div><div class="stat-sub">Awaiting release approval</div></div>
          <div class="stat-card"><div class="stat-label">Incidents (30d)</div><div class="stat-value" style="color:var(--amber)" id="stat-incidents">0</div><div class="stat-sub">Reported incidents</div></div>
        </div>
        <div class="stats-grid">
          <div class="stat-card"><div class="stat-label">Visitations Today</div><div class="stat-value" style="color:var(--purple)" id="stat-visits">0</div><div class="stat-sub">Scheduled visits</div></div>
          <div class="stat-card"><div class="stat-label">Shifts Today</div><div class="stat-value" style="color:var(--teal)" id="stat-shifts">0</div><div class="stat-sub">Staff on duty</div></div>
          <div class="stat-card"><div class="stat-label">Low Stock Items</div><div class="stat-value" style="color:var(--red)" id="stat-lowstock">0</div><div class="stat-sub">Need restocking</div></div>
          <div class="stat-card"><div class="stat-label">Active Rehab</div><div class="stat-value" style="color:#fb923c" id="stat-rehab">0</div><div class="stat-sub">Program enrollments</div></div>
        </div>
        <div class="grid-2">
          <div class="table-card"><div class="table-header"><div class="table-title">Recent Admissions</div></div><table><thead><tr><th>ID</th><th>Name</th><th>Cell</th><th>Date</th></tr></thead><tbody id="dash-admissions-body"></tbody></table></div>
          <div class="table-card"><div class="table-header"><div class="table-title">Recent Incidents</div></div><table><thead><tr><th>Type</th><th>Prisoner</th><th>Severity</th><th>Date</th></tr></thead><tbody id="dash-incidents-body"></tbody></table></div>
        </div>
        <div style="margin-top:20px" class="table-card"><div class="table-header"><div class="table-title">Activity Log — Last 10 Actions</div></div><div id="dash-activity" style="padding:12px 16px" class="log-list"></div></div>
      </div>
<script>
async function renderDashboard() {
  renderDashboardActivity();
  const stats = await getStats();
  document.getElementById('stat-total').textContent    = stats.total_prisoners || 0;
  document.getElementById('stat-housed').textContent   = stats.active_prisoners || 0;
  if(document.getElementById('stat-pending-releases')) document.getElementById('stat-pending-releases').textContent = stats.pending_release_requests || 0;
  document.getElementById('stat-incidents').textContent= stats.incidents_30d || 0;
  document.getElementById('stat-visits').textContent   = stats.visits_today || 0;
  if(document.getElementById('stat-shifts')) document.getElementById('stat-shifts').textContent = stats.shifts_today || 0;
  if(document.getElementById('stat-lowstock')) document.getElementById('stat-lowstock').textContent = stats.low_stock_items || 0;
  if(document.getElementById('stat-rehab')) document.getElementById('stat-rehab').textContent = stats.active_rehab || 0;

  const [prisoners, incidents, auditFallback, auditDirect] = await Promise.all([dbGetAll('prisoners'), dbGetAll('incidents'), dbGetAll('dashboard_audit'), dbGetAll('audit')]);
  const audit = (Array.isArray(stats.recent_activity) && stats.recent_activity.length) ? stats.recent_activity : (auditFallback.length ? auditFallback : auditDirect);

  const admTbody = document.getElementById('dash-admissions-body');
  const recent = [...prisoners].sort((a,b)=>new Date(b.admdate)-new Date(a.admdate)).slice(0,5);
  admTbody.innerHTML = recent.map(p=>`<tr><td class="text-mono text-accent">${p.prisonerId}</td><td style="color:var(--text)">${p.firstname} ${p.lastname}</td><td>${p.cell||'—'}</td><td>${fmtDate(p.admdate)}</td></tr>`).join('') || '<tr><td colspan="4" style="text-align:center;color:var(--text3)">No data</td></tr>';

  const incTbody = document.getElementById('dash-incidents-body');
  const recentInc = [...incidents].sort((a,b)=>new Date(b.incidentDate||b.created_at)-new Date(a.incidentDate||a.created_at)).slice(0,5);
  incTbody.innerHTML = recentInc.map(i=>`<tr><td>${i.type}</td><td style="color:var(--text)">${i.prisonerName}</td><td>${severityBadge(i.severity)}</td><td>${fmtDate(i.incidentDate)}</td></tr>`).join('') || '<tr><td colspan="4" style="text-align:center;color:var(--text3)">No incidents</td></tr>';

  const actDiv = document.getElementById('dash-activity');
  const recent10 = [...audit].sort((a,b)=>new Date(b.ts)-new Date(a.ts) || b.id-a.id).slice(0,10);
  actDiv.innerHTML = recent10.map(a=>`<div class="log-item"><div class="log-icon" style="background:var(--bg4)"><svg viewBox="0 0 24 24" fill="var(--accent)" width="16" height="16"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg></div><div class="log-text"><div class="log-title">${a.action} — ${a.module}</div><div class="log-meta">${a.user} · ${a.details||''}</div></div><div class="log-time">${fmtDateTime(a.ts)}</div></div>`).join('') || '<div style="color:var(--text3);font-size:13px;padding:8px">No activity yet</div>';

  updateNavCounts();
}

async function renderDashboardActivity() {
  const target = document.getElementById('dash-activity');
  if (!target) return;
  try {
    const response = await apiCall(`api/data.php?action=get&store=dashboard_audit&_=${Date.now()}`);
    const records = response.success && Array.isArray(response.data) ? response.data : [];
    const recent10 = records.sort((a, b) => new Date(b.ts) - new Date(a.ts) || Number(b.id) - Number(a.id)).slice(0, 10);
    target.innerHTML = recent10.map(a => `<div class="log-item"><div class="log-icon" style="background:var(--bg4)"><svg viewBox="0 0 24 24" fill="var(--accent)" width="16" height="16"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg></div><div class="log-text"><div class="log-title">${a.action || 'Activity'} - ${a.module || 'System'}</div><div class="log-meta">${a.user || 'System'} | ${a.details || ''}</div></div><div class="log-time">${fmtDateTime(a.ts)}</div></div>`).join('') || '<div style="color:var(--text3);font-size:13px;padding:8px">No activity recorded.</div>';
  } catch (error) {
    target.innerHTML = '<div style="color:var(--text3);font-size:13px;padding:8px">Activity log unavailable.</div>';
  }
}


</script>

