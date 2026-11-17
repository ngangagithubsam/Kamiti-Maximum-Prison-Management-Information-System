<div class="page" id="page-reports">
  <style>
    .analytics-header { padding: 20px; background: linear-gradient(135deg, var(--blue) 0%, var(--purple) 100%); color: white; border-radius: 8px; margin-bottom: 20px; }
    .analytics-header h1 { margin: 0; font-size: 28px; }
    .analytics-header p { margin: 5px 0 0 0; opacity: 0.9; }
    .dashboard-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .dashboard-card { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); position: relative; overflow: hidden; }
    .dashboard-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #3498db, #2ecc71); }
    .dashboard-card.pending::before { background: linear-gradient(90deg, #f39c12, #e67e22); }
    .dashboard-card.approved::before { background: linear-gradient(90deg, #2ecc71, #27ae60); }
    .dashboard-card.rejected::before { background: linear-gradient(90deg, #e74c3c, #c0392b); }
    .dashboard-card.archived::before { background: linear-gradient(90deg, #95a5a6, #7f8c8d); }
    .card-icon { font-size: 32px; margin-bottom: 12px; }
    .card-label { font-size: 12px; color: var(--text2); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
    .card-value { font-size: 28px; font-weight: bold; color: var(--text); margin-bottom: 8px; }
    .card-change { font-size: 12px; color: var(--text2); }
    .filters-section, .chart-card { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 24px; }
    .filters-title, .chart-title { font-size: 16px; font-weight: 600; color: var(--text); margin-bottom: 16px; padding-bottom: 10px; border-bottom: 2px solid var(--border); }
    .filters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
    .filter-control { display: flex; flex-direction: column; gap: 6px; }
    .filter-control label { font-size: 12px; font-weight: 600; color: var(--text2); }
    .filter-control input, .filter-control select { padding: 8px 12px; border: 1px solid var(--border); border-radius: 4px; font-size: 13px; }
    .action-buttons { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; }
    .report-selection { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-top: 16px; }
    .report-selection label { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text2); }
    .chart-container { position: relative; height: 300px; }
    .chart-container.large { height: 400px; }
    .stats-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .stat-box { background: white; border-radius: 8px; padding: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; }
    .stat-box-label { font-size: 12px; color: var(--text2); text-transform: uppercase; margin-bottom: 8px; }
    .stat-box-value { font-size: 24px; font-weight: bold; color: var(--blue); }
    .stat-box-subtitle { font-size: 11px; color: var(--text3); margin-top: 4px; }
    .charts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(480px, 1fr)); gap: 24px; }
    .loading-pill { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text2); }
    .empty-state { text-align: center; color: var(--text2); padding: 18px 0; }
    @media (max-width: 1200px) { .charts-grid { grid-template-columns: 1fr; } }
  </style>

  <div class="analytics-header">
    <h1>📊 Reports Analytics Dashboard</h1>
    <p>Live prison report analytics with filters, charts, statistics, and exports</p>
  </div>

  <div class="dashboard-cards" id="dashboardCards">
    <div class="dashboard-card"><div class="card-icon">👥</div><div class="card-label">Total Prison Population</div><div class="card-value" id="card-total">0</div><div class="card-change">Current records</div></div>
    <div class="dashboard-card pending"><div class="card-icon">●</div><div class="card-label">Remand Prisoners</div><div class="card-value" id="card-pending">0</div><div class="card-change">Pre-trial custody</div></div>
    <div class="dashboard-card approved"><div class="card-icon">▣</div><div class="card-label">Sentenced Prisoners</div><div class="card-value" id="card-approved">0</div><div class="card-change">Sentence records</div></div>
    <div class="dashboard-card rejected"><div class="card-icon">↗</div><div class="card-label">Released Prisoners</div><div class="card-value" id="card-rejected">0</div><div class="card-change">Release records</div></div>
    <div class="dashboard-card archived"><div class="card-icon">⇄</div><div class="card-label">Transferred Prisoners</div><div class="card-value" id="card-archived">0</div><div class="card-change">Transfer records</div></div>
    <div class="dashboard-card"><div class="card-icon">◷</div><div class="card-label">Incidents This Month</div><div class="card-value" id="card-thisMonth">0</div><div class="card-change">Operational activity</div></div>
  </div>

  <div class="filters-section">
    <div class="filters-title">🔍 Filters</div>
    <div class="filters-grid">
      <div class="filter-control"><label>Date From</label><input type="date" id="filterDateFrom" onchange="initReportsDashboard()"></div>
      <div class="filter-control"><label>Date To</label><input type="date" id="filterDateTo" onchange="initReportsDashboard()"></div>
      <div class="filter-control"><label>Prisoner Type</label><input id="filterCategory" placeholder="Remand or sentenced" onchange="initReportsDashboard()"></div>
      <div class="filter-control"><label>Security Level</label><input id="filterReportType" placeholder="Any security level" onchange="initReportsDashboard()"></div>
      <div class="filter-control"><label>Prisoner Status</label><select id="filterStatus" onchange="initReportsDashboard()"><option value="">All Statuses</option><option>Active</option><option>Released</option><option>Transferred</option><option>Deceased</option></select></div>
      <div class="filter-control"><label>Prisoner</label><input id="filterOfficer" placeholder="Prisoner ID" onchange="initReportsDashboard()"></div>
      <div class="filter-control"><label>Block</label><input id="filterBlock" placeholder="Block name" onchange="initReportsDashboard()"></div>
    </div>
    <div class="report-selection">
      <label><input type="checkbox" class="report-select" data-report="reports-by-category" checked> Reports by Category</label>
      <label><input type="checkbox" class="report-select" data-report="status-report"> Status Report</label>
      <label><input type="checkbox" class="report-select" data-report="officer-report"> Officer Report</label>
      <label><input type="checkbox" class="report-select" data-report="prison-block-report"> Prison Block Report</label>
      <label><input type="checkbox" class="report-select" data-report="monthly-report"> Monthly Report</label>
      <label><input type="checkbox" class="report-select" data-report="daily-activity"> Daily Activity</label>
      <label><input type="checkbox" class="report-select" data-report="department-performance"> Department Performance</label>
      <label><input type="checkbox" class="report-select" data-report="complete-report" checked> Complete Report</label>
    </div>
  </div>

  <div class="action-buttons">
    <button class="btn btn-primary" onclick="exportReportsPDF()">📄 Export PDF</button>
    <button class="btn btn-info" onclick="exportReportsExcel()">📊 Export Excel</button>
    <button class="btn btn-secondary" onclick="exportReportsCSV()">📥 Export CSV</button>
    <button class="btn btn-secondary" onclick="printReportsReport()">🖨️ Print Report</button>
    <button class="btn btn-success" onclick="initReportsDashboard()">🔄 Refresh</button>
  </div>

  <div class="loading-pill" id="reports-loading" style="display:none; margin-bottom: 16px;">⏳ Loading report data…</div>

  <div class="stats-summary">
    <div class="stat-box"><div class="stat-box-label">Upcoming Releases</div><div class="stat-box-value" id="stat-avgDay">0</div><div class="stat-box-subtitle">Next 90 days</div></div>
    <div class="stat-box"><div class="stat-box-label">Pending Court Hearings</div><div class="stat-box-value" id="stat-category" style="font-size: 16px;">0</div><div class="stat-box-subtitle">Next 90 days</div></div>
    <div class="stat-box"><div class="stat-box-label">Current Cell Occupancy</div><div class="stat-box-value" id="stat-officer" style="font-size: 14px;">0</div><div class="stat-box-subtitle">Occupied spaces</div></div>
    <div class="stat-box"><div class="stat-box-label">Visits This Month</div><div class="stat-box-value" id="stat-month">0</div><div class="stat-box-subtitle">Scheduled visits</div></div>
    <div class="stat-box"><div class="stat-box-label">Available Cell Capacity</div><div class="stat-box-value" id="stat-approval">0</div><div class="stat-box-subtitle">Unoccupied spaces</div></div>
    <div class="stat-box"><div class="stat-box-label">Total Cell Capacity</div><div class="stat-box-value" id="stat-rejection">0</div><div class="stat-box-subtitle">Configured capacity</div></div>
  </div>

  <div class="charts-grid">
    <div class="chart-card"><div class="chart-title">📈 Reports Submitted Over Time</div><div class="chart-container"><canvas id="chartTimeline"></canvas></div></div>
    <div class="chart-card"><div class="chart-title">📊 Reports by Category</div><div class="chart-container"><canvas id="chartCategory"></canvas></div></div>
    <div class="chart-card"><div class="chart-title">🔄 Report Status Distribution</div><div class="chart-container"><canvas id="chartStatus"></canvas></div></div>
    <div class="chart-card"><div class="chart-title">👤 Top 10 Officers</div><div class="chart-container"><canvas id="chartOfficer"></canvas></div></div>
    <div class="chart-card"><div class="chart-title">🏢 Reports by Prison Block</div><div class="chart-container"><canvas id="chartBlock"></canvas></div></div>
    <div class="chart-card"><div class="chart-title">📅 Monthly Reports by Category</div><div class="chart-container large"><canvas id="chartMonthly"></canvas></div></div>
    <div class="chart-card"><div class="chart-title">📉 Daily Report Activity (Last 30 Days)</div><div class="chart-container large"><canvas id="chartDaily"></canvas></div></div>
    <div class="chart-card"><div class="chart-title">🎯 Department Performance</div><div class="chart-container large"><canvas id="chartDepartment"></canvas></div></div>
  </div>

  <div class="chart-card">
    <div class="chart-title">🗂️ Filtered Prisoner Records</div>
    <div id="reports-table-placeholder" class="empty-state">Loading report records…</div>
    <div id="reports-table-wrapper" style="display:none">
      <table class="table" style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
          <tr style="background:#f5f7fa;">
            <th style="padding:10px;border-bottom:1px solid #e5e7eb;">Prisoner ID</th>
            <th style="padding:10px;border-bottom:1px solid #e5e7eb;">Full Name</th>
            <th style="padding:10px;border-bottom:1px solid #e5e7eb;">Prisoner Type</th>
            <th style="padding:10px;border-bottom:1px solid #e5e7eb;">Status</th>
            <th style="padding:10px;border-bottom:1px solid #e5e7eb;">Security</th>
            <th style="padding:10px;border-bottom:1px solid #e5e7eb;">Block</th>
            <th style="padding:10px;border-bottom:1px solid #e5e7eb;">Admission Date</th>
          </tr>
        </thead>
        <tbody id="reports-table-body"></tbody>
      </table>
    </div>
  </div>
</div>

<script>
let reportsDashboardCharts = {};
let reportsDashboardState = { payload: null, rows: [] };

function notifyReports(message, type = 'info') {
  if (typeof showNotif === 'function') {
    showNotif(message, type);
  }
}

function escapeReportsHtml(value) {
  return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function getReportsFilters() {
  return {
    dateFrom: document.getElementById('filterDateFrom')?.value || '',
    dateTo: document.getElementById('filterDateTo')?.value || '',
    category: document.getElementById('filterCategory')?.value || '',
    reportType: document.getElementById('filterReportType')?.value || '',
    status: document.getElementById('filterStatus')?.value || '',
    officer: document.getElementById('filterOfficer')?.value || '',
    block: document.getElementById('filterBlock')?.value || '',
  };
}

function formatReportDate(value) {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return String(value);
  return date.toLocaleString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function safeText(value) {
  return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function buildExportSections(payload, selectedTypes) {
  const rows = payload?.table || [];
  const stats = payload?.statistics || {};
  const filters = getReportsFilters();
  const appliedFilters = Object.entries(filters).filter(([_, v]) => v).map(([key, value]) => `${key.replace(/([A-Z])/g, ' $1')}: ${value}`);

  const summaryRows = [
    { Metric: 'Total Reports', Value: stats.total ?? rows.length },
    { Metric: 'Pending', Value: stats.pending ?? 0 },
    { Metric: 'Approved', Value: stats.approved ?? 0 },
    { Metric: 'Rejected', Value: stats.rejected ?? 0 },
    { Metric: 'Archived', Value: stats.archived ?? 0 },
    { Metric: 'Reports This Month', Value: stats.thisMonth ?? 0 },
    { Metric: 'Approval Rate', Value: `${stats.approvalRate ?? 0}%` },
    { Metric: 'Rejection Rate', Value: `${stats.rejectionRate ?? 0}%` },
    { Metric: 'Average Reports/Day', Value: stats.avgPerDay ?? 0 },
    { Metric: 'Top Category', Value: stats.topCategory || '—' },
    { Metric: 'Top Officer', Value: stats.topOfficer || '—' },
  ];

  return {
    filters: appliedFilters,
    summaryRows,
    detailRows: rows.map(row => ({
      'Photo': row.photo_url || '',
      'Report ID': row.reportId || row.id || '',
      'Title': row.title || '',
      'Type': row.type || 'General',
      'Status': row.status || 'Pending',
      'Officer': row.author || '',
      'Block': row.block || '',
      'Department': row.department || '',
      'Processing Time (hrs)': row.processing_time_hours ?? 0,
      'Date': formatReportDate(row.reportDate || row.created_at),
    })),
    charts: payload?.charts || {},
  };
}

function createReportExportHtml(payload, selectedTypes) {
  const sections = buildExportSections(payload, selectedTypes);
  const filterHtml = sections.filters.length ? `<p><strong>Applied Filters:</strong> ${sections.filters.join(' | ')}</p>` : '<p><strong>Applied Filters:</strong> None</p>';
  const summaryHtml = `<table style="width:100%;border-collapse:collapse;margin-top:12px;"><tbody>${sections.summaryRows.map(r => `<tr><td style="padding:8px;border:1px solid #dfe3e8;font-weight:600;width:220px;">${safeText(r.Metric)}</td><td style="padding:8px;border:1px solid #dfe3e8;">${safeText(r.Value)}</td></tr>`).join('')}</tbody></table>`;
  const tableHeaders = Object.keys(sections.detailRows[0] || {});
  const tableHtml = sections.detailRows.length ? `<table style="width:100%;border-collapse:collapse;margin-top:20px;"><thead><tr>${tableHeaders.map(header => `<th style="padding:10px;border:1px solid #dfe3e8;background:#f5f7fa;text-align:left;">${safeText(header)}</th>`).join('')}</tr></thead><tbody>${sections.detailRows.map(row => `<tr>${tableHeaders.map(col => `<td style="padding:8px;border:1px solid #dfe3e8;">${col === 'Photo' && row[col] ? `<img src="${safeText(row[col])}" alt="Prisoner photo" style="width:56px;height:70px;object-fit:cover;">` : safeText(row[col])}</td>`).join('')}</tr>`).join('')}</tbody></table>` : '<p style="margin-top:20px;color:#777;">No detailed records available for the selected filters.</p>';

  return `<!doctype html><html><head><meta charset="utf-8"><title>Prison Reports</title><style>body{font-family:Arial,Helvetica,sans-serif;padding:24px;color:#222;background:#fff;}header{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px;padding:18px 0;border-bottom:1px solid #e5e7eb;}header .brand{display:flex;align-items:center;gap:12px;}header .brand-icon{width:52px;height:52px;border-radius:14px;background:#0a1f40;display:flex;align-items:center;justify-content:center;}header .brand-icon svg{width:26px;height:26px;fill:#fff;}header .brand-text{display:flex;flex-direction:column;gap:2px;}header .brand-text .title{font-size:16px;font-weight:700;letter-spacing:0.5px;}header .brand-text .subtitle{font-size:10px;color:#555;text-transform:uppercase;letter-spacing:1px;}header .meta{text-align:right;min-width:200px;}header .meta .meta-title{font-size:14px;font-weight:700;}header .meta .meta-sub{font-size:11px;color:#555;}h1{font-size:28px;margin:0 0 6px 0;}p{margin:6px 0;line-height:1.5;}table{font-size:12px;}footer{margin-top:32px;font-size:11px;color:#777;display:flex;justify-content:space-between;align-items:center;}@media print{html,body{margin:0;padding:0;}body{padding-top:124px;}@page{size:auto;margin:15mm;}header{position:fixed;top:0;left:0;right:0;background:#fff;padding:16px 24px;box-shadow:0 1px 1px rgba(0,0,0,0.08);z-index:999;}header + section{margin-top:120px;}footer{position:fixed;bottom:0;left:0;right:0;background:#fff;padding:12px 24px;box-shadow:0 -1px 1px rgba(0,0,0,0.08);page-break-inside:avoid;}header,footer{page-break-inside:avoid;}table{page-break-inside:auto;}tr{page-break-inside:avoid;page-break-after:auto;}th{background:#f5f7fa !important;} .watermark{position:fixed;top:45%;left:50%;transform:translate(-50%,-50%) rotate(-30deg);font-size:64px;color:rgba(0,0,0,0.05);pointer-events:none;z-index:0;}</style></head><body><div class="watermark">KAMITI REPORT</div><header><div class="brand"><div class="brand-icon"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C8.13 2 5 5.13 5 9c0 3.22 2.26 5.88 5.2 6.75L10 22h4l-.2-6.25C16.74 14.88 19 12.22 19 9c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 110-5 2.5 2.5 0 010 5z"/></svg></div><div class="brand-text"><div class="title">INMATE INFO PLUS</div><div class="subtitle">Kamiti Prison Mgt Sys</div></div></div><div class="meta"><div class="meta-title">Prison Reports</div><div class="meta-sub">Generated: ${safeText(new Date().toLocaleString())}</div></div></header><section><h1>Executive Summary</h1>${filterHtml}${summaryHtml}</section><section><h2 style="margin-top:24px;">Detailed Records</h2>${tableHtml}</section><footer><div>Prepared By: ${safeText(currentUser?.name || 'System')}</div><div>Approved By: ____________________________</div></footer></body></html>`;
}

function createExcelWorkbook(payload) {
  const sections = buildExportSections(payload, getSelectedReportTypes());
  if (window.XLSX && typeof XLSX !== 'undefined') {
    const workbook = XLSX.utils.book_new();
    const summaryData = [['Metric', 'Value'], ...sections.summaryRows.map(row => [row.Metric, String(row.Value)])];
    const detailHeaders = Object.keys(sections.detailRows[0] || {});
    const detailData = [detailHeaders, ...sections.detailRows.map(row => detailHeaders.map(header => row[header] ?? ''))];

    const summarySheet = XLSX.utils.aoa_to_sheet(summaryData);
    const detailSheet = XLSX.utils.aoa_to_sheet(detailData);

    summarySheet['!cols'] = [{ wch: 28 }, { wch: 24 }];
    detailSheet['!cols'] = detailHeaders.map(() => ({ wch: 18 }));

    if (!summarySheet['!cols']) summarySheet['!cols'] = [{ wch: 28 }, { wch: 24 }];
    if (!detailSheet['!cols']) detailSheet['!cols'] = detailHeaders.map(() => ({ wch: 18 }));

    detailHeaders.forEach((_, colIndex) => {
      const cellAddress = XLSX.utils.encode_cell({ c: colIndex, r: 0 });
      if (detailSheet[cellAddress]) {
        detailSheet[cellAddress].s = {
          font: { bold: true },
          fill: { fgColor: { rgb: 'FFD9E1F2' } },
          alignment: { horizontal: 'center', vertical: 'center' },
        };
      }
    });

    XLSX.utils.book_append_sheet(workbook, summarySheet, 'Executive Summary');
    XLSX.utils.book_append_sheet(workbook, detailSheet, 'Detailed Records');
    return workbook;
  }

  const sheet = sections.detailRows;
  const sheetRows = sheet.map(row => Object.values(row));
  const headers = Object.keys(sheet[0] || {});
  const dataRows = sheetRows.map(row => `<Row>${row.map(cell => `<Cell><Data ss:Type="String">${safeText(cell)}</Data></Cell>`).join('')}</Row>`).join('');
  return `<?xml version="1.0"?><?mso-application progid="Excel.Sheet"?>
  <Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
            xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:x="urn:schemas-microsoft-com:office:excel"
            xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
    <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office"><Author>Prison System</Author><Created>${new Date().toISOString()}</Created></DocumentProperties>
    <Styles>
      <Style ss:ID="Header"><Font ss:Bold="1"/></Style>
      <Style ss:ID="Cell"><NumberFormat ss:Format="@"/></Style>
    </Styles>
    <Worksheet ss:Name="Executive Summary">
      <Table>
        ${sections.summaryRows.map(row => `<Row><Cell ss:StyleID="Cell"><Data ss:Type="String">${safeText(row.Metric)}</Data></Cell><Cell ss:StyleID="Cell"><Data ss:Type="String">${safeText(row.Value)}</Data></Cell></Row>`).join('')}
      </Table>
    </Worksheet>
    <Worksheet ss:Name="Detailed Records">
      <Table>
        <Row ss:StyleID="Header">${headers.map(header => `<Cell><Data ss:Type="String">${safeText(header)}</Data></Cell>`).join('')}</Row>
        ${dataRows}
      </Table>
    </Worksheet>
  </Workbook>`;
}

function getSelectedReportTypes() {
  return Array.from(document.querySelectorAll('.report-select:checked')).map(item => item.getAttribute('data-report'));
}

function setReportsLoading(isLoading) {
  const loader = document.getElementById('reports-loading');
  if (loader) {
    loader.style.display = isLoading ? 'inline-flex' : 'none';
  }
}

function clearChart(canvasId) {
  const chart = reportsDashboardCharts[canvasId];
  if (chart) {
    chart.destroy();
    delete reportsDashboardCharts[canvasId];
  }
  const canvas = document.getElementById(canvasId);
  const parent = canvas?.parentElement;
  if (!parent) return;
  if (canvas) {
    canvas.style.display = 'none';
  }
  if (!parent.querySelector('.reports-empty-state')) {
    const empty = document.createElement('div');
    empty.className = 'reports-empty-state empty-state';
    empty.textContent = 'No reports found for the selected filters.';
    parent.appendChild(empty);
  }
}

function renderChart(canvasId, config, fallbackMessage = 'No reports found for the selected filters.') {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  const parent = canvas.parentElement;
  if (!parent) return;

  const emptyMessage = parent.querySelector('.reports-empty-state');
  if (emptyMessage) {
    emptyMessage.remove();
  }

  if (!config || !config.labels || !config.labels.length) {
    clearChart(canvasId);
    return;
  }

  canvas.style.display = 'block';

  if (!window.Chart) {
    parent.innerHTML = '<div class="empty-state">Chart library is unavailable.</div>';
    return;
  }

  if (reportsDashboardCharts[canvasId]) {
    reportsDashboardCharts[canvasId].destroy();
  }

  const ctx = canvas.getContext('2d');
  const chartConfig = {
    type: config.type || 'bar',
    data: {
      labels: config.labels || [],
      datasets: config.datasets || [],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: true, position: 'bottom' } },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0 } },
      },
    },
  };

  if (config.options && config.options.indexAxis) {
    chartConfig.options.indexAxis = config.options.indexAxis;
  }

  reportsDashboardCharts[canvasId] = new Chart(ctx, chartConfig);
}

function setReportsText(id, value) {
  const el = document.getElementById(id);
  if (el) {
    el.textContent = value;
  }
}

function renderSummaryCards(stats) {
  const operational = stats.operational || {};
  setReportsText('card-total', stats.total ?? 0);
  setReportsText('card-pending', operational.remand ?? stats.pending ?? 0);
  setReportsText('card-approved', operational.sentenced ?? stats.approved ?? 0);
  setReportsText('card-rejected', operational.released ?? stats.rejected ?? 0);
  setReportsText('card-archived', operational.transferred ?? stats.archived ?? 0);
  setReportsText('card-thisMonth', operational.incidentsThisMonth ?? stats.thisMonth ?? 0);
  setReportsText('card-total-change', 'Live database totals');
  setReportsText('month-change', 'Current month');

  const total = stats.total || 1;
  setReportsText('pending-pct', Math.round(((stats.pending || 0) / total) * 100) + '%');
  setReportsText('approved-pct', Math.round(((stats.approved || 0) / total) * 100) + '%');
  setReportsText('rejected-pct', Math.round(((stats.rejected || 0) / total) * 100) + '%');
  setReportsText('archived-pct', Math.round(((stats.archived || 0) / total) * 100) + '%');

  setReportsText('stat-avgDay', operational.upcomingReleases ?? stats.avgPerDay ?? 0);
  setReportsText('stat-category', operational.pendingHearings ?? stats.topCategory ?? 0);
  setReportsText('stat-officer', operational.occupied ?? stats.topOfficer ?? 0);
  setReportsText('stat-month', operational.visitsThisMonth ?? stats.thisMonth ?? 0);
  setReportsText('stat-approval', operational.available ?? stats.approvalRate ?? 0);
  setReportsText('stat-rejection', operational.capacity ?? stats.rejectionRate ?? 0);
}

function renderTable(rows) {
  const placeholder = document.getElementById('reports-table-placeholder');
  const wrapper = document.getElementById('reports-table-wrapper');
  const body = document.getElementById('reports-table-body');
  if (!body) return;

  if (!rows || !rows.length) {
    placeholder.style.display = 'block';
    wrapper.style.display = 'none';
    placeholder.textContent = 'No data available for the selected period.';
    return;
  }

  placeholder.style.display = 'none';
  wrapper.style.display = 'block';
  body.innerHTML = rows.map(row => `
    <tr>
      <td style="padding:10px;border-bottom:1px solid #f0f2f5;">${escapeReportsHtml(row.prisonerId || row.reportId || row.id || '—')}</td>
      <td style="padding:10px;border-bottom:1px solid #f0f2f5;">${escapeReportsHtml(row.title || '—')}</td>
      <td style="padding:10px;border-bottom:1px solid #f0f2f5;">${escapeReportsHtml(row.prisonerType || row.type || '—')}</td>
      <td style="padding:10px;border-bottom:1px solid #f0f2f5;">${escapeReportsHtml(row.status || 'Pending')}</td>
      <td style="padding:10px;border-bottom:1px solid #f0f2f5;">${escapeReportsHtml(row.security || '—')}</td>
      <td style="padding:10px;border-bottom:1px solid #f0f2f5;">${escapeReportsHtml(row.block || 'Unassigned')}</td>
      <td style="padding:10px;border-bottom:1px solid #f0f2f5;">${escapeReportsHtml(row.reportDate ? row.reportDate : (row.created_at || '—'))}</td>
    </tr>
  `).join('');
}

function buildExportRows(payload, selectedTypes) {
  const rows = payload?.table || [];
  const stats = payload?.statistics || {};
  if (!selectedTypes || !selectedTypes.length) {
    selectedTypes = ['complete-report'];
  }

  if (selectedTypes.includes('complete-report')) {
    return rows.map(row => ({
      'Report ID': row.reportId || row.id || '',
      'Title': row.title || '',
      'Type': row.type || '',
      'Status': row.status || 'Pending',
      'Officer': row.author || '',
      'Block': row.block || '',
      'Date': row.reportDate || row.created_at || '',
      'Department': row.department || '',
      'Processing Time (hrs)': row.processing_time_hours || 0,
    }));
  }

  const categories = Object.entries(stats.categoryCounts || {}).map(([key, value]) => ({ 'Category': key, 'Count': value }));
  const statuses = Object.entries(stats.statusCounts || {}).map(([key, value]) => ({ 'Status': key, 'Count': value }));
  const officers = Object.entries(stats.officerCounts || {}).map(([key, value]) => ({ 'Officer': key, 'Count': value }));
  const blocks = Object.entries(rows.reduce((acc, row) => { const block = row.block || 'Unassigned'; acc[block] = (acc[block] || 0) + 1; return acc; }, {})).map(([key, value]) => ({ 'Block': key, 'Count': value }));
  const monthly = Object.entries(stats.monthlyCounts || {}).map(([key, value]) => ({ 'Month': key, 'Count': value }));
  const daily = Object.entries(stats.dailyCounts || {}).map(([key, value]) => ({ 'Day': key, 'Count': value }));
  const departments = (stats.departmentData || []).map(item => ({ 'Department': item.department, 'Total Reports': item.total, 'Approval Rate': item.approvalRate + '%', 'Avg Processing Time': item.avgProcessingTime }));

  const exportMap = {
    'reports-by-category': categories,
    'status-report': statuses,
    'officer-report': officers,
    'prison-block-report': blocks,
    'monthly-report': monthly,
    'daily-activity': daily,
    'department-performance': departments,
  };

  const exportRows = [];
  selectedTypes.forEach(type => {
    if (exportMap[type]) {
      exportRows.push(...exportMap[type]);
    }
  });
  return exportRows.length ? exportRows : rows.map(row => ({ 'Report ID': row.reportId || row.id || '', 'Title': row.title || '' }));
}

function exportReportsPDF() {
  if (!reportsDashboardState.payload) {
    notifyReports('No reports data available to export yet.', 'error');
    return;
  }
  const html = createReportExportHtml(reportsDashboardState.payload, getSelectedReportTypes());
  const win = window.open('', '_blank', 'width=1200,height=900');
  if (!win) {
    notifyReports('Please allow popups to export PDF.', 'error');
    return;
  }
  win.document.write(html);
  win.document.close();
  win.focus();
  setTimeout(() => win.print(), 500);
  notifyReports('Report opened in print preview. Save as PDF from browser.', 'success');
}

function exportReportsExcel() {
  if (!reportsDashboardState.payload) {
    notifyReports('No reports data available to export yet.', 'error');
    return;
  }

  if (window.XLSX && typeof XLSX !== 'undefined') {
    try {
      const workbook = createExcelWorkbook(reportsDashboardState.payload);
      const workbookOut = XLSX.write(workbook, { bookType: 'xlsx', type: 'array', cellStyles: true });
      const blob = new Blob([workbookOut], { type: 'application/octet-stream' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `reports_${new Date().toISOString().slice(0, 10)}.xlsx`;
      a.click();
      URL.revokeObjectURL(url);
      notifyReports('Excel report downloaded.', 'success');
      return;
    } catch (error) {
      console.warn('XLSX export failed, falling back to legacy XML export', error);
    }
  }

  const excelXml = createExcelWorkbook(reportsDashboardState.payload);
  const blob = new Blob([excelXml], { type: 'application/vnd.ms-excel;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `reports_${new Date().toISOString().slice(0, 10)}.xls`;
  a.click();
  URL.revokeObjectURL(url);
  notifyReports('Excel report downloaded.', 'success');
}

function exportReportsCSV() {
  if (!reportsDashboardState.payload) {
    notifyReports('No reports data available to export yet.', 'error');
    return;
  }
  const data = buildExportSections(reportsDashboardState.payload, getSelectedReportTypes()).detailRows;
  if (!data.length) {
    notifyReports('No reports found for the selected filters.', 'error');
    return;
  }
  const headers = Object.keys(data[0]);
  const csvRows = [headers.join(',')];
  data.forEach(row => {
    csvRows.push(headers.map(header => `"${String(row[header] ?? '').replace(/"/g, '""')}"`).join(','));
  });
  const blob = new Blob(["\uFEFF" + csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `reports_${new Date().toISOString().slice(0, 10)}.csv`;
  a.click();
  URL.revokeObjectURL(url);
  notifyReports('CSV export downloaded.', 'success');
}

function printReportsReport() {
  if (!reportsDashboardState.payload) {
    notifyReports('No reports data available to print yet.', 'error');
    return;
  }
  const html = createReportExportHtml(reportsDashboardState.payload, getSelectedReportTypes());
  const win = window.open('', '_blank', 'width=1200,height=900');
  if (!win) {
    notifyReports('Please allow popups to print report.', 'error');
    return;
  }
  win.document.write(html);
  win.document.close();
  win.focus();
  setTimeout(() => win.print(), 500);
  notifyReports('Print preview opened.', 'success');
}

async function initReportsDashboard() {
  setReportsLoading(true);
  try {
    const response = await apiCall('api/analytics.php', { action: 'operational_dashboard', filters: getReportsFilters() });
    if (!response.success) {
      throw new Error(response.message || response.msg || 'Unable to load report data');
    }
    reportsDashboardState.payload = response;
    reportsDashboardState.rows = response.table || [];
    renderSummaryCards(response.statistics || {});
    renderTable(reportsDashboardState.rows);
    if (response.message) {
      notifyReports(response.message, response.success ? 'info' : 'error');
    }
    if (response.charts) {
      renderChart('chartTimeline', response.charts.timeline);
      renderChart('chartCategory', response.charts.category);
      renderChart('chartStatus', response.charts.status);
      renderChart('chartOfficer', response.charts.officer);
      renderChart('chartBlock', response.charts.block);
      renderChart('chartMonthly', response.charts.monthly);
      renderChart('chartDaily', response.charts.daily);
      renderChart('chartDepartment', response.charts.department);
    }
  } catch (error) {
    notifyReports(error.message || 'Unable to refresh reports', 'error');
    renderTable([]);
    ['chartTimeline','chartCategory','chartStatus','chartOfficer','chartBlock','chartMonthly','chartDaily','chartDepartment'].forEach(clearChart);
  } finally {
    setReportsLoading(false);
  }
}

document.addEventListener('appReady', () => {
  if (document.getElementById('page-reports')) {
    initReportsDashboard();
  }
});
window.initReportsDashboard = initReportsDashboard;
window.exportReportsPDF = exportReportsPDF;
window.exportReportsExcel = exportReportsExcel;
window.exportReportsCSV = exportReportsCSV;
window.printReportsReport = printReportsReport;
</script>
